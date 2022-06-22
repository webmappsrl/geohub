#!/bin/bash

php artisan geohub:out_source_importer poi http://smileandride.be.webmapp.it wp

php artisan geohub:out_source_importer track http://smileandride.be.webmapp.it wp

php artisan geohub:sync-ec-from-out-source media info@smileandride.com --endpoint="http://smileandride.be.webmapp.it" --provider="OutSourceImporterFeatureWP" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source poi info@smileandride.com --endpoint="http://smileandride.be.webmapp.it" --provider=OutSourceImporterFeatureWP --poi_type=eating-and-drinking --name_format="{name}"

php artisan geohub:sync-ec-from-out-source track info@smileandride.com --endpoint="http://smileandride.be.webmapp.it" --provider="OutSourceImporterFeatureWP" --activity=cycling --name_format="{name}"
