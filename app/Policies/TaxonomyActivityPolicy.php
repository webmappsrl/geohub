<?php

namespace App\Policies;

use App\Models\TaxonomyActivity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaxonomyActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        if ($user->hasRole('Author') || $user->hasRole('Contributor')) {
            return false;
        }
    }
    
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole('Editor')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaxonomyActivity  $taxonomyActivity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, TaxonomyActivity $taxonomyActivity)
    {
        if ($user->hasRole('Editor')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaxonomyActivity  $taxonomyActivity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, TaxonomyActivity $taxonomyActivity)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaxonomyActivity  $taxonomyActivity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, TaxonomyActivity $taxonomyActivity)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaxonomyActivity  $taxonomyActivity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, TaxonomyActivity $taxonomyActivity)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TaxonomyActivity  $taxonomyActivity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, TaxonomyActivity $taxonomyActivity)
    {
        //
    }
}
