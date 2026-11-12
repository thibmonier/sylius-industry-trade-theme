// Accessibility audit of the Blank project job (US-280 for the dark contrast, US-281 for the full rule set).
//
// Usage: node audit-axe.mjs <base url> <path> [<path>...]
// Environment: CHROME_PATH (Chrome or Chromium binary).
//
// Each page is loaded twice, with prefers-color-scheme light then dark emulated, and audited by axe-core with every
// WCAG 2.x rule of levels A, AA and AAA plus the best practices. A themed page must have no violation at all, including
// the ones inherited from the Sylius core pages: the theme overrides what it needs to. Exit status 1 on any violation,
// naming the color scheme, the rule and the selector.

import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import puppeteer from 'puppeteer-core';

const TAGS = ['wcag2a', 'wcag2aa', 'wcag2aaa', 'wcag21a', 'wcag21aa', 'wcag21aaa', 'wcag22aa', 'best-practice'];
const SCHEMES = ['light', 'dark'];

const [baseUrl, ...paths] = process.argv.slice(2);
const chromePath = process.env.CHROME_PATH;

if (!baseUrl || paths.length === 0 || !chromePath) {
    console.error('Usage: CHROME_PATH=<chrome> node audit-axe.mjs <base url> <path> [<path>...]');
    process.exit(2);
}

const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');

async function audit(browser, path, scheme) {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: scheme }]);

    const response = await page.goto(new URL(path, baseUrl).href, { waitUntil: 'networkidle0' });
    if (!response || response.status() !== 200) {
        throw new Error(`${path}: HTTP ${response ? response.status() : 'no response'} instead of 200`);
    }

    const emulated = await page.evaluate((s) => matchMedia(`(prefers-color-scheme: ${s})`).matches, scheme);
    if (!emulated) {
        throw new Error(`${path}: the ${scheme} color scheme is not emulated`);
    }

    await page.evaluate(axeSource);
    const results = await page.evaluate(
        (tags) => axe.run(document, { runOnly: { type: 'tag', values: tags }, resultTypes: ['violations'] }),
        TAGS,
    );
    await page.close();

    return results.violations.flatMap((violation) =>
        violation.nodes.map((node) => ({
            path,
            scheme,
            rule: violation.id,
            selector: node.target.join(' '),
            detail: (node.any[0] ?? node.all[0] ?? node.none[0] ?? { message: '' }).message.replace(/\s+/g, ' '),
        })),
    );
}

const browser = await puppeteer.launch({
    executablePath: chromePath,
    headless: true,
    args: ['--no-sandbox', '--disable-gpu'],
});

let failures = [];
try {
    for (const path of paths) {
        for (const scheme of SCHEMES) {
            const found = await audit(browser, path, scheme);
            console.log(`${path} (${scheme}): ${found.length} axe violation(s)`);
            failures = failures.concat(found);
        }
    }
} finally {
    await browser.close();
}

for (const failure of failures) {
    console.log(`::error title=Accessibility (${failure.scheme})::${failure.path} ${failure.rule} ${failure.selector} ${failure.detail}`);
}

console.log(`Total: ${failures.length} violation(s)`);
process.exit(failures.length === 0 ? 0 : 1);
