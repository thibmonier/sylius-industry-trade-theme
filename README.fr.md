# Sylius Industry & Trade Theme

> English: [README.md](README.md) <!-- vitrine:lang -->

Thème B2B pour [Sylius](https://sylius.com) 2 optimisé pour l'industrie et le négoce : haute densité d'information, contrastes AAA.

## Caractéristiques

- **Jetons de design** : palette de couleurs avec contrastes WCAG AAA (≥ 7:1), surchargeables via propriétés CSS personnalisées
- **Composants communs** : en-tête, pied de page, boutons, tableaux, formulaires, messages
- **Compatibilité** : Sylius 2.2 et 2.3, PHP 8.3+
- **Assets** : Webpack Encore

## Prérequis

- PHP 8.3 ou supérieur
- Sylius 2.2.9 ou 2.3+
- Sylius Theme Bundle ^2.4

## Installation

```bash
composer require tmonier/sylius-industry-trade-theme
```

Copier la configuration des Twig hooks dans votre application :

```bash
cp vendor/tmonier/sylius-industry-trade-theme/config/packages/tmonier_sylius_industry_trade_theme.yaml config/packages/
```

### Installation des assets

```bash
php bin/console sylius:theme:assets:install
npm run build
```

### Activation du thème

Le thème est activé en définissant le `theme_name` d'un canal Sylius. Consultez la [documentation officielle](https://old-docs.sylius.com/en/latest/book/themes/index.html) pour la personnalisation du contexte de thème.

## Usage

### Personnalisation des jetons de design

Le thème définit des propriétés CSS personnalisées sur `:root` (couleurs, espacements, polices). Pour les surcharger, ajoutez vos propres valeurs dans la feuille de style de votre application :

```css
:root {
    --bs-primary: #your-navy;
    --industry-accent: #your-accent;
    --bs-link-color: #your-link-color;
    --bs-link-hover-color: #your-hover-color;
}
```

Voir `assets/styles/theme.scss` pour la liste complète des propriétés personnalisées disponibles.

### Surcharge des composants

Les gabarits Twig peuvent être surchargés dans `templates/bundles/SyliusShopBundle/` de votre application. L'ordre de priorité est :

1. Application : `templates/bundles/SyliusShopBundle/`
2. Thème : `vendor/tmonier/sylius-industry-trade-theme/templates/`
3. Bundle Sylius : `vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/Resources/views/`

<!-- vitrine:demo-b2b:start -->
## Démo B2B

Ce plugin est utilisé dans la [démo B2B Sylius](https://github.com/thibmonier/sylius-b2b-demo) : une boutique Sylius 2.3 avec des données d'exemple fictives, où les plugins B2B fonctionnent ensemble. Le code source, l'installation et les fixtures de la démo sont dans ce dépôt.

- Démo en ligne : [sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)
- Auteur : [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

La démo est un exemple, pas une configuration de production.
<!-- vitrine:demo-b2b:end -->

## Licence

MIT
