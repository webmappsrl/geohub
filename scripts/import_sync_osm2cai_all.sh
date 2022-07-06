#!/bin/bash

echo "import Friuli Venezia Giulia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/a/3,4" OSM2CAI
echo "sync Friuli Venezia Giulia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/a/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Veneto ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/b/3,4" OSM2CAI
echo "sync Veneto ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/b/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Trentino Alto Adige ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/c/3,4" OSM2CAI
echo "sync Trentino Alto Adige ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/c/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Lombardia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/d/3,4" OSM2CAI
echo "sync Lombardia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/d/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Piemonte ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/e/3,4" OSM2CAI
echo "sync Piemonte ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/e/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Val d'Aosta ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/f/3,4" OSM2CAI
echo "sync Val d'Aosta ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/f/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Liguria ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/g/3,4" OSM2CAI
echo "sync Liguria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/g/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Emilia Romagna ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/h/3,4" OSM2CAI
echo "sync Emilia Romagna ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/h/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Toscana ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/l/3,4" OSM2CAI
echo "sync Toscana ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/l/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Marche ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/m/3,4" OSM2CAI
echo "sync Marche ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/m/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Umbria ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/n/3,4" OSM2CAI
echo "sync Umbria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/n/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Lazio ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/o/3,4" OSM2CAI
echo "sync Lazio ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/o/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Abruzzo ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/p/3,4" OSM2CAI
echo "sync Abruzzo ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/p/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Molise ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/q/3,4" OSM2CAI
echo "sync Molise ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/q/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Campania ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/s/3,4" OSM2CAI
echo "sync Campania ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/s/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Puglia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/r/3,4" OSM2CAI
echo "sync Puglia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/r/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Basilicata ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/t/3,4" OSM2CAI
echo "sync Basilicata ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/t/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Calabria ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/u/3,4" OSM2CAI
echo "sync Calabria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/u/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Sicilia ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/v/3,4" OSM2CAI
echo "sync Sicilia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/v/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "import Sardegna ..."
php artisan geohub:out_source_importer track "https://osm2cai.cai.it/api/v1/hiking-routes/region/z/3,4" OSM2CAI
echo "sync Sardegna ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.cai.it/api/v1/hiking-routes/region/z/3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"

echo "finished OSM2CAI all regones"