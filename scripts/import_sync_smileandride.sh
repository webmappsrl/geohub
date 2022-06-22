#!/bin/bash

echo "import poi ..."
php artisan geohub:out_source_importer poi http://smileandride.be.webmapp.it wp

echo "import track ..."
php artisan geohub:out_source_importer track http://smileandride.be.webmapp.it wp

echo "sync media ..."
php artisan geohub:sync-ec-from-out-source media info@smileandride.com --endpoint="http://smileandride.be.webmapp.it" --provider="OutSourceImporterFeatureWP" --name_format="{name}"

echo "sync poi ..."
php artisan geohub:sync-ec-from-out-source poi info@smileandride.com --endpoint="http://smileandride.be.webmapp.it" --provider=OutSourceImporterFeatureWP --poi_type=poi --name_format="{name}"

echo "sync track ..."
php artisan geohub:sync-ec-from-out-source track info@smileandride.com --endpoint="http://smileandride.be.webmapp.it" --provider="OutSourceImporterFeatureWP" --activity=cycling --name_format="{name}"

echo "finished smile and ride"