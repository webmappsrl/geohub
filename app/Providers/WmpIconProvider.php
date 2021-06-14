<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Bernhardh\NovaIconSelect\IconProvider;

class WmpIconProvider extends IconProvider
{


    public function __construct()
    {
        /**$json = file_get_contents('css/icons/webmapp-icons/selection.json');
         * $content = json_decode($json, true);
         * $options = array();
         * foreach ($content as $icons) {
         * foreach ($icons as $icon) {
         * $options = array_push(
         * $options,
         * [
         * 'label' => 'Add User',
         * 'value' => 'webmapp-icon-' . $icon->properties->name,
         * 'search' => ['add user']
         * ]);
         * }
         * }**/

        $this->setOptions([
            [
                'label' => 'Add User',
                'value' => 'webmapp-icon-add-user',
                'search' => ['add user']
            ],
            [
                'label' => 'Baby Backpack',
                'value' => 'webmapp-icon-baby-backpack',
                'search' => ['baby backpack']
            ],
            [
                'label' => 'Baby Carriage',
                'value' => 'webmapp-icon-baby-carriage',
                'search' => ['baby carriage']
            ],
            [
                'label' => 'Basket',
                'value' => 'webmapp-icon-basket',
                'search' => ['bell']
            ],
            [
                'label' => 'Bell',
                'value' => 'webmapp-icon-bell',
                'search' => ['bell']
            ],
            [
                'label' => 'Bike',
                'value' => 'webmapp-icon-bike',
                'search' => ['bike']
            ],
            [
                'label' => 'Camera',
                'value' => 'webmapp-icon-camera',
                'search' => ['camera']
            ],
            [
                'label' => 'Directions',
                'value' => 'webmapp-icon-directions',
                'search' => ['directions']
            ],
            [
                'label' => 'Edit',
                'value' => 'webmapp-icon-edit',
                'search' => ['edit']
            ],
            [
                'label' => 'Filters',
                'value' => 'webmapp-icon-filters',
                'search' => ['filters']
            ],
            [
                'label' => 'Groups',
                'value' => 'webmapp-icon-groups',
                'search' => ['groups']
            ],
            [
                'label' => 'Hearth',
                'value' => 'webmapp-icon-hearth',
                'search' => ['herth']
            ],
            [
                'label' => 'Home',
                'value' => 'webmapp-icon-home',
                'search' => ['home']
            ],
            [
                'label' => 'Horse',
                'value' => 'webmapp-icon-horse',
                'search' => ['horse']
            ],
            [
                'label' => 'Info',
                'value' => 'webmapp-icon-info',
                'search' => ['info']
            ],
            [
                'label' => 'Kebab',
                'value' => 'webmapp-icon-kebab',
                'search' => ['kebab']
            ],
            [
                'label' => 'Levels',
                'value' => 'webmapp-icon-levels',
                'search' => ['levels']
            ],
            [
                'label' => 'Map',
                'value' => 'webmapp-icon-map',
                'search' => ['map']
            ],
            [
                'label' => 'Nav',
                'value' => 'webmapp-icon-nav',
                'search' => ['nav']
            ],
            [
                'label' => 'Pin',
                'value' => 'webmapp-icon-pin',
                'search' => ['pin']
            ],
            [
                'label' => 'Search',
                'value' => 'webmapp-icon-search',
                'search' => ['search']
            ],
            [
                'label' => 'Setting',
                'value' => 'webmapp-icon-setting',
                'search' => ['setting']
            ],
            [
                'label' => 'Share',
                'value' => 'webmapp-icon-share',
                'search' => ['share']
            ],
            [
                'label' => 'Ski',
                'value' => 'webmapp-icon-ski',
                'search' => ['ski']
            ],
            [
                'label' => 'Star',
                'value' => 'webmapp-icon-star',
                'search' => ['star']
            ],
            [
                'label' => 'Trekking',
                'value' => 'webmapp-icon-trekking',
                'search' => ['trekking']
            ],
            [
                'label' => 'User',
                'value' => 'webmapp-icon-user',
                'search' => ['user']
            ],
            [
                'label' => 'Arrow Left',
                'value' => 'webmapp-icon-arrow-left-outline',
                'search' => ['arrow left']
            ],
            [
                'label' => 'Arrow Right',
                'value' => 'webmapp-icon-arrow-right-outline',
                'search' => ['arrow right']
            ],
            [
                'label' => 'Check',
                'value' => 'webmapp-icon-check-outline',
                'search' => ['check']
            ],
            [
                'label' => 'Chevron Down',
                'value' => 'webmapp-icon-chevron-down-outline',
                'search' => ['chevron']
            ],
            [
                'label' => 'Chevron Left',
                'value' => 'webmapp-icon-chevron-left-outline',
                'search' => ['chevron']
            ],
            [
                'label' => 'Chevron Right',
                'value' => 'webmapp-icon-chevron-right-outline',
                'search' => ['chevron']
            ],
            [
                'label' => 'Chevron Up',
                'value' => 'webmapp-icon-chevron-up-outline',
                'search' => ['chevron']
            ],
            [
                'label' => 'Close',
                'value' => 'webmapp-icon-close-outline',
                'search' => ['close']
            ],


        ]);
    }
}
