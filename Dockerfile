# Base PHP-FPM Alpine
FROM php:8.3-fpm-alpine

# Instalar dependencias y extensiones
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip opcache bcmath \
    && apk del --no-cache build-base

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuración PHP-FPM y OPcache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> $PHP_INI_DIR/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> $PHP_INI_DIR/conf.d/opcache.ini && \
    echo "realpath_cache_size=4096K" >> $PHP_INI_DIR/conf.d/php.ini && \
    echo "realpath_cache_ttl=600" >> $PHP_INI_DIR/conf.d/php.ini

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar composer y dependencias
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --no-scripts

# Copiar código fuente
COPY . .

# Preparar Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuración de Nginx y Supervisor
COPY nginx.conf /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf


EXPOSE 80

# Iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
