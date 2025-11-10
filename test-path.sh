#!/bin/bash

# Script de test pour vérifier le chemin exact du thème sur le serveur Hostinger

# Couleurs
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Charger les variables d'environnement depuis .env
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
else
    echo -e "${RED}Erreur: Le fichier .env n'existe pas${NC}"
    exit 1
fi

# Construire la commande SSH
SSH_CMD="ssh"
if [ ! -z "$DEPLOY_PORT" ] && [ "$DEPLOY_PORT" != "22" ]; then
    SSH_CMD="ssh -p $DEPLOY_PORT"
fi

echo -e "${YELLOW}Vérification du chemin sur le serveur...${NC}"
echo "Connexion à: ${DEPLOY_USER}@${DEPLOY_HOST}"
echo ""

# Tester différents chemins possibles
PATHS_TO_TEST=(
    "/domains/urbanquest.fr/public_html/wp-content/themes/hestia-child"
    "/home/u825816766/domains/urbanquest.fr/public_html/wp-content/themes/hestia-child"
    "/home/${DEPLOY_USER}/domains/urbanquest.fr/public_html/wp-content/themes/hestia-child"
    "/home/${DEPLOY_USER}/public_html/wp-content/themes/hestia-child"
    "~/domains/urbanquest.fr/public_html/wp-content/themes/hestia-child"
    "~/public_html/wp-content/themes/hestia-child"
)

echo -e "${YELLOW}Test des chemins possibles:${NC}"
echo ""

for TEST_PATH in "${PATHS_TO_TEST[@]}"; do
    echo -n "Test: $TEST_PATH ... "
    if eval "$SSH_CMD ${DEPLOY_USER}@${DEPLOY_HOST} \"test -d $TEST_PATH\"" 2>/dev/null; then
        echo -e "${GREEN}✓ EXISTE${NC}"
        echo ""
        echo -e "${GREEN}Chemin trouvé: $TEST_PATH${NC}"
        echo ""
        echo "Contenu du répertoire:"
        eval "$SSH_CMD ${DEPLOY_USER}@${DEPLOY_HOST} \"ls -la $TEST_PATH\"" 2>/dev/null | head -10
        exit 0
    else
        echo -e "${RED}✗ n'existe pas${NC}"
    fi
done

echo ""
echo -e "${YELLOW}Aucun chemin trouvé. Recherche du répertoire wp-content...${NC}"
echo ""

# Chercher wp-content
WP_CONTENT_PATHS=(
    "/domains/urbanquest.fr/public_html/wp-content"
    "/home/u825816766/domains/urbanquest.fr/public_html/wp-content"
    "/home/${DEPLOY_USER}/domains/urbanquest.fr/public_html/wp-content"
    "/home/${DEPLOY_USER}/public_html/wp-content"
)

for WP_PATH in "${WP_CONTENT_PATHS[@]}"; do
    echo -n "Test wp-content: $WP_PATH ... "
    if eval "$SSH_CMD ${DEPLOY_USER}@${DEPLOY_HOST} \"test -d $WP_PATH/themes\"" 2>/dev/null; then
        echo -e "${GREEN}✓ EXISTE${NC}"
        echo ""
        echo -e "${GREEN}Répertoire themes trouvé: $WP_PATH/themes${NC}"
        echo ""
        echo "Thèmes disponibles:"
        eval "$SSH_CMD ${DEPLOY_USER}@${DEPLOY_HOST} \"ls -la $WP_PATH/themes\"" 2>/dev/null
        exit 0
    else
        echo -e "${RED}✗ n'existe pas${NC}"
    fi
done

echo ""
echo -e "${RED}Aucun chemin trouvé.${NC}"
echo ""
echo "Essayez de vous connecter manuellement et vérifiez le chemin avec:"
echo "  $SSH_CMD ${DEPLOY_USER}@${DEPLOY_HOST}"
echo "  pwd"
echo "  find ~ -name 'hestia-child' -type d 2>/dev/null"

