#!/bin/bash

# Script de deploy para Teaching Assistance
# Uso: ./deploy.sh

SSH_HOST="u943665595@167.88.34.121"
SSH_PORT="65002"
PROJECT_PATH="domains/iedagropivijay.edu.co/public_html/asistencia"
PHP_PATH="/opt/alt/php84/usr/bin/php"

echo "🚀 Iniciando deploy..."

ssh -p $SSH_PORT $SSH_HOST << EOF
    cd $PROJECT_PATH
    echo "📥 Actualizando desde GitHub..."
    git pull origin main
    echo "🧹 Limpiando caché..."
    $PHP_PATH artisan optimize:clear
    echo "✅ Deploy completado!"
EOF
