FROM php:7.4-alpine

RUN adduser -D manychois
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json /home/manychois/composer.json
COPY composer.lock /home/manychois/composer.lock
COPY phpunit.xml /home/manychois/phpunit.xml

USER manychois
WORKDIR /home/manychois
RUN composer install

COPY src /home/manychois/src
COPY tests /home/manychois/tests

RUN composer test
