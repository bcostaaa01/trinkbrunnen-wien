# Production image: PHP 8.4 + Apache, cache stored in SQLite.
# Local development uses DDEV with MariaDB instead (see .ddev/).
FROM php:8.4-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    DB_DRIVER=sqlite \
    DB_PATH=/var/www/html/storage/fountains.sqlite \
    PORT=8080

# Serve from public/ and listen on $PORT (Render injects its own value).
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!Listen 80!Listen ${PORT}!' /etc/apache2/ports.conf \
    && sed -ri 's!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!' /etc/apache2/sites-available/000-default.conf \
    && echo 'date.timezone = Europe/Vienna' > /usr/local/etc/php/conf.d/timezone.ini \
    && echo 'expose_php = Off' > /usr/local/etc/php/conf.d/security.ini

COPY . /var/www/html

RUN mkdir -p /var/www/html/storage && chown -R www-data:www-data /var/www/html/storage

EXPOSE 8080
