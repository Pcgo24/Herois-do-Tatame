#!/bin/sh
set -e

# O Render injeta RENDER_EXTERNAL_URL com a URL final do serviço, que só é
# conhecida depois que ele é criado. Usar isso evita ter que preencher APP_URL
# à mão e reimplantar. Um APP_URL definido explicitamente continua vencendo.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
    echo "==> APP_URL herdado do Render: ${APP_URL}"
elif [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ]; then
    export APP_URL="https://${RENDER_EXTERNAL_HOSTNAME}"
    echo "==> APP_URL montado a partir do hostname do Render: ${APP_URL}"
fi

echo "==> Gerando a configuração do nginx na porta ${PORT}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

echo "==> Descobrindo os pacotes do Laravel"
php artisan package:discover --ansi

echo "==> Rodando as migrations"
php artisan migrate --force

echo "==> Criando o usuário professor, se ainda não existir"
php artisan db:seed --class=ProfessorSeeder --force

echo "==> Cacheando configuração, rotas e views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Subindo nginx e php-fpm"
exec supervisord -c /etc/supervisord.conf
