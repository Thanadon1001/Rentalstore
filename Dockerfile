FROM php:8.1-cli

# ติดตั้ง dependencies และ extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# ติดตั้ง Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . /app

# รัน Composer install
RUN composer install --ignore-platform-reqs

CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]