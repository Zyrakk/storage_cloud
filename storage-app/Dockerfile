FROM php:8.3-fpm

RUN apt-get update \
 && apt-get install -y libpq-dev git unzip libssl-dev pkg-config \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

# Instala Composer
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Instala extensión Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Copia composer.json y genera lock/install
COPY composer.json composer.lock ./
RUN composer update --no-dev --optimize-autoloader

# Copia el resto de la app
COPY . .

# Ajusta permisos
RUN chown -R www-data:www-data /var/www/html

# PHP.ini overrides para subida de archivos
RUN { \
  echo "upload_max_filesize = 1G"; \
  echo "post_max_size = 1G"; \
  echo "memory_limit = 2G"; \
} > /usr/local/etc/php/conf.d/uploads.ini

