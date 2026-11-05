# Sylius Industry & Trade Theme

> Français : [README.fr.md](README.fr.md) <!-- vitrine:lang -->

B2B theme for [Sylius](https://sylius.com) 2 optimized for industry and trade: high information density, AAA contrasts.

## Features

- **Design tokens**: color palette with WCAG AAA contrasts (≥ 7:1), overridable via CSS custom properties
- **Common components**: header, footer, buttons, tables, forms, messages
- **Compatibility**: Sylius 2.2 and 2.3, PHP 8.3+
- **Assets**: Webpack Encore

## Prerequisites

- PHP 8.3 or higher
- Sylius 2.2.9 or 2.3+
- Sylius Theme Bundle ^2.4

## Installation

```bash
composer require tmonier/sylius-industry-trade-theme
```

Copy the Twig hooks configuration to your application:

```bash
cp vendor/tmonier/sylius-industry-trade-theme/config/packages/tmonier_sylius_industry_trade_theme.yaml config/packages/
```

### Install assets

```bash
php bin/console sylius:theme:assets:install
npm run build
```

### Theme activation

The theme is activated by setting the `theme_name` of a Sylius channel.

## Usage

### Customize design tokens

The theme defines CSS custom properties on `:root` (colors, spacing, fonts). To override them, add your own values in your application stylesheet:

```css
:root {
    --bs-primary: #your-navy;
    --industry-accent: #your-accent;
    --bs-link-color: #your-link-color;
    --bs-link-hover-color: #your-hover-color;
}
```

See `assets/styles/theme.scss` for the complete list of available custom properties.

### Override components

Twig templates can be overridden in `templates/bundles/SyliusShopBundle/` of your application. The priority order is:

1. Application: `templates/bundles/SyliusShopBundle/`
2. Theme: `vendor/tmonier/sylius-industry-trade-theme/templates/`
3. Sylius Bundle: `vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/Resources/views/`

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
- **Product list**: one row per product with reference, designation, stock and price excl. VAT; below 768 px each row becomes a card. A product without image shows a placeholder.
- **Product page**: product and variant references, availability, technical sheet (product attributes) and a Documents list for attributes whose text value is a link to a pdf, dwg, step, zip, doc or xls file. Blocks from plugins (for example tier pricing) are left to the plugins and simply absent when the plugin is not installed.
- Texts are in the `industry_trade` translation domain (`translations/`, English and French).
- Implemented as theme template overrides (`templates/bundles/SyliusShopBundle/`), which only apply to channels using the theme: Twig hooks are global and would break channels without the theme.

<!-- vitrine:demo-b2b:start -->
## B2B demo

This plugin is used in the [Sylius B2B demo](https://github.com/thibmonier/sylius-b2b-demo): a Sylius 2.3 shop with fictitious sample data where the B2B plugins work together. The source code, setup and fixtures of the demo are in that repository.

- Online demo: [sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)
- Author: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

The demo is an example, not a production setup.
<!-- vitrine:demo-b2b:end -->

## License

MIT
