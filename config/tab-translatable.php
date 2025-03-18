<?php

return [

    /*
     * The source of supported locales on the application
     * Available selection "array", "database". Default array
     */
    'source' => 'array',

    /*
     * If you choose array selection, you should add all supported translation on it as "code"
     */
    'locales' => [
        'it',
        'en',
        'fr',
        'de',
        'es',
        'nl',
        'sq',
    ],

    /*
     * If you choose database selection, you should choose the model responsible for retrieving supported translations
     * And choose the 'code_field' for example "en", "fr", "es"...
     */
    'database' => [
        'model' => 'App\\Language',
        'code_field' => 'lang',
    ],

];
