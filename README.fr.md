# Sylius Industry & Trade Theme

> English: [README.md](README.md) <!-- vitrine:lang -->

Thème B2B pour [Sylius](https://sylius.com) 2 optimisé pour l'industrie et le négoce : haute densité d'information, contrastes AAA.

## Caractéristiques

- **Jetons de design** : palette de couleurs avec contrastes WCAG AAA (≥ 7:1), surchargeables via propriétés CSS personnalisées
- **Composants communs** : en-tête, pied de page, boutons, tableaux, formulaires, messages
- **Panier, compte et pages de contenu** : panier dense (référence, quantité modifiable, prix HT), navigation du compte au style du thème (entrées des plugins seulement si le plugin est installé), gabarit de page de contenu `@TmonierSyliusIndustryTradeTheme/content/page.html.twig`
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
cp -r vendor/tmonier/sylius-industry-trade-theme/config/twig_hooks config/
```

Les gabarits des hooks du panier, du compte et des pages de contenu sont dans le namespace Twig `@TmonierSyliusIndustryTradeTheme` (déclaré par `twig.paths` dans la config copiée), résolu sur tous les canaux ; chacun rend le gabarit du cœur tant que le thème n'est pas actif sur le canal courant, donc un canal sans thème garde les pages standard. Les hooks sont dans `config/twig_hooks/cart_account.yaml`, importé par le fichier principal. Ils ne changent que le gabarit de hookables existants : les pages des autres plugins continuent de fonctionner ; le lien vers la saisie rapide d'un panier vide n'apparaît que si le plugin quick-order est installé.

### Installation des assets

```bash
php bin/console sylius:theme:assets:install
npm run build
```

### Activation du thème

Le thème est activé en définissant le `theme_name` d'un canal Sylius.

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

## Captures

| Panier (12 lignes) | Panier vide |
|---|---|
| ![Panier dense](docs/cart.png) | ![Panier vide](docs/cart-empty.png) |

| Navigation du compte | Page de contenu |
|---|---|
| ![Compte](docs/account.png) | ![Page de contenu](docs/content-page.png) |

<!-- vitrine:demo-b2b:start -->
## Démo B2B

Ce plugin est utilisé dans la [démo B2B Sylius](https://github.com/thibmonier/sylius-b2b-demo) : une boutique Sylius 2.3 avec des données d'exemple fictives, où les plugins B2B fonctionnent ensemble. Le code source, l'installation et les fixtures de la démo sont dans ce dépôt.

- Démo en ligne : [sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)
- Auteur : [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

La démo est un exemple, pas une configuration de production.
<!-- vitrine:demo-b2b:end -->

## Licence

MIT
