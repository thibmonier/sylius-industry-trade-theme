// Dark mode contrast audit of the Blank project job (US-280).
//
// Usage: node audit-dark-contrast.mjs <base url> <path> [<path>...]
// Environment: CHROME_PATH (Chrome or Chromium binary).
//
// Each page is loaded with prefers-color-scheme: dark emulated, then audited by axe-core. Only the two contrast rules
// run: the other axe findings of the Sylius core pages (landmarks, ARIA) have nothing to do with the dark mode and
// would turn the job red for reasons the theme does not control. Exit status 1 on any violation, naming rule and selector.

import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import puppeteer from 'puppeteer-core';

const CONTRAST_RULES = ['color-contrast', 'color-contrast-enhanced'];

const [baseUrl, ...paths] = process.argv.slice(2);
const chromePath = process.env.CHROME_PATH;

if (!baseUrl || paths.length === 0 || !chromePath) {
    console.error('Usage: CHROME_PATH=<chrome> node audit-dark-contrast.mjs <base url> <path> [<path>...]');
    process.exit(2);
}

const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');

async function audit(browser, path) {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'dark' }]);

    const response = await page.goto(new URL(path, baseUrl).href, { waitUntil: 'networkidle0' });
    if (!response || response.status() !== 200) {
        throw new Error(`${path}: HTTP ${response ? response.status() : 'no response'} instead of 200`);
    }

    const mode = await page.evaluate(() => matchMedia('(prefers-color-scheme: dark)').matches);
    if (!mode) {
        throw new Error(`${path}: the dark color scheme is not emulated`);
    }

    await page.evaluate(axeSource);
    const results = await page.evaluate(
        (rules) => axe.run(document, { runOnly: { type: 'rule', values: rules }, resultTypes: ['violations'] }),
        CONTRAST_RULES,
    );
    await page.close();

    return results.violations.flatMap((violation) =>
        violation.nodes.map((node) => ({
            path,
            rule: violation.id,
            selector: node.target.join(' '),
            detail: (node.any[0] ?? node.all[0] ?? { message: '' }).message.replace(/\s+/g, ' '),
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
        const found = await audit(browser, path);
        console.log(`${path}: ${found.length} contrast violation(s) in dark mode`);
        failures = failures.concat(found);
    }
} finally {
    await browser.close();
}

for (const failure of failures) {
    console.log(`::error title=Dark mode contrast::${failure.path} ${failure.rule} ${failure.selector} ${failure.detail}`);
}

console.log(`Total: ${failures.length} violation(s)`);
process.exit(failures.length === 0 ? 0 : 1);
