# Sylius Industry & Trade Theme

> Français : [README.fr.md](README.fr.md) <!-- vitrine:lang -->

B2B theme for [Sylius](https://sylius.com) 2 optimized for industry and trade: high information density, AAA contrasts, fast input.

## Features

- **Design tokens**: color palette with WCAG AAA contrasts (≥ 7:1), overridable via SCSS variables
- **Common components**: header, footer, buttons, tables, forms, messages
- **Compatibility**: Sylius 2.2 and 2.3, PHP 8.3+
- **Assets**: Webpack Encore, Stimulus controllers
- **Installation**: Flex recipe for automatic configuration

## Prerequisites

- PHP 8.3 or higher
- Sylius 2.2.9 or 2.3+
- Sylius Theme Bundle ^2.4

## Installation

```bash
composer require tmonier/sylius-industry-trade-theme
```

The Flex recipe automatically copies the Twig hooks configuration to `config/packages/`.

### Install assets

```bash
php bin/console sylius:theme:assets:install
npm run build
```

### Theme activation

The theme is activated by setting the `theme_name` of a Sylius channel. See the [official documentation](https://docs.sylius.com/en/latest/book/themes/index.html) for theme context customization.

## Usage

### Customize design tokens

Color, typography and spacing tokens are defined in `assets/styles/_variables.scss`. To override them:

1. Create your own SCSS file in your application
2. Redefine variables before importing the theme:

```scss
// config/packages/assets/styles/custom-theme.scss
$industry-navy: #your-color;
$industry-accent: #your-accent;

@use '~tmonier/sylius-industry-trade-theme/assets/styles/theme.scss';
```

### Override components

Twig templates can be overridden in `templates/bundles/SyliusShopBundle/` of your application. The priority order is:

1. Application: `templates/bundles/SyliusShopBundle/`
2. Theme: `vendor/tmonier/sylius-industry-trade-theme/templates/`
3. Sylius Bundle: `vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/Resources/views/`

<!-- vitrine:demo-b2b:start -->
## B2B demo

This plugin is used in the [Sylius B2B demo](https://github.com/thibmonier/sylius-b2b-demo): a Sylius 2.3 shop with fictitious sample data where the B2B plugins work together. The source code, setup and fixtures of the demo are in that repository.

- Online demo: [sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)
- Author: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

The demo is an example, not a production setup.
<!-- vitrine:demo-b2b:end -->

## License

MIT
