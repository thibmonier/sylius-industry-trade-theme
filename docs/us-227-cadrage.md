# US-227 — Cadrage (T-227-01)

## Page cible

La liste dense du thème (`templates/bundles/SyliusShopBundle/product/index/content/body/main/products.html.twig`), c'est-à-dire
les pages de taxon et de résultats de recherche. Le thème ne dépend pas du plugin quick-order : sa page `/quick-order`
garde ses propres champs, le thème n'y touche pas.

## Source de l'autocomplétion

- Le cœur n'expose aucun point d'accès qui cherche par référence : le filtre de recherche de la liste ne porte que sur
  `translation.name`, et l'API Shop (`/api/v2/shop/products`) filtre `translations.name` en « partial » seulement, et n'est
  pas forcément activée dans l'application hôte.
- Source retenue : les lignes déjà rendues sur la page (référence et désignation), sans requête réseau. Les options sont rendues
  côté serveur, le script ne fait que filtrer (attribut `hidden`). La recherche sur tout le catalogue reste celle du cœur.

## Palette de commandes (retirée le 2026-11-11)

Le plugin de palette est un plugin du back-office : son dialogue est rendu par les hooks `admin` et son `assets/shop/entrypoint.js`
est vide. Aucune palette n'existe donc en boutique aujourd'hui. Détection sans dépendance : le lien du thème est masqué et
n'apparaît que si la page contient un déclencheur `[data-tmonier-command-palette-trigger]` ; un clic le déclenche. Tant qu'aucun
plugin ne rend ce déclencheur en boutique, le lien reste invisible (à dire dans le README, sans promesse).

**Décision de Thibaut du 2026-11-11 :** le lien est retiré du périmètre (code sans effet tant qu'aucune palette n'existe en boutique). Le scénario « Palette de commandes » sort de la story.

## Estimation

5 points, inchangée : champ combobox + région live (2), navigation clavier de la grille (1,5), tests avec
script exécuté sous Node, AAA, README et CHANGELOG (1,5).
