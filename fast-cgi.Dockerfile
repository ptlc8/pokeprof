ARG PHP_VERSION=8
FROM php:${PHP_VERSION}-fpm-alpine

# Use the default production configuration
ARG PHP_ENV=production # can be "development"
RUN mv "$PHP_INI_DIR/php.ini-"${PHP_ENV} "$PHP_INI_DIR/php.ini"

# Install the mysqli extension
RUN docker-php-ext-install mysqli

# Install the GD extension
RUN apk add --no-cache libpng-dev libjpeg-turbo-dev libwebp-dev && \
    docker-php-ext-configure gd --with-jpeg --with-webp && \
    docker-php-ext-install gd

# Copy the source files
COPY src /var/www/html
WORKDIR /var/www/html

# Start the server
CMD ["php-fpm"]
