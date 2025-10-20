FROM composer@sha256:adca13b22c7b77f7fc606353453be12ba93e0917c332b8b0fa5efe3a85bf27ab as composer-tool

FROM node:25@sha256:92a8086f817cfaf95bfca41d2f72382ae64e87389fe865f9b2f06335edb34073 as build-client

WORKDIR /srv/app
COPY . /srv/app/

RUN npm clean-install && npm run build

FROM dunglas/frankenphp@sha256:d656fa836a1f1e4b75a7838d817276e7c45b20c6714f6af336a6c7eb1638531b

RUN apt update && apt install -y ffmpeg unzip
RUN install-php-extensions zip opcache intl

# Enable PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime

ENV APP_ENV=prod

# Assume FrankenPHP is deployed behind a reverse proxy which handles TLS termination
ENV SERVER_NAME=:8000

ARG USER=www-data
ARG GROUP=www-data

WORKDIR /srv/app
RUN mkdir /srv/app/var && chown -R ${USER}:${GROUP} /srv/app /config/caddy /data/caddy
RUN mkdir -p /srv/app/config/jwt && chown -R ${USER}:${GROUP} /srv/app/config/jwt

USER ${USER}

COPY . /srv/app/

COPY --from=composer-tool /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-scripts
RUN php bin/console cache:warmup

COPY --from=build-client /srv/app/public/build /srv/app/public/build
