#!/bin/bash

source .env
containerName="php_$APP_NAME"
docker exec -it -u 0 $containerName pecl install xdebug-3.1.6

docker cp ./docker/configs/phpfpm/xdebug.ini $containerName:/usr/local/etc/php/conf.d/.
