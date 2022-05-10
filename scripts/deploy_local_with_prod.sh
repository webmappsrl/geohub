#!/bin/bash
set -e

echo "Deployment started ..."

# DB clean, download, restore
if test -f "dump.sql"; 
then
    echo "File dump.sql exists: skipping download. If you want to downlad it again remove it."
else
    echo "File dump deos not exist: downloading and gunzipping."
    scp forge@116.203.186.2:/home/forge/backup/geohub.webmapp.it/dump.sql.gz ./ 
    gunzip dump.sql.gz
fi

composer install
composer dump-autoload

# Clear and cache routes
# php artisan route:clear
# php artisan route:cache

# Clear and cache config
php artisan config:cache

# Clear the old cache
php artisan clear-compiled

# TODO: Uncomment when api.favorite issue will be resolved
# php artisan optimize

php artisan db:restore

php artisan migrate

echo "Deployment finished!"