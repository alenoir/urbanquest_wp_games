# Urban Quest WordPress Theme

## Informations importantes

**Ce projet est un child theme de Hestia.**

### Structure du projet

- **Thème parent** : Hestia
- **Thème enfant** : Urban Quest (ce projet)
- **Post types personnalisés** : `game`, `ville`, `departement`, `region`, `country`
- **ACF** : Utilisation d'Advanced Custom Fields pour les champs personnalisés

### Fichiers principaux

- `single-game.php` : Template pour les pages individuelles de jeux
- `single-region.php` : Template pour les pages individuelles de régions
- `acf.json` : Configuration des champs ACF
- `functions.php` : Fonctions personnalisées du thème enfant
- `Code.gs` : Script Google Apps Script pour la synchronisation

### Configuration spécifique

#### Suppression des métadonnées "Publié par"

Les métadonnées "Publié par" sont supprimées pour le post type `game` via des hooks WordPress dans `functions.php` et `single-game.php`.

#### Suppression des articles similaires

La section "Articles similaires" est désactivée pour le post type `game`.

#### Champs ACF configurables pour les jeux

- `prix` : Prix du jeu (défaut: "39€")
- `nombre_joueurs` : Nombre de joueurs (défaut: "2 – 5 joueurs")
- `age_minimum` : Âge minimum (défaut: "à partir de 8 ans")
- `duree` : Durée du jeu (défaut: "60 minutes")

### Notes techniques

- Les hooks WordPress doivent être dans `functions.php` du thème enfant pour fonctionner correctement
- Le thème utilise des templates personnalisés pour les post types `game` et `region`
- Les relations entre les entités (ville → département → région) sont gérées via ACF relationships
