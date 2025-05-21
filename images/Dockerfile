# Use the official PHP image from Docker Hub
FROM php:8.2-cli

# Set the working directory
WORKDIR /app

# Install system dependencies for PHP extensions
RUN apt-get update \
    && apt-get install -y libzip-dev zip unzip libcurl4-openssl-dev pkg-config \
    && docker-php-ext-install curl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHPUnit globally
RUN composer global require phpunit/phpunit --prefer-dist --no-interaction --no-progress || true

# Set PATH for global composer bin
ENV PATH="/root/.composer/vendor/bin:$PATH"

# Install PHP dependencies
RUN composer install || true

# Install Xdebug for debugging
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Default command runs tests
CMD ["phpunit", "--testdox", "tests"]
