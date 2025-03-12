FROM serversideup/php:8.2-fpm-nginx

USER root

ADD ./ /var/www/html

WORKDIR /var/www/html

RUN apt update &&\
    apt install -y ffmpeg &&\
    apt clean &&\
    install-php-extensions bcmath gd intl

RUN --mount=type=secret,id=composer-auth \
    COMPOSER_AUTH=$(cat /run/secrets/composer-auth) \
    composer install --no-cache --no-scripts --no-dev --ansi --no-interaction &&\
    mkdir -p /var/www/html/public/vendor/genie-pro &&\
    cp -r /var/www/html/vendor/inovector/mixpost-pro-team/resources/dist/vendor/genie-pro \
    /var/www/html/public/vendor/genie-pro &&\
    mkdir -p /var/www/html/public/vendor/genie-enterprise &&\
    cp -r /var/www/html/vendor/inovector/mixpost-enterprise/resources/dist/vendor/genie-enterprise \
    /var/www/html/public/vendor/genie-enterprise &&\
    chown www-data:www-data /var/www/html -R

USER www-data