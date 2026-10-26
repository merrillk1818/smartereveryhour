FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    nodejs \
    npm \
    zip \
    unzip

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    xml

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json ./
RUN npm install --ignore-scripts

COPY . .

RUN npm run build && rm -rf node_modules

RUN composer run-script post-autoload-dump 2>/dev/null || true
RUN chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

EXPOSE 9000
CMD ["php-fpm"]
