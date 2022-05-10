#!/bin/bash
set -e

echo "Deployment started ..."

# DB clean, download, restore
# rm -f dump.sql
# scp forge@116.203.186.2:/home/forge/backup/geohub/dump.sql.gz ./ 
# gunzip dump.sql.gz
php artisan db:restore
# rm -f dump.sql
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