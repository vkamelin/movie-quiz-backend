# базовый PHP-FPM 8.3
FROM php:8.3-fpm

# system deps, extensions, composer, supervisor, tools
RUN apt-get update \
 && apt-get install -y \
      libzip-dev libonig-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
      zip unzip git curl supervisor mc htop \
 && docker-php-ext-configure zip \
 && docker-php-ext-install \
      pdo_mysql mysqli zip exif pcntl bcmath gd intl \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && curl -sS https://getcomposer.org/installer \
      | php -- --install-dir=/usr/local/bin --filename=composer \
 && mkdir -p /var/log/supervisor \
 && rm -rf /var/lib/apt/lists/*

# copy Supervisor config and workers
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/conf.d/       /etc/supervisor/conf.d/

# copy entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN mkdir -p /var/www/html/runtime/logs /var/www/html/storage/logs /usr/local/etc/php/conf.d && \
    chown -R www-data:www-data /var/www/html/runtime/logs /var/www/html/storage/logs

# convert line endings
RUN apt-get update && apt-get install -y dos2unix \
 && dos2unix /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# copy application sources
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/runtime/logs /var/www/html/storage/logs

# PHP upload limits (100 MB)
COPY docker/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# install PHP dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader

# entrypoint starts services
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
