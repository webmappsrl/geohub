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
php artisan db:restore

php artisan migrate

# Clear caches
php artisan cache:clear

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Clear the old cache
php artisan clear-compiled

composer dump-autoload
php artisan optimize

echo "Deployment finished!"