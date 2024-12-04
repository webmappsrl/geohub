<?php


namespace App\Services;

use App\Models\User;

class UserService
{
  /**
   * Undocumented function
   *
   * @param \App\Models\User $user
   * @param string|null $sku
   * @param string|null $appId
   * @param bool $save - If the model should be saved
   * @return \App\Models\User - the eventually updated User model
   */
  public function assigUserSkuAndAppIdIfNeeded($user, $sku = null, $appId = null, $save = true): User
  {
    if (is_null($user->sku) && ! is_null($sku))
      $user->sku = $sku;

    if (is_null($user->appId) && ! is_null($appId))
      $user->app_id = $appId;

    if ($save && $user->isDirty()) {
      $user->save();
    }

    return $user;
  }

  static public function getService(): UserService
  {
    return app()->make(static::class);
  }
}
