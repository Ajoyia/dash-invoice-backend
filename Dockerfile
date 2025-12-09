FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    mysql-client \
    nodejs \
    npm \
    autoconf \
    g++ \
    make \
    linux-headers

RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd sockets

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY rest-service/composer.json rest-service/composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist \
    --ignore-platform-req=php-64bit

COPY rest-service/package.json rest-service/package-lock.json* ./
RUN npm ci || npm install

COPY rest-service/ .

RUN composer dump-autoload --optimize --classmap-authoritative

RUN npm run build

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

FROM base AS development

RUN composer install --prefer-dist

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000

CMD ["php-fpm"]

FROM php:8.3-fpm-alpine AS production

RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    autoconf \
    g++ \
    make \
    linux-headers

RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd sockets

RUN apk del .build-deps

RUN apk add --no-cache \
    libpng \
    libzip \
    oniguruma \
    postgresql-libs \
    mysql-client

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY --from=base /var/www/html /var/www/html

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    && composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && rm -rf /var/www/html/node_modules \
    && rm /usr/local/bin/composer

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000

CMD ["php-fpm"]
