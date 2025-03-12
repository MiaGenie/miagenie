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
    php artisan package:discover -n --ansi &&\
    php artisan mixpost:publish-assets --force=true -n --ansi &&\
    php artisan mixpost-enterprise:publish-assets --force=true -n --ansi &&\
    php artisan event:clear -n --ansi &&\
    php artisan storage:link --force -n --ansi  &&\
    chown www-data:www-data /var/www/html -R

USER www-data