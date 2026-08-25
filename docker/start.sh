#!/bin/sh
set -e

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
