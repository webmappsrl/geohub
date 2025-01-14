#! /bin/bash
php artisan geohub:out_source_importer_updated_at poi osmpoi:caiparma_rifugi osmpoi
php artisan geohub:out_source_importer_updated_at poi osmpoi:caiparma_bivacchi osmpoi
php artisan geohub:out_source_importer_updated_at poi osmpoi:caiparma_punti_acqua osmpoi
php artisan geohub:out_source_importer_updated_at poi osmpoi:caiparma_luoghi_di_posa osmpoi

php artisan geohub:sync-ec-from-out-source-updated-at media caiparma@webmapp.it --endpoint="osmpoi:caiparma_rifugi" --provider="OutSourceImporterFeatureOSMPoi" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at media caiparma@webmapp.it --endpoint="osmpoi:caiparma_bivacchi" --provider="OutSourceImporterFeatureOSMPoi" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at media caiparma@webmapp.it --endpoint="osmpoi:caiparma_punti_acqua" --provider="OutSourceImporterFeatureOSMPoi" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at media caiparma@webmapp.it --endpoint="osmpoi:caiparma_luoghi_di_posa" --provider="OutSourceImporterFeatureOSMPoi" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at poi caiparma@webmapp.it --endpoint="osmpoi:caiparma_rifugi" --provider=OutSourceImporterFeatureOSMPoi --poi_type=alpine-hut --theme="caiparma-pois" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at poi caiparma@webmapp.it --endpoint="osmpoi:caiparma_bivacchi" --provider=OutSourceImporterFeatureOSMPoi --poi_type=bivacco --theme="caiparma-pois" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at poi caiparma@webmapp.it --endpoint="osmpoi:caiparma_punti_acqua" --provider=OutSourceImporterFeatureOSMPoi --poi_type=fountain --theme="caiparma-pois" --name_format="{name}"

php artisan geohub:sync-ec-from-out-source-updated-at poi caiparma@webmapp.it --endpoint="osmpoi:caiparma_luoghi_di_posa" --provider=OutSourceImporterFeatureOSMPoi --poi_type=information-guidepost --theme="caiparma-pois" --name_format="{ref} - {name}"
php artisan geohub:sync-ec-tags-from-osf poi caiparma@webmapp.it --tag=osmid
