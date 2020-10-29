FROM php:7.4-zts

RUN apt update -y && \
    apt install -y libuv1-dev

RUN docker-php-ext-install pcntl
RUN pecl install parallel eio uv-0.2.4 && docker-php-ext-enable parallel eio uv
