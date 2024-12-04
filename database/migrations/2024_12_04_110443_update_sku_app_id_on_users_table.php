<?php

use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSkuAppIdOnUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('referrer', 'sku');
            $table->string('app_id', 100)->before('sku')->nullable();
        });

        $usersArr = DB::select(DB::raw('select distinct user_id, sku, app_id from (
	select user_id, sku, app_id from ugc_tracks ut where sku is not null or app_id is not null
	union all
	select user_id, sku, app_id from ugc_pois ut where sku is not null or app_id is not null
) as t'));

        /**
         * @var \App\Services\UserService
         */
        $userService = app()->make(UserService::class);
        foreach ($usersArr as $user) {
            /**
             * @var \App\Models\User
             */
            $model = User::find($user->user_id);
            //$user->timestamps = false;
            $userService->assigUserSkuAndAppIdIfNeeded($model, $user->sku, $user->app_id);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('sku', 'referrer');
            $table->dropColumn('app_id');
        });
    }
}
