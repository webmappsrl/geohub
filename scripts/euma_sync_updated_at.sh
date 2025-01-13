#!/bin/bash
cd /root/geohub.webmapp.it

php artisan geohub:out_source_importer_updated_at poi https://database.european-mountaineers.eu/api/v1/hut/list euma

php artisan geohub:out_source_importer_updated_at poi https://database.european-mountaineers.eu/api/v1/climbingrockarea/list euma

php artisan geohub:out_source_importer_updated_at track https://database.european-mountaineers.eu/api/v1/trail/list euma

php artisan geohub:sync-ec-from-out-source-updated-at poi euma@webmapp.it --endpoint="https://database.european-mountaineers.eu/api/v1/hut/list" --provider=OutSourceImporterFeatureEUMA --name_format="{name}" --theme="euma-poi-huts"

php artisan geohub:sync-ec-from-out-source-updated-at poi euma@webmapp.it --endpoint="https://database.european-mountaineers.eu/api/v1/climbingrockarea/list" --provider=OutSourceImporterFeatureEUMA --name_format="{name}" --theme="euma-poi-crags"

php artisan geohub:sync-ec-from-out-source-updated-at track euma@webmapp.it --endpoint="https://database.european-mountaineers.eu/api/v1/trail/list" --provider=OutSourceImporterFeatureEUMA --name_format="{name}" --activity="hiking" --theme=euma-trails

php artisan geohub:index-tracks 30 --no-elastic
