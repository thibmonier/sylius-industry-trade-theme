# Thème Industrie & Négoce pour Sylius

Thème B2B pour Sylius 2 optimisé pour l'industrie et le négoce : haute densité d'information, contrastes AAA, saisie rapide.

## Caractéristiques

- **Jetons de design** : palette de couleurs avec contrastes WCAG AAA (≥ 7:1), surchargeables via variables SCSS
- **Composants communs** : en-tête, pied de page, boutons, tableaux, formulaires, messages
- **Compatibilité** : Sylius 2.2 et 2.3, PHP 8.3+
- **Assets** : Webpack Encore, Stimulus controllers
- **Installation** : recette Flex pour configuration automatique

## Installation

```bash
composer require tmonier/sylius-industry-trade-theme
```

La recette Flex copie automatiquement la configuration des Twig hooks dans `config/packages/`.

### Installation des assets

```bash
php bin/console sylius:theme:assets:install
npm run build
```

### Activation du thème

Le thème est activé automatiquement via le ThemeBundle de Sylius. Consultez la [documentation officielle](https://docs.sylius.com/en/latest/book/themes/index.html) pour personnaliser le contexte de thème.

## Utilisation

### Personnalisation des jetons de design

Les jetons de couleur, typographie et espacements sont définis dans `assets/styles/_variables.scss`. Pour les surcharger :

1. Créez votre propre fichier SCSS dans votre application
2. Redéfinissez les variables avant d'importer le thème :

```scss
// config/packages/assets/styles/custom-theme.scss
$industry-navy: #your-color;
$industry-accent: #your-accent;

@use '~tmonier/sylius-industry-trade-theme/assets/styles/theme.scss';
```

### Surcharge des composants

Les gabarits Twig peuvent être surchargés dans `templates/bundles/SyliusShopBundle/` de votre application. L'ordre de priorité est :

1. Application : `templates/bundles/SyliusShopBundle/`
2. Thème : `vendor/tmonier/sylius-industry-trade-theme/templates/`
3. Bundle Sylius : `vendor/sylius/sylius/src/Sylius/Bundle/ShopBundle/Resources/views/`

## Démo

Voir le thème en action sur la [démo B2B Sylius](https://sylius-b2b.thibaut-monier.fr).

## Auteur

**Thibaut Monier**  
Delivery Manager / CTO · Expert Sylius & Symfony

- [Profil LinkedIn](https://www.linkedin.com/in/thibautmonier/)
- [Démo](https://sylius-b2b.thibaut-monier.fr)

## Licence

MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Contributions

Les contributions sont les bienvenues ! Ouvrez une issue ou une pull request sur GitHub.

## Support

Pour toute question ou demande de support, ouvrez une issue sur [GitHub](https://github.com/thibmonier/sylius-industry-trade-theme/issues).
