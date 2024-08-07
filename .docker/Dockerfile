FROM php:8.3-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pcntl && \
    rm /usr/local/bin/install-php-extensions

WORKDIR /app
USER www-data

COPY . /app

CMD ["php", "bin/server.php"]
