FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Conteneur de développement : PHPStan et PHPUnit ne doivent pas buter sur les
# 128 Mo par défaut de l'image.
RUN printf 'memory_limit=-1\n' > /usr/local/etc/php/conf.d/zz-memory-limit.ini

WORKDIR /app
