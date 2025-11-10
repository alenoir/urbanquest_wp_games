#!/bin/bash

# Script de déploiement pour Urban Quest WordPress Theme
# Synchronise les fichiers du thème vers le serveur Hostinger
#
# Usage:
#   ./deploy.sh           # Mode interactif (demande confirmation)
#   ./deploy.sh --yes     # Mode non-interactif (déploie directement)
#   ./deploy.sh -y        # Mode non-interactif (raccourci)

set -e  # Arrêter en cas d'erreur

# Vérifier si le mode non-interactif est activé
NON_INTERACTIVE=false
if [[ "$1" == "--yes" ]] || [[ "$1" == "-y" ]]; then
    NON_INTERACTIVE=true
fi

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Charger les variables d'environnement depuis .env
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
else
    echo -e "${RED}Erreur: Le fichier .env n'existe pas${NC}"
    echo "Créez un fichier .env à partir de .env.example"
    exit 1
fi

# Vérifier que les variables requises sont définies
if [ -z "$DEPLOY_HOST" ] || [ -z "$DEPLOY_USER" ] || [ -z "$DEPLOY_PATH" ]; then
    echo -e "${RED}Erreur: Variables manquantes dans .env${NC}"
    echo "Vérifiez que DEPLOY_HOST, DEPLOY_USER et DEPLOY_PATH sont définis"
    exit 1
fi

# Afficher les informations de déploiement
echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  Déploiement Urban Quest Theme${NC}"
echo -e "${YELLOW}========================================${NC}"
echo "Serveur: ${DEPLOY_USER}@${DEPLOY_HOST}"
if [ ! -z "$DEPLOY_PORT" ] && [ "$DEPLOY_PORT" != "22" ]; then
    echo "Port SSH: ${DEPLOY_PORT}"
fi
echo "Chemin: ${DEPLOY_PATH}"
echo ""

# Demander confirmation (sauf en mode non-interactif)
if [ "$NON_INTERACTIVE" = false ]; then
    echo -e "${YELLOW}Le script va synchroniser vos fichiers locaux vers le serveur.${NC}"
    read -p "Continuer le déploiement? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Déploiement annulé${NC}"
        exit 0
    fi
else
    echo -e "${GREEN}Mode non-interactif activé - déploiement automatique${NC}"
fi

# Chemin local du thème (répertoire actuel)
LOCAL_PATH="."

# Fichiers à exclure du déploiement
EXCLUDE_FILE=".deployignore"

# Créer le fichier .deployignore s'il n'existe pas
if [ ! -f "$EXCLUDE_FILE" ]; then
    cat > "$EXCLUDE_FILE" << EOF
# Fichiers à exclure du déploiement
.git/
.gitignore
.env
.env.example
README.md
deploy.sh
.deployignore
*.md
.DS_Store
EOF
fi

# Construire la commande rsync
RSYNC_CMD="rsync -avz --delete"

# Ajouter les exclusions
if [ -f "$EXCLUDE_FILE" ]; then
    RSYNC_CMD="$RSYNC_CMD --exclude-from=$EXCLUDE_FILE"
fi

# Ajouter les options SSH si un port personnalisé est défini
SSH_RSYNC_OPTION=""
if [ ! -z "$DEPLOY_PORT" ] && [ "$DEPLOY_PORT" != "22" ]; then
    SSH_RSYNC_OPTION="-e \"ssh -p $DEPLOY_PORT\""
    RSYNC_CMD="$RSYNC_CMD $SSH_RSYNC_OPTION"
fi

# Construire la destination
DEST="${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}"

# Afficher un aperçu des fichiers qui seront synchronisés
if [ "$NON_INTERACTIVE" = false ]; then
    echo -e "${YELLOW}Prévisualisation des fichiers à synchroniser...${NC}"
    PREVIEW_CMD="rsync -avz --dry-run --exclude-from=$EXCLUDE_FILE"
    if [ ! -z "$SSH_RSYNC_OPTION" ]; then
        PREVIEW_CMD="$PREVIEW_CMD $SSH_RSYNC_OPTION"
    fi
    eval "$PREVIEW_CMD $LOCAL_PATH/ $DEST" 2>&1 | head -30
    echo ""
    echo -e "${YELLOW}Note:${NC} Seuls les fichiers modifiés seront transférés."
    echo ""
    
    # Confirmation finale
    read -p "Déployer ces fichiers? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Déploiement annulé${NC}"
        exit 0
    fi
else
    echo -e "${YELLOW}Préparation de la synchronisation...${NC}"
fi

# Exécuter la synchronisation
echo -e "${GREEN}Synchronisation en cours...${NC}"
eval "$RSYNC_CMD $LOCAL_PATH/ $DEST"

# Vérifier le résultat
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Déploiement réussi!${NC}"
    echo ""
    echo -e "${YELLOW}Note:${NC} Si vous avez modifié les règles de réécriture d'URL,"
    echo "n'oubliez pas d'aller dans Réglages > Permaliens dans WordPress"
    echo "et de cliquer sur 'Enregistrer les modifications'"
else
    echo -e "${RED}✗ Erreur lors du déploiement${NC}"
    exit 1
fi

