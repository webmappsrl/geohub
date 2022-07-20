#!/bin/bash

echo "import poi ..."
php artisan geohub:out_source_importer poi "sicai_pt_accoglienza_unofficial" sicai

echo "import track ..."
php artisan geohub:out_source_importer track "sicai_si_tappe" sicai

echo "sync media poi ..."
php artisan geohub:sync-ec-from-out-source media 1 --endpoint="sicai_pt_accoglienza_unofficial" --provider="OutSourceImporterFeatureSICAI" --name_format="{name}"

echo "sync media track ..."
php artisan geohub:sync-ec-from-out-source media 1 --endpoint="sicai_si_tappe" --provider="OutSourceImporterFeatureSICAI" --name_format="{name}"
echo "sync poi ..."
php artisan geohub:sync-ec-from-out-source poi sicai@webmapp.it --endpoint="sicai_pt_accoglienza_unofficial" --provider="OutSourceImporterFeatureSICAI" --name_format="{name}" --poi_type=alpine-hut

echo "sync track ..."
php artisan geohub:sync-ec-from-out-source track sicai@webmapp.it --endpoint="sicai_si_tappe" --provider="OutSourceImporterFeatureSICAI" --name_format="{name}" --activity=hiking

echo "create script hoqu poi ..."
php artisan geohub:generate_hoqu_script --osf_endpoint=sicai_pt_accoglienza_unofficial sicai_pt_accoglienza_unofficial

echo "create script hoqu track ..."
php artisan geohub:generate_hoqu_script --osf_endpoint=sicai_si_tappe sicai_si_tappe

echo "create script hoqu track mbtiles ..."
# php artisan geohub:generate_hoqu_script --osf_endpoint=sicai_si_tappe --mbtiles sicai_si_tappe_mbtiles

echo "Execute script hoqu poi ..."
bash storage/app/hoqu_scripts/sicai_pt_accoglienza_unofficial.sh
echo "Execute script hoqu track ..."
bash storage/app/hoqu_scripts/sicai_si_tappe.sh
echo "Execute script hoqu track mbtiles ..."
# bash storage/app/hoqu_scripts/sicai_si_tappe_mbtiles.sh