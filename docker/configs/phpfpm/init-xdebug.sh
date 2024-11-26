#!/bin/bash

source .env
containerName="php_$APP_NAME"
docker exec -it -u 0 $containerName pecl install xdebug-3.1.6 && touch /var/log/xdebug.log && chown www-data /var/log/xdebug.log

docker cp ./docker/configs/phpfpm/xdebug.ini $containerName:/usr/local/etc/php/conf.d/.
