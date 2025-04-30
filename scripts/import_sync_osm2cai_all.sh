#!/bin/bash

echo "load external features for Friuli Venezia Giulia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/a/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Friuli Venezia Giulia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/a/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Friuli Venezia Giulia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/a/1,2,3,4" osm2cai_a

echo "load external features for Veneto ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/b/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Veneto ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/b/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Veneto ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/b/1,2,3,4" osm2cai_b

echo "load external features for Trentino Alto Adige ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/c/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Trentino Alto Adige ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/c/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Trentino Alto Adige ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/c/1,2,3,4" osm2cai_c

echo "load external features for Lombardia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/d/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Lombardia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/d/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Lombardia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/d/1,2,3,4" osm2cai_d

echo "load external features for Piemonte ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/e/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Piemonte ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/e/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Piemonte ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/e/1,2,3,4" osm2cai_e

echo "load external features for Val d'Aosta ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/f/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Val d'Aosta ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/f/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Val d'Aosta ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/f/1,2,3,4" osm2cai_f

echo "load external features for Liguria ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/g/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Liguria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/g/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Liguria ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/g/1,2,3,4" osm2cai_g

echo "load external features for Emilia Romagna ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/h/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Emilia Romagna ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/h/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Emilia Romagna ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/h/1,2,3,4" osm2cai_h

echo "load external features for Toscana ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/l/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Toscana ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/l/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Toscana ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/l/1,2,3,4" osm2cai_l

echo "load external features for Marche ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/m/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Marche ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/m/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Marche ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/m/1,2,3,4" osm2cai_m

echo "load external features for Umbria ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/n/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Umbria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/n/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Umbria ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/n/1,2,3,4" osm2cai_n

echo "load external features for Lazio ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/o/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Lazio ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/o/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Lazio ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/o/1,2,3,4" osm2cai_o

echo "load external features for Abruzzo ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/p/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Abruzzo ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/p/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Abruzzo ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/p/1,2,3,4" osm2cai_p

echo "load external features for Molise ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/q/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Molise ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/q/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Molise ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/q/1,2,3,4" osm2cai_q

echo "load external features for Campania ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/s/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Campania ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/s/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Campania ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/s/1,2,3,4" osm2cai_s

echo "load external features for Puglia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/r/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Puglia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/r/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Puglia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/r/1,2,3,4" osm2cai_r

echo "load external features for Basilicata ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/t/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Basilicata ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/t/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Basilicata ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/t/1,2,3,4" osm2cai_t

echo "load external features for Calabria ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/u/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Calabria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/u/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Calabria ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/u/1,2,3,4" osm2cai_u

echo "load external features for Sicilia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/v/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Sicilia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/v/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Sicilia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/v/1,2,3,4" osm2cai_v

echo "load external features for Sardegna ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/z/1,2,3,4" OSM2CAI
echo "handle entities on geohub with the loaded external features - Sardegna ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/z/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate queue jobs for all needed calculations -  Sardegna ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/z/1,2,3,4" osm2cai_z

echo "finished OSM2CAI all regioni"

echo "Cleaning up orphaned OSM2CAI features from Geohub..."
php artisan geohub:clean-osm2cai-features --force