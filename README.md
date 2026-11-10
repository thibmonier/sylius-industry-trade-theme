# Sylius Industry & Trade Theme

> Français : [README.fr.md](README.fr.md) <!-- vitrine:lang -->

B2B theme for [Sylius](https://sylius.com) 2 optimized for industry and trade: high information density, AAA contrasts.

## Features

- **Design tokens**: color palette with WCAG AAA contrasts (≥ 7:1), overridable via CSS custom properties
- **Optional dark mode**: dark token set, every text/background pair at 7:1 (AAA, checked in CI), light/dark toggle remembered between visits
- **Common components**: header, footer, buttons, tables, forms, messages
- **Cart, account and content pages**: dense cart (reference, editable quantity, prices excl. tax), themed account navigation (plugin entries included only when the plugin is installed), content page layout `@TmonierSyliusIndustryTradeTheme/content/page.html.twig`
- **Compatibility**: Sylius 2.2 and 2.3, PHP 8.3+
- **Assets**: Webpack Encore

## Prerequisites

- PHP 8.3 or higher
- Sylius 2.2.9 or 2.3+
- Sylius Theme Bundle ^2.4

## Installation

<!-- blank-project: step=require -->
```bash
composer require tmonier/sylius-industry-trade-theme
```

Copy the configuration to your application:

<!-- blank-project: step=copy-config -->
```bash
cp vendor/tmonier/sylius-industry-trade-theme/config/packages/tmonier_sylius_industry_trade_theme.yaml config/packages/
cp -r vendor/tmonier/sylius-industry-trade-theme/config/twig_hooks config/
```

What the configuration does, and what it never does:

- It registers the Twig namespace `@TmonierSyliusIndustryTradeTheme` (`twig.paths`), resolved on every channel, for the templates the theme adds: the cart "Reference" column, the account navigation items and the content page layout.
- It adds two hookables, `industry_reference`, to the cart head and body (priority 350). They render nothing unless the theme is active on the current channel.
- It does **not** replace any hookable of Sylius or of your application. The themed pages (header, footer, flash messages, cart, account, product list, search field) are template overrides under `templates/bundles/SyliusShopBundle/`, which Sylius applies only to channels using the theme. The hookables you added to `sylius_shop.base.header.content`, `sylius_shop.base.footer.content` or the product list keep rendering, and your own settings on the cart hookables `items`, `coupon`, `summary`, `form` and `account` (template, props, priority) stay yours, on themed and non-themed channels alike.
- It adds one more hookable, `industry_color_mode` (`config/twig_hooks/color_mode.yaml`), to the shop top bar (`sylius_shop.base.header.top_bar`, priority -100): the light/dark toggle. It renders nothing unless the theme is active on the current channel. Do not copy that file if you do not want the toggle: the theme then only follows the system setting.
- The quick order link of an empty cart appears only when the quick-order plugin is installed.

### Install the styles and assets

The package ships SCSS sources, not compiled assets. Import them in the Encore entry of your application, **after** the Sylius shop styles (Sass loader enabled, as in a standard Sylius application):

<!-- blank-project: step=styles file=assets/shop/entrypoint.scss write=append -->
```scss
// assets/shop/entrypoint.scss (or the stylesheet imported by your shop entry)
@use '../../vendor/tmonier/sylius-industry-trade-theme/assets/styles/theme';
```

If your shop entry does not import a stylesheet yet (a fresh Sylius-Standard project), import this file from it:

<!-- blank-project: step=styles-entry file=assets/shop/entrypoint.js write=append -->
```js
import './entrypoint.scss';
```

then build as usual:

<!-- blank-project: step=build -->
```bash
npm run build
```

All the rules of the theme are scoped to `[data-theme="industry-trade"]`, an attribute the theme layout sets on `<body>`: on a channel without the theme, or elsewhere in your application, no token and no component changes.

### Theme activation

Sylius must find the theme: its filesystem source scans the directories of `sylius_theme.sources.filesystem.directories` (`composer.json` files, `scan_depth` levels deep), for example

<!-- blank-project: step=theme-source file=config/packages/sylius_theme.yaml write=replace -->
```yaml
sylius_theme:
    sources:
        filesystem:
            scan_depth: 1
            directories:
                - '%kernel.project_dir%/themes'
                - '%kernel.project_dir%/vendor/tmonier'
```

Then set the `theme_name` of a Sylius channel to `tmonier/sylius-industry-trade-theme`.

The CI runs this procedure, as written, in a fresh Sylius-Standard project (Sylius 2.2 and 2.3): the code blocks marked `blank-project` above are executed by `bin/blank-project-steps`, then the job checks a channel with the theme and a channel without it. Change a command here and the job runs the new one.

## Usage

### Customize design tokens

The theme defines its CSS custom properties on `[data-theme="industry-trade"]` (colors, spacing, fonts), not on `:root`, so they cannot collide with the variables of your application. To override them, use the same selector, loaded after the theme stylesheet:

```css
[data-theme="industry-trade"] {
    --bs-primary: #your-navy;
    --industry-accent: #your-accent;
    --bs-link-color: #your-link-color;
    --bs-link-hover-color: #your-hover-color;
}
```

See `assets/styles/theme.scss` for the complete list of available custom properties.

### Dark mode (optional)

The theme ships a dark token set under `[data-theme="industry-trade"]`. There is no dark mode by default:

- **System setting**: while the visitor has not chosen, `prefers-color-scheme: dark` applies the dark tokens; a light system stays light.
- **Toggle**: the button in the top bar ("Dark mode", `aria-pressed` announces the state, keyboard reachable) sets `data-industry-mode="dark"` or `"light"` on `<html>` and remembers the choice in `localStorage` (key `industry-trade:color-mode`). Choosing "light" overrides a dark system setting.
- **No flash**: a small inline script in the `<head>`, run before the stylesheets, applies the remembered choice before the first paint. The toggle stays hidden until that script has run (without JavaScript it would do nothing).
- **Storage unavailable** (blocked or private browsing): the mode applies to the current page, nothing is remembered, no JavaScript error.
- **CSP**: the script is inline (`<script data-industry-color-mode>`). If your Content-Security-Policy forbids inline scripts, allow it with a nonce or the hash of the script (`templates/industry_trade/_color_mode_script.html.twig`).
- **Contrast**: the dark pairs are the `$industry-dark-*` tokens of `assets/styles/_variables.scss`; `tests/Unit/Theme/ColorContrastTest.php` reads them and fails in CI, naming the pair, when one falls under 7:1 (3:1 for control borders).
- **Overrides**: your own `[data-theme="industry-trade"]` overrides apply in light mode; to change a color in dark mode, use `[data-industry-mode="dark"] [data-theme="industry-trade"] { ... }` (and the same inside `@media (prefers-color-scheme: dark)` on `html:not([data-industry-mode])` if you want it to follow the system too).
- **Logo**: in dark mode the theme puts a light rounded backdrop behind the header logo (`header a[aria-label*='logo' i]`), so a logo with dark text, like the default Sylius one, stays readable; your logo is not modified. If you ship a dark-ready logo, reset it with `[data-industry-mode="dark"] [data-theme="industry-trade"] header a[aria-label*='logo' i] { background: none; padding: 0; }`.

| Light | Dark |
|---|---|
| ![Light mode](docs/color-mode-light.png) | ![Dark mode](docs/color-mode-dark.png) |

### Override components

On a channel that uses the theme, a template of the theme **wins over** the same template in `templates/bundles/` of your application. The lookup order, for a themed channel, is:

1. the active theme, then its parent themes (`parents` in the theme configuration): `vendor/tmonier/sylius-industry-trade-theme/templates/bundles/<Bundle>/...`;
2. your application: `templates/bundles/<Bundle>/...`;
3. the Sylius bundle: `vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/templates/...`.

(This is how `sylius/theme-bundle` resolves a template: `NamespacedTemplateLocator` looks in the theme first, `ThemeHierarchyProvider` adds the parents.) A channel without the theme only sees levels 2 and 3.

To change a template the theme overrides, do not copy it into `templates/bundles/`: it would be ignored on themed channels. Create a theme of your own that declares this one as its parent, put your template in it, and set the channel to your theme:

```json
{
    "name": "acme/shop-theme",
    "extra": {
        "sylius-theme": {
            "title": "Acme",
            "parents": ["tmonier/sylius-industry-trade-theme"]
        }
    }
}
```

with `templates/bundles/SyliusShopBundle/...` next to that `composer.json` (in the `themes/` directory of your application by default). The two hookables of the "Reference" column compare the channel theme with `industry_theme_name`: if you use a child theme, redefine that Twig global to the name of your theme (`twig.globals.industry_theme_name`).

Hookables and their props stay the way to change what the theme does not override: add or configure them in your own `sylius_twig_hooks` file.

### Reviewing template drift

Every template the theme overrides under `templates/bundles/<Bundle>/**` is copied from a Sylius template. The unit test `tests/Unit/TemplateDrift/ThemeTemplateDriftTest.php` compares the fingerprint of each original (normalized sha256, per Sylius minor version) with `tests/TemplateDrift/fingerprints.json`, recorded at the last review. It runs in the `unit` testsuite and in a weekly scheduled run (Monday). Overrides are discovered automatically.

When it fails, the message names the template:

- **original template modified**: read the upstream diff between the previously reviewed Sylius version and the new one, adapt the override, then run `php bin/template-fingerprints --update`;
- **template removed upstream**: adapt or remove the override (the script refuses to write while an original is missing);
- **override not registered**: run `php bin/template-fingerprints --update` after review.

The script rewrites the fingerprints of the Sylius minor version installed in `vendor/`. To record the other minor version too, install it (`composer require sylius/sylius:~2.2.0 --no-update && composer update`), run the script again, then restore your constraint. `php bin/template-fingerprints --check` only reports the drift.

## Screenshots

Catalogue pages (rendered with Bootstrap and the theme stylesheet; the shop header and footer are shown without the full Sylius stylesheet).

| Page | Capture |
|------|---------|
| Home (hero, aisles of the channel menu taxon) | ![Home](docs/home.png) |
| Dense product list (reference, designation, stock, price excl. VAT) | ![Product list](docs/product-list.png) |
| Product list below 768 px (cards, no horizontal scroll) | ![Product list at 375 px](docs/product-list-375.png) |
| Product page (references, technical sheet, documents, availability) | ![Product page](docs/product-show.png) |

## Catalogue pages

- **Home**: title, lead text and one link per child of the channel's menu taxon (hidden when the channel has none).
- **Product list**: one row per product with reference, designation, stock, price excl. VAT and an actions cell. The price cell renders the core card hook `sylius_shop.shared.product.card.prices` and the actions cell the hookables of `sylius_shop.shared.product.card` (favourites, add to cart, quote... added by your application or plugins), so a price hidden by your application stays hidden. The exact stock is always shown; below 768 px each row becomes a card. A product without image shows a placeholder.
- **Product page**: product and variant references, availability, technical sheet (product attributes) and a Documents list for attributes whose text value is a link to a pdf, dwg, step, zip, doc or xls file. Blocks from plugins (for example tier pricing) are left to the plugins and simply absent when the plugin is not installed.
- Texts are in the `industry_trade` translation domain (`translations/`, English and French).
- Implemented as theme template overrides (`templates/bundles/SyliusShopBundle/`), which only apply to channels using the theme and leave the hook configuration of your application alone. The only global additions are the two "Reference" hookables, guarded in their templates.

| Cart (12 lines) | Empty cart |
|---|---|
| ![Dense cart](docs/cart.png) | ![Empty cart](docs/cart-empty.png) |

| Account navigation | Content page |
|---|---|
| ![Account](docs/account.png) | ![Content page](docs/content-page.png) |

<!-- vitrine:demo-b2b:start -->
## B2B demo

This plugin is used in the [Sylius B2B demo](https://github.com/thibmonier/sylius-b2b-demo): a Sylius 2.3 shop with fictitious sample data where the B2B plugins work together. The source code, setup and fixtures of the demo are in that repository.

- Online demo: [sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)
- Author: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

The demo is an example, not a production setup.
<!-- vitrine:demo-b2b:end -->

## License

MIT
