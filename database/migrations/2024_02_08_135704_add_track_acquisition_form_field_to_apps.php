<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTrackAcquisitionFormFieldToApps extends Migration
{

    public $default_json =
    '[
        {
            "id" : "track",
            "label" : 
            {
                "it" : "Traccia",
                "en" : "Track"
            },
            "fields" :
            [
                {
                    "name" : "title",
                    "type" : "text",
                    "placeholder": {
                        "it" : "Inserisci un titolo",
                        "en" : "Add a title"
                    },
                    "required" : true,
                    "label" : 
                    {
                        "it" : "Titolo traccia",
                        "en" : "Track title"
                    }
                },
                {
                    "name" : "activity",
                    "type" : "select",
                    "required" : true,
                    "label" : 
                    {
                        "it" : "Tipo di attività",
                        "en" : "Actiivity type"
                    },
                    "values" : [
                        {
                            "value" : "skitouring",
                            "label" :
                            {
                                "it" : "Scialpinismo",
                                "en" : "Skitouring"
                            }
                        },
                        {
                            "value" : "walking",
                            "label" :
                            {
                                "it" : "Passeggiata",
                                "en" : "Walking"
                            }
                        },
                        {
                            "value" : "running",
                            "label" :
                            {
                                "it" : "Corsa",
                                "en" : "Running"
                            }
                        },
                        {
                            "value" : "cycling",
                            "label" :
                            {
                                "it" : "Bicicletta",
                                "en" : "Cycling"
                            }
                        },
                        {
                            "value" : "hiking",
                            "label" :
                            {
                                "it" : "Escursionismo",
                                "en" : "Hiking"
                            }
                        }
                    ]
                },
                {
                    "name" : "description",
                    "type" : "textarea",
                    "placeholder": {
                        "it" : "Se vuoi puoi aggiungere una descrizione",
                        "en" : "You can add a description if you want"
                    },
                    "required" : false,
                    "label" : 
                    {
                        "it" : "Descrizione",
                        "en" : "Description"
                    }
                }
            ] 
        }
    ]';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::table('apps', function (Blueprint $table) {
            $table->text('track_acquisition_form')->default($this->default_json);
        });
        DB::statement("UPDATE apps SET track_acquisition_form = '$this->default_json';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            Schema::table('apps', function (Blueprint $table) {
                $table->dropColumn('track_acquisition_form');
            });
        });
    }
}
