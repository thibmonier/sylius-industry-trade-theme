#!/usr/bin/env bash
# Blank project job (US-273): render assertions on the shop served at port $1, run from the Sylius project.
# Each failure names the page and what is missing, then prints the end of the application log.
set -euo pipefail

PORT="${1:?port}"
LOG="var/log/${APP_ENV:-prod}.log"
FAILED=0

fail() {
    echo "::error title=Blank project render::$1"
    FAILED=1
}

# fetch <host> <path> <output file>: prints the HTTP status, follows the locale redirect.
fetch() {
    curl -sS -L -o "$3" -w '%{http_code}' --resolve "$1:$PORT:127.0.0.1" "http://$1:$PORT$2"
}

status=$(fetch themed.localhost / themed-home.html)
[ "$status" = 200 ] || fail "themed channel, home: HTTP $status instead of 200"
grep -q 'data-theme="industry-trade"' themed-home.html \
    || fail "themed channel, home: no data-theme=\"industry-trade\" on the page (theme not active)"
grep -q '/build/app/shop/' themed-home.html \
    || fail "themed channel, home: the stylesheet of the application entry is not linked"

slug=$(mysql -h 127.0.0.1 -u root -N -B "$DATABASE_NAME" -e "SELECT tt.slug FROM sylius_product p
    JOIN sylius_taxon_translation tt ON tt.translatable_id = p.main_taxon_id AND tt.locale = 'en_US' LIMIT 1")
status=$(fetch themed.localhost "/en_US/taxons/$slug" themed-list.html)
[ "$status" = 200 ] || fail "themed channel, product list /en_US/taxons/$slug: HTTP $status instead of 200"
grep -q 'industry-product-table' themed-list.html \
    || fail "themed channel, product list: the dense product table of the theme is not rendered"

status=$(fetch plain.localhost / plain-home.html)
[ "$status" = 200 ] || fail "channel without the theme, home: HTTP $status instead of 200"
if grep -q 'data-theme="industry-trade"' plain-home.html; then
    fail "channel without the theme, home: data-theme=\"industry-trade\" is present"
fi

if ! grep -lqE 'data-theme=.?industry-trade' public/build/app/shop/*.css 2>/dev/null; then
    fail "compiled styles: no [data-theme=industry-trade] rule in public/build/app/shop/*.css (styles not imported)"
fi

if [ "$FAILED" = 1 ]; then
    echo "--- errors of $LOG (deprecations left out)"
    grep -E '"level(_name)?":("?(ERROR|CRITICAL|ALERT|EMERGENCY)"?|[45][0-9]{2})|\.(ERROR|CRITICAL):' "$LOG" 2>/dev/null | tail -n 5 | cut -c1-2000 || true
    echo "--- exception of the themed home, replayed with APP_DEBUG=1"
    APP_DEBUG=1 php -d variables_order=EGPCS -S 127.0.0.1:8081 -t public > var/log/server-debug.log 2>&1 &
    sleep 3
    curl -sS -L --resolve themed.localhost:8081:127.0.0.1 http://themed.localhost:8081/ -o debug-home.html || true
    grep -o '<title>[^<]*</title>' debug-home.html | head -n 1 || true
    grep -oE 'class="exception-message[^"]*"[^>]*>[^<]*' debug-home.html | head -n 3 || true
    echo "--- end of var/log/server.log"
    tail -n 20 var/log/server.log 2>/dev/null || true
    exit 1
fi
echo "Render assertions passed: themed home and product list, channel without the theme, compiled styles."
