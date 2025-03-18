#!/bin/bash
cd /root/geohub.webmapp.it

php artisan geohub:out_source_importer_updated_at poi "https://www.sardegnasentieri.it/ss/listpoi/?_format=json" sentierisardegna

php artisan geohub:out_source_importer_updated_at track "https://www.sardegnasentieri.it/ss/list-tracks/?_format=json" sentierisardegna

php artisan geohub:sync-ec-from-out-source-updated-at media sardegnasentieri@webmapp.it --endpoint="https://www.sardegnasentieri.it/ss/listpoi/?_format=json" --provider="OutSourceImporterFeatureSentieriSardegna" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at media sardegnasentieri@webmapp.it --endpoint="https://www.sardegnasentieri.it/ss/list-tracks/?_format=json" --provider="OutSourceImporterFeatureSentieriSardegna" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at poi sardegnasentieri@webmapp.it --endpoint="https://www.sardegnasentieri.it/ss/listpoi/?_format=json" --provider=OutSourceImporterFeatureSentieriSardegna --name_format="{name}" --theme="sardegnas-pois"

php artisan geohub:sync-ec-from-out-source-updated-at track sardegnasentieri@webmapp.it --endpoint="https://www.sardegnasentieri.it/ss/list-tracks/?_format=json" --provider=OutSourceImporterFeatureSentieriSardegna --name_format="{name}"

php artisan geohub:index-tracks 32 --no-elastic
