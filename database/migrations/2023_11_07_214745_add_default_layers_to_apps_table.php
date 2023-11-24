<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultLayersToAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->string('data_label')->default('{"it":"Dati","en":"Data"}');

            $table->string('pois_data_label')->default('{"it":"Punti di interesse","en":"Points of interest"}');
            $table->boolean('pois_data_default')->default(true);
            $table->text('pois_data_icon')->default('<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.1957 10.7668L0.443114 18.4869C0.302634 18.5981 0.191196 18.7301 0.115165 18.8753C0.0391335 19.0206 0 19.1763 0 19.3335C0 19.4907 0.0391335 19.6464 0.115165 19.7917C0.191196 19.9369 0.302634 20.0689 0.443114 20.18L14.9306 31.6492C15.071 31.7604 15.2377 31.8486 15.4212 31.9088C15.6047 31.969 15.8014 32 16 32C16.1986 32 16.3953 31.969 16.5788 31.9088C16.7623 31.8486 16.929 31.7604 17.0694 31.6492L31.5569 20.1805C31.6974 20.0694 31.8088 19.9374 31.8848 19.7921C31.9609 19.6469 32 19.4912 32 19.334C32 19.1767 31.9609 19.021 31.8848 18.8758C31.8088 18.7305 31.6974 18.5985 31.5569 18.4874L21.8129 10.7742C21.4691 11.6305 21.1239 12.4857 20.7774 13.3397L28.3503 19.334L16 29.1105L3.65036 19.334L11.2335 13.3305C10.8906 12.4732 10.5447 11.6185 10.1957 10.7664V10.7668Z" fill="black"/><path d="M16.0003 0C12.7715 0 10.1254 2.81805 10.1254 6.25679C10.1257 7.543 10.5007 8.79724 11.1984 9.84558L15.2835 17.3882C15.8557 18.1866 16.2358 18.0342 16.7113 17.3458L21.2169 9.15653C21.3078 8.98085 21.3795 8.79321 21.4416 8.60181C21.7275 7.85783 21.8746 7.06135 21.8745 6.25713C21.8745 2.81771 19.2297 0 16.0003 0ZM16.0003 2.93152C17.7392 2.93152 19.1216 4.40498 19.1216 6.25713C19.1216 8.1086 17.7392 9.58137 16.0003 9.58137C14.2617 9.58137 12.8784 8.1086 12.8784 6.25679C12.8784 4.40498 14.2621 2.93152 16.0003 2.93152Z" fill="black"/></svg>')->nullable();
            
            $table->string('tracks_data_label')->default('{"it":"Percorsi","en":"Routes"}');
            $table->boolean('tracks_data_default')->default(true);
            $table->text('tracks_data_icon')->default('<svg width="32" height="25" viewBox="0 0 32 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.9306 0.346329C15.2088 0.128784 15.5847 0.00458118 15.9782 0.000120467C16.1805 -0.00213654 16.3813 0.0273419 16.5687 0.0868101C16.7562 0.146278 16.9264 0.234527 17.0694 0.346329L31.5569 11.6646C31.6974 11.7743 31.8088 11.9045 31.8848 12.0479C31.9609 12.1912 32 12.3449 32 12.5001C32 12.6552 31.9609 12.8089 31.8848 12.9522C31.8088 13.0956 31.6974 13.2258 31.5569 13.3355L17.0694 24.6538C16.929 24.7636 16.7623 24.8506 16.5788 24.91C16.3953 24.9694 16.1986 25 16 25C15.8014 25 15.6047 24.9694 15.4212 24.91C15.2377 24.8506 15.071 24.7636 14.9306 24.6538L0.443114 13.3355C0.302635 13.2258 0.191196 13.0956 0.115165 12.9522C0.0391335 12.8089 0 12.6552 0 12.5001C0 12.3449 0.0391335 12.1912 0.115165 12.0479C0.191196 11.9045 0.302635 11.7743 0.443114 11.6646L14.9306 0.346329ZM10.7445 6.95744L15.9994 2.84718L28.359 12.4989L15.9994 22.179L6.30255 14.572L3.65036 12.5001L6.88161 9.97593C6.88247 9.97626 6.88334 9.97658 6.88421 9.9769L9.48801 7.94027C9.48755 7.94023 9.48709 7.94018 9.48663 7.94014L10.7445 6.95744Z" fill="black"/><path d="M20.8218 14.7708C21.0358 14.6603 21.2811 14.5917 21.5464 14.5917C22.3489 14.5917 23 15.1733 23 15.8896C23 16.6067 22.3483 17.1875 21.5463 17.1875C20.7427 17.1875 20.0923 16.6067 20.0923 15.8896C20.0923 15.6373 20.1772 15.4033 20.3169 15.2038C19.5531 14.6995 18.7824 14.1395 18.4875 13.3884C18.0618 12.3024 19.4489 10.2695 18.0999 9.54924C16.9655 8.94325 15.18 9.48227 14.2804 8.67784C14.0715 8.78166 13.835 8.84567 13.5794 8.84567C13.4969 8.84567 13.4167 8.83574 13.3375 8.82409C13.2618 9.01005 13.205 9.20495 13.1719 9.40657C13.0888 9.90204 13.2669 10.3871 13.2157 10.8803C13.162 11.4083 12.946 11.9175 12.3372 12.0996C11.5015 12.3492 9.28463 11.8197 8.80199 12.5961C8.86838 12.7448 8.90738 12.9064 8.90738 13.0758C8.90738 13.7929 8.25637 14.3737 7.45384 14.3737C6.65072 14.3737 6 13.7929 6 13.0758C6 12.498 6.42577 12.0147 7.01005 11.8463L7.12201 11.8121L7.22647 11.8001L7.35705 11.7888H7.51374L7.69386 11.8001C7.92834 11.8348 8.14135 11.9195 8.32349 12.0417C8.9408 11.4385 10.3047 11.5996 11.2616 11.592C11.6411 11.5894 12.3452 11.6179 12.4537 11.1712C12.5851 10.6335 12.4109 10.0949 12.4348 9.55341C12.4502 9.21063 12.5379 8.87494 12.6779 8.55864C12.3441 8.32079 12.1258 7.95888 12.1258 7.54788C12.1258 6.83097 12.7763 6.25 13.5795 6.25C14.382 6.25 15.0329 6.83123 15.0329 7.54788C15.0329 7.79866 14.9501 8.0305 14.8118 8.22897C15.6991 9.0165 17.9946 8.30987 18.8914 9.35799C19.8092 10.4307 19.0072 11.5498 19.0658 12.7188C19.1085 13.5713 19.9777 14.2192 20.8218 14.7708Z" fill="black"/></svg>')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('data_label');

            $table->dropColumn('pois_data_label');
            $table->dropColumn('pois_data_default');
            $table->dropColumn('pois_data_icon');

            $table->dropColumn('tracks_data_label');
            $table->dropColumn('tracks_data_default');
            $table->dropColumn('tracks_data_icon');
        });
    }
}