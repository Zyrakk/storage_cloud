FROM php:8.1-fpm

RUN apt-get update \
 && apt-get install -y libpq-dev git unzip \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia y descarga dependencias
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copia el resto de la app
COPY . .

# Ajusta permisos
RUN chown -R www-data:www-data /var/www/html
