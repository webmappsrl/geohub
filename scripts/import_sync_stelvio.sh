#!/bin/bash

echo "import poi ..."
php artisan geohub:out_source_importer poi https://stelvio.wp.webmapp.it wp

echo "import track ..."
php artisan geohub:out_source_importer track https://stelvio.wp.webmapp.it wp

echo "sync media ..."
php artisan geohub:sync-ec-from-out-source media stelvio@webmapp.it --endpoint="https://stelvio.wp.webmapp.it" --provider="OutSourceImporterFeatureWP" --name_format="{name}"

echo "sync poi ..."
php artisan geohub:sync-ec-from-out-source poi stelvio@webmapp.it --endpoint="https://stelvio.wp.webmapp.it" --provider=OutSourceImporterFeatureWP --poi_type=poi --name_format="{name}"

echo "sync track ..."
php artisan geohub:sync-ec-from-out-source track stelvio@webmapp.it --endpoint="https://stelvio.wp.webmapp.it" --provider="OutSourceImporterFeatureWP" --activity=hiking --name_format="{name}"

echo "create script hoqu poi ..."
php artisan geohub:generate_hoqu_script --osf_endpoint=https://stelvio.wp.webmapp.it stelvio-wp-webmapp-it-poi

echo "create script hoqu track ..."
php artisan geohub:generate_hoqu_script --osf_endpoint=https://stelvio.wp.webmapp.it stelvio-wp-webmapp-it-tappe

echo "create script hoqu track mbtiles ..."
php artisan geohub:generate_hoqu_script --osf_endpoint=https://stelvio.wp.webmapp.it --mbtiles stelvio-wp-webmapp-it-tappe_mbtiles

echo "Execute script hoqu poi ..."
bash storage/app/hoqu_scripts/stelvio-wp-webmapp-it-poi.sh
echo "Execute script hoqu track ..."
bash storage/app/hoqu_scripts/stelvio-wp-webmapp-it-tappe.sh
echo "Execute script hoqu track mbtiles ..."
bash storage/app/hoqu_scripts/stelvio-wp-webmapp-it-tappe_mbtiles.sh