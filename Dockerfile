FROM php:8.2-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# HARD SET APACHE ROOT (NO ENV VARS)
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's|<Directory /var/www/>|<Directory /var/www/html/public>|g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
CMD ["apache2-foreground"]

