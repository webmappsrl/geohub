<?php

use App\Models\App;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     ### Overlay Layer Config  REF: https://github.com/webmappsrl/wm-app/edit/develop/docs/config/sections/map.md

This represents the configuration of each overlay layer visible in the map with additional interactive and/or visual data.

This is a [translatable](../translatable.md) object

| Ready | Key              | Type                      | Mandatory | Description                                                                                                                                                                                                                                       |
| -- | ---------------- | ------------------------- | --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
MAIN
| &#9744; | `id`             | `string`                  | `true`    | The layer id                                                                                                                                                                                                                                      |
| &#9744; | `name`           | `string`                  | `false`   | The layer name (useful when the layer is shown in the filters or as block in the homepage)                                                                                                                                                        |
| &#9744; | `description`    | `string`                  | `false`   | The layer description (shown in the homepage when the layer is shown as block)                                                                                                                                                                    |
| &#9744; | `icon`           | `string`                  | `false`   | Sets the default icon of the layer. This can be overwritten in the feature property                                                                                                                                                               |

BEHAVIOUR
| &#9744; | `noDetails`      | `boolean`                 | `false`   | When true clicking on a feature in map will open the popup but not the details page                                                                                                                                                               |
| &#9744; | `noInteraction`  | `boolean`                 | `false`   | When true clicking on a feature of this layer will be disabled                                                                                                                                                                                    |
| &#9744; | `minZoom`        | `number`                  | `false`   | The minimum visible zoom of the layer                                                                                                                                                                                                             |
| &#9744; | `maxZoom`        | `number`                  | `false`   | The maximum visible zoom of the layer                                                                                                                                                                                                             |
| &#9744; | `preventFilter`  | `boolean`                 | `false`   | This wwill hide the layer from the filters, making the layer always visible no matter what filtering/search is activated                                                                                                                          |
| &#9744; | `invertPolygons` | `boolean`                 | `false`   | Invert all the polygons inside this layer. This is really useful when there is a bounds polygon inside the layer that represent the area that needs to be highlighted. Inverting the polygon allow to make the outside area filled with any color |
| &#9744; | `alert`          | `boolean`                 | `false`   | When true points in this layer will trigger an alert (popup and sound) when navigating close to the point                                                                                                                                         |
| &#9744; | `show_label`     | `boolean`                 | `false`   | This will show labels in from and to points of the layers                                                                                                                                                                                         |
| &#9744; | `params`         | `{ [id: string]: string}` | `false`   | Defines some custom parameters to add when calling the API (headers and other custom configurations)                                                                                                                                              |

STYLE
| &#9744; | `color`          | `string`                  | `false`   | The color used to represent the features in map                                                                                                                                                                                                   |
| &#9744; | `fill_color`     | `string`                  | `false`   | This is used for polygons as the fill color (all the content area color)/. If this is not present it will use the color                                                                                                                           |
| &#9744; | `fill_opacity`   | `number`                  | `false`   | This can set the opacity of the fill color. This is a number between 0 - transparent - and 100 - fully visible                                                                                                                                    |
| &#9744; | `stroke_width`   | `number`                  | `false`   | This represent the width of the line. For polygons this refers to the border of the polygon                                                                                                                                                       |
| &#9744; | `stroke_opacity` | `number`                  | `false`   | This set the opacity of the stroke. This is a number between 0 - transparent - and 100 - fully visible                                                                                                                                            |
| &#9744; | `zindex`         | `number`                  | `false`   | Set the depth of the layer. See [z index](#z-index) for more information                                                                                                                                                                          |
| &#9744; | `line_dash`      | `Array<number>`           | `false`   | This sets the line dash of the tracks contained in the layer                                                                                                                                                                                      |

     *
     * @return void
     */
    public function up()
    {
        Schema::create('layers', function (Blueprint $table) {
            // MAIN
            $table->id();
            $table->foreignIdFor(App::class);
            $table->timestamps();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->text('icon')->nullable();

            // BEHAVIOUR
            $table->boolean('noDetails')->nullable();
            $table->boolean('noInteraction')->nullable();
            $table->integer('minZoom')->nullable();
            $table->integer('maxZoom')->nullable();
            $table->boolean('preventFilter')->nullable();
            $table->boolean('invertPolygons')->nullable();
            $table->boolean('alert')->nullable();
            $table->boolean('show_label')->nullable();
            $table->text('params')->nullable();

            // STYLE
            $table->string('color')->nullable();
            $table->string('fill_color')->nullable();
            $table->integer('fill_opacity')->nullable();
            $table->integer('stroke_width')->nullable();
            $table->integer('stroke_opacity')->nullable();
            $table->integer('zindex')->nullable();
            $table->string('line_dash')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('layers');
    }
}
