FROM php:7.4-cli-alpine

RUN docker-php-ext-install pcntl
RUN apk add composer

CMD ["/app/leaksdb"]
