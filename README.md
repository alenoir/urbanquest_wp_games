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

## Réécriture d'URL (URL Rewrite)

Les règles de réécriture d'URL sont configurées dans `functions.php` pour générer des URLs propres et SEO-friendly.

### Structures d'URL

- **jeu** : `/jeux-de-piste/%ville%/%game_title%`
- **ville** : `/jeux-de-piste/%ville%`
- **région** : `/jeux-de-piste/%region%`
- **département** : `/jeux-de-piste/%departement%`
- **pays** : `/jeux-de-piste/%pays%`

### Activation

**IMPORTANT** : Après avoir ajouté ou modifié le code de réécriture d'URL dans `functions.php`, vous devez :

1. Aller dans **Réglages > Permaliens** dans l'administration WordPress
2. Cliquer sur **"Enregistrer les modifications"** (même sans changer de réglage)
3. Cela va activer les nouvelles règles de réécriture

### Fonctionnement

- Les permaliens sont générés automatiquement lors de la création/modification d'un post
- Pour les jeux, l'URL inclut automatiquement le slug de la ville associée (via le champ ACF `city`)
- Les URLs sont générées en utilisant le slug WordPress (`post_name`) de chaque post
- Si un slug n'existe pas, il est généré automatiquement depuis le titre du post

### Notes techniques

- Les règles de réécriture sont ajoutées avec la priorité `top` pour être évaluées en premier
- La fonction `parse_request` détermine automatiquement le bon post type selon l'URL
- L'ordre de recherche pour les URLs à un seul segment est : ville → région → département → pays

## Déploiement vers Hostinger

Ce projet inclut un script de déploiement automatisé pour synchroniser facilement les fichiers vers votre serveur Hostinger.

### Prérequis

- Accès SSH/SFTP à votre serveur Hostinger
- `rsync` installé sur votre machine locale
- Les identifiants de connexion Hostinger (hôte, utilisateur, chemin)

### Configuration initiale

1. **Créer le fichier de configuration** :

   ```bash
   cp env.example .env
   ```

2. **Éditer le fichier `.env`** avec vos informations Hostinger :

   ```bash
   # Adresse du serveur Hostinger
   DEPLOY_HOST=votre-serveur.hostinger.com

   # Nom d'utilisateur FTP/SFTP
   DEPLOY_USER=votre-utilisateur

   # Chemin vers le thème sur le serveur
   # Exemple: /domains/votresite.com/public_html/wp-content/themes/urbanquest-wp-sheet
   DEPLOY_PATH=/domains/votresite.com/public_html/wp-content/themes/urbanquest-wp-sheet

   # Port SSH (optionnel, par défaut 22)
   DEPLOY_PORT=22
   ```

3. **Rendre le script exécutable** :
   ```bash
   chmod +x deploy.sh
   ```

### Utilisation

Pour déployer vos modifications vers Hostinger :

**Mode interactif** (recommandé pour la première fois) :

```bash
./deploy.sh
```

Le script va :

- Afficher un aperçu des fichiers à synchroniser
- Demander confirmation avant le déploiement
- Synchroniser uniquement les fichiers modifiés (rsync)
- Exclure automatiquement les fichiers de configuration locale (.git, .env, etc.)

**Mode non-interactif** (pour déploiements rapides) :

```bash
./deploy.sh --yes
# ou
./deploy.sh -y
```

Le script déploie directement sans demander de confirmation.

### Notes importantes

- Le fichier `.env` contient des informations sensibles et ne doit **jamais** être commité dans Git
- Le script utilise `rsync` avec l'option `--delete` pour supprimer les fichiers qui n'existent plus localement
- Après le déploiement, si vous avez modifié les règles de réécriture d'URL, n'oubliez pas d'aller dans **Réglages > Permaliens** dans WordPress et de cliquer sur **"Enregistrer les modifications"**

### Alternative : Déploiement manuel via FTP

Si vous préférez utiliser un client FTP (FileZilla, Cyberduck, etc.) :

1. Connectez-vous à votre serveur Hostinger via FTP/SFTP
2. Naviguez vers `/wp-content/themes/urbanquest-wp-sheet/`
3. Téléversez les fichiers modifiés

**Fichiers à ne jamais téléverser** :

- `.git/` et `.gitignore`
- `.env` et `env.example`
- `README.md`
- `deploy.sh` et `.deployignore`
