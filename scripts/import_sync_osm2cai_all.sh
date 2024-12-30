#!/bin/bash

echo "import Friuli Venezia Giulia ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/a/1,2,3,4" OSM2CAI
echo "sync Friuli Venezia Giulia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/a/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Friuli Venezia Giulia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/a/1,2,3,4" osm2cai_a
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/a/1,2,3,4" --mbtiles osm2cai_a_mbtiles

echo "import Veneto ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/b/1,2,3,4" OSM2CAI
echo "sync Veneto ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/b/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Veneto ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/b/1,2,3,4" osm2cai_b
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/b/1,2,3,4" --mbtiles osm2cai_b_mbtiles

echo "import Trentino Alto Adige ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/c/1,2,3,4" OSM2CAI
echo "sync Trentino Alto Adige ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/c/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Trentino Alto Adige ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/c/1,2,3,4" osm2cai_c
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/c/1,2,3,4" --mbtiles osm2cai_c_mbtiles

echo "import Lombardia ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/d/1,2,3,4" OSM2CAI
echo "sync Lombardia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/d/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Lombardia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/d/1,2,3,4" osm2cai_d
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/d/1,2,3,4" --mbtiles osm2cai_d_mbtiles

echo "import Piemonte ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/e/1,2,3,4" OSM2CAI
echo "sync Piemonte ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/e/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Piemonte ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/e/1,2,3,4" osm2cai_e
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/e/1,2,3,4" --mbtiles osm2cai_e_mbtiles

echo "import Val d'Aosta ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/f/1,2,3,4" OSM2CAI
echo "sync Val d'Aosta ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/f/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Val d'Aosta ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/f/1,2,3,4" osm2cai_f
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/f/1,2,3,4" --mbtiles osm2cai_f_mbtiles

echo "import Liguria ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/g/1,2,3,4" OSM2CAI
echo "sync Liguria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/g/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Liguria ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/g/1,2,3,4" osm2cai_g
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/g/1,2,3,4" --mbtiles osm2cai_g_mbtiles

echo "import Emilia Romagna ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/h/1,2,3,4" OSM2CAI
echo "sync Emilia Romagna ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/h/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Emilia Romagna ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/h/1,2,3,4" osm2cai_h
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/h/1,2,3,4" --mbtiles osm2cai_h_mbtiles

echo "import Toscana ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/l/1,2,3,4" OSM2CAI
echo "sync Toscana ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/l/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Toscana ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/l/1,2,3,4" osm2cai_l
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/l/1,2,3,4" --mbtiles osm2cai_l_mbtiles

echo "import Marche ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/m/1,2,3,4" OSM2CAI
echo "sync Marche ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/m/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Marche ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/m/1,2,3,4" osm2cai_m
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/m/1,2,3,4" --mbtiles osm2cai_m_mbtiles

echo "import Umbria ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/n/1,2,3,4" OSM2CAI
echo "sync Umbria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/n/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Umbria ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/n/1,2,3,4" osm2cai_n
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/n/1,2,3,4" --mbtiles osm2cai_n_mbtiles

echo "import Lazio ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/o/1,2,3,4" OSM2CAI
echo "sync Lazio ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/o/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Lazio ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/o/1,2,3,4" osm2cai_o
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/o/1,2,3,4" --mbtiles osm2cai_o_mbtiles

echo "import Abruzzo ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/p/1,2,3,4" OSM2CAI
echo "sync Abruzzo ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/p/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Abruzzo ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/p/1,2,3,4" osm2cai_p
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/p/1,2,3,4" --mbtiles osm2cai_p_mbtiles

echo "import Molise ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/q/1,2,3,4" OSM2CAI
echo "sync Molise ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/q/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Molise ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/q/1,2,3,4" osm2cai_q
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/q/1,2,3,4" --mbtiles osm2cai_q_mbtiles

echo "import Campania ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/s/1,2,3,4" OSM2CAI
echo "sync Campania ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/s/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Campania ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/s/1,2,3,4" osm2cai_s
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/s/1,2,3,4" --mbtiles osm2cai_s_mbtiles

echo "import Puglia ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/r/1,2,3,4" OSM2CAI
echo "sync Puglia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/r/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Puglia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/r/1,2,3,4" osm2cai_r
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/r/1,2,3,4" --mbtiles osm2cai_r_mbtiles

echo "import Basilicata ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/t/1,2,3,4" OSM2CAI
echo "sync Basilicata ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/t/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Basilicata ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/t/1,2,3,4" osm2cai_t
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/t/1,2,3,4" --mbtiles osm2cai_t_mbtiles

echo "import Calabria ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/u/1,2,3,4" OSM2CAI
echo "sync Calabria ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/u/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Calabria ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/u/1,2,3,4" osm2cai_u
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/u/1,2,3,4" --mbtiles osm2cai_u_mbtiles

echo "import Sicilia ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/v/1,2,3,4" OSM2CAI
echo "sync Sicilia ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/v/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Sicilia ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/v/1,2,3,4" osm2cai_v
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/v/1,2,3,4" --mbtiles osm2cai_v_mbtiles

echo "import Sardegna ..."
php artisan geohub:out_source_importer track "https://osm2cai.maphub.it/api/v1/hiking-routes/region/z/1,2,3,4" OSM2CAI
echo "sync Sardegna ..."
php artisan geohub:sync-ec-from-out-source track osm2cai@webmapp.it --endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/z/1,2,3,4" --provider="OutSourceImporterFeatureOSM2CAI"  --activity=hiking --name_format="{ref} - {from} - {to}"
echo "generate script Sardegna ..."
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/z/1,2,3,4" osm2cai_z
php artisan geohub:generate_hoqu_script --osf_endpoint="https://osm2cai.maphub.it/api/v1/hiking-routes/region/z/1,2,3,4" --mbtiles osm2cai_z_mbtiles

echo "finished OSM2CAI all regones"