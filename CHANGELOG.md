# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

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
