# =====================================================
# 🚀 Bawh on the internet (Render.com)
# 3 steps: build the design (Node) → get the PHP packages (Composer) → run the website (PHP + Apache)
# =====================================================

# 1) 🎨 Build the CSS and JavaScript with Vite
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

# 2) 📦 Install the PHP packages (without the developer-only ones)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# 3) 🌐 The website: PHP 8.4 + Apache
FROM php:8.4-apache

# Apache serves the "public" folder, and Laravel's .htaccess can make the nice links work
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache database

CMD ["bash", "docker/start.sh"]
