FROM php:8.2-apache

# Enable Apache rewrite + proxy headers
RUN a2enmod rewrite headers

# System deps + PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# PHP limits
RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# HARD SET APACHE DOCUMENT ROOT
RUN printf '%s\n' \
"<VirtualHost *:80>" \
"    ServerAdmin webmaster@localhost" \
"    DocumentRoot /var/www/html/public" \
"" \
"    <Directory /var/www/html/public>" \
"        AllowOverride All" \
"        Require all granted" \
"    </Directory>" \
"" \
"    ErrorLog \${APACHE_LOG_DIR}/error.log" \
"    CustomLog \${APACHE_LOG_DIR}/access.log combined" \
"</VirtualHost>" \
> /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]

