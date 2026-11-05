# Sylius Industry & Trade Theme

> Français : [README.fr.md](README.fr.md) <!-- vitrine:lang -->

B2B theme for [Sylius](https://sylius.com) 2 optimized for industry and trade: high information density, AAA contrasts.

## Features

- **Design tokens**: color palette with WCAG AAA contrasts (≥ 7:1), overridable via CSS custom properties
- **Common components**: header, footer, buttons, tables, forms, messages
- **Cart, account and content pages**: dense cart (reference, editable quantity, prices excl. tax), themed account navigation (plugin entries included only when the plugin is installed), content page layout `@TmonierSyliusIndustryTradeTheme/content/page.html.twig`
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
cp -r vendor/tmonier/sylius-industry-trade-theme/config/twig_hooks config/
```

The cart, account and content-page hook templates live in the Twig namespace `@TmonierSyliusIndustryTradeTheme` (registered by `twig.paths` in the copied config), which Twig resolves on every channel; each one renders the Sylius core template unless the theme is active on the current channel, so a channel without the theme keeps the standard pages. The hooks live in `config/twig_hooks/cart_account.yaml`, imported by the main file. They only change templates of existing hookables, so pages of other plugins keep working; the quick order link of an empty cart appears only when the quick-order plugin is installed.

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

## Screenshots

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
