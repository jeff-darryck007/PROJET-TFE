#!/bin/bash
# Mise à jour du site en production (alwaysdata).
# À lancer sur le serveur, en SSH, depuis le dossier du projet :
#   ./deploy.sh              → met à jour le backend (code, dépendances, base de données)
#   ./deploy.sh --frontend   → idem + recompile le frontend Vue sur le serveur
# Voir DEPLOIEMENT.md pour l'installation initiale.
set -e
cd "$(dirname "$0")"

echo "== Récupération de la dernière version depuis GitHub"
git pull --ff-only

echo "== Backend : dépendances, base de données, cache"
cd backend-TFE
composer install --no-dev --optimize-autoloader --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear
cd ..

if [ "$1" = "--frontend" ]; then
    echo "== Frontend : compilation et copie dans backend-TFE/public"
    cd frontend-TFE
    npm ci
    npm run build
    rm -rf ../backend-TFE/public/assets
    cp -r dist/. ../backend-TFE/public/
    cd ..
fi

echo "== Terminé"
