FROM serversideup/php:8.2-fpm-nginx

USER root

ADD ./ /var/www/html

WORKDIR /var/www/html

RUN apt update &&\
    apt install -y ffmpeg git &&\
    apt clean &&\
    install-php-extensions bcmath gd intl

RUN COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"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\"}}" \
    composer install --no-scripts --no-dev --ansi --no-interaction &&\
    composer clear-cache &&\
    mkdir -p /var/www/html/public/vendor/genie-pro &&\
    cp -r /var/www/html/vendor/inovector/mixpost-pro-team/resources/dist/vendor/genie-pro \
    /var/www/html/public/vendor &&\
    mkdir -p /var/www/html/public/vendor/genie-enterprise &&\
    cp -r /var/www/html/vendor/inovector/mixpost-enterprise/resources/dist/vendor/genie-enterprise \
    /var/www/html/public/vendor &&\
    chown www-data:www-data /var/www/html -R

USER www-data
