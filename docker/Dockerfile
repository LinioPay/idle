FROM composer@sha256:9f7d376e4b3a0cd6c739838e7512a4499af1b6bc1d942d0412ab8102cf25fd82

RUN apk --update --no-cache add autoconf g++ make \
    && pecl install -f xdebug-2.9.6 \
    && docker-php-ext-enable xdebug \
    && apk del --purge autoconf g++ make

EXPOSE 9000
