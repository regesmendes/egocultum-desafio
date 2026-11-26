FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
        libonig-dev \
        libxml2-dev \
        libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite mbstring dom xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
