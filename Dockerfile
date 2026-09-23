ARG PHP_VERSION=8.4
ARG NODE_VERSION=22
ARG PNPM_VERSION=12.5.1

FROM node:${NODE_VERSION}-bookworm-slim AS node

FROM serversideup/php:${PHP_VERSION}-frankenphp-bookworm AS build

ARG PNPM_VERSION

USER root

COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules/corepack /usr/local/lib/node_modules/corepack

RUN ln -s /usr/local/lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack
RUN corepack enable pnpm
RUN corepack install --global pnpm@${PNPM_VERSION}

USER www-data
WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock package.json pnpm-lock.yaml ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

RUN pnpm install --frozen-lockfile

COPY --chown=www-data:www-data . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist

ARG VITE_APP_NAME=Laravel
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

RUN pnpm run build
RUN rm -rf node_modules

FROM serversideup/php:${PHP_VERSION}-frankenphp-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    SSL_MODE=off \
    HEALTHCHECK_PATH=/up \
    PHP_OPCACHE_ENABLE=1 \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info

USER www-data
WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

EXPOSE 8080
