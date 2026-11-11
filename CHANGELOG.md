# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added

- Dark mode switch with three choices (US-278): "Automatic", "Light", "Dark". "Automatic" removes the remembered choice (`industry-trade:color-mode`) and the page follows `prefers-color-scheme` again, including when the system changes during the visit. A `industry-trade:color-mode-change` event is dispatched on `document`.
- CSP-strict ready script (US-279): the script carries the application nonce when it exposes the Twig variable `industry_trade_script_nonce`; the SHA-256 hash of the script is published in both READMEs and checked by `ColorModeScriptCspTest`.

### Changed

- The top-bar toggle button (`data-industry-color-mode-toggle` on the button) becomes a group of three buttons (`data-industry-color-mode-choice`); translation key `industry_theme.color_mode.toggle` is replaced by `group`, `auto`, `light`, `dark`.

### Fixed

- Readable top bar link (US-277): the link of the Sylius core top bar (black background, `sylius_shop.base.header.top_bar`) no longer takes the theme's general dark blue link color (1.8:1). It uses dedicated tokens (`$industry-topbar-link`, `$industry-dark-topbar-link`, hover and focus included), at 7:1 or more in light and dark modes, with a 3:1 focus outline. The language dropdown and the mode toggle are untouched, and a channel without the theme keeps the core rendering.
- `ColorContrastTest` now measures the top bar link pairs (light, dark, hover, focus).

## [0.2.0] - 2026-11-11

### Added

- Optional dark mode (US-226): dark token set under `[data-theme="industry-trade"]`, applied by the system setting (`prefers-color-scheme: dark`) or by a light/dark toggle in the shop top bar (hookable `industry_color_mode`, `config/twig_hooks/color_mode.yaml`). The choice is remembered in `localStorage`; with storage unavailable the mode applies to the page without error; an inline script in the `<head>` applies it before the first paint. No dark mode by default.
- Contrast audit of the dark pairs in CI (`ColorContrastTest`, 7:1 for text, 3:1 for control borders), reading the `$industry-dark-*` tokens.
- Light and dark screenshots in both READMEs.

### Changed

- The theme pages read their colors from `--industry-*` custom properties (same light values) so the dark mode can redefine them.

### Upgrade

- To get the toggle, copy `config/twig_hooks/color_mode.yaml`. Without it, the theme follows the system setting only.
- With a strict Content-Security-Policy, allow the inline `<script data-industry-color-mode>` with a nonce or a hash (see the README).

[Unreleased]: https://github.com/thibmonier/sylius-industry-trade-theme/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/thibmonier/sylius-industry-trade-theme/compare/v0.1.1...v0.2.0
