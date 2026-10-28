#!/usr/bin/env bash
# 🚀 Runs every time the website starts on Render
set -e
cd /var/www/html

# Render tells us which port to use (10000 by default)
PORT="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# The SQLite database file and Laravel's folders
mkdir -p database storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite

php artisan package:discover --ansi
php artisan migrate --force
# 🌱 demo content if the database is empty + 🛡️ the admin account from ADMIN_EMAIL / ADMIN_PASSWORD
php artisan bawh:setup
php artisan config:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache database
exec apache2-foreground
