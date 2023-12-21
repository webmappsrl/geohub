<?php

namespace App\Nova\Actions;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class EmulateUser extends Action
{
    use InteractsWithQueue, Queueable;

    public $showOnDetail = false;

    public $showOnIndex = false;

    public $showOnTableRow = true;

    public $withoutConfirmation = true;

    public function name(): string
    {
        return __('Emulate');
    }

    /**
     * Perform the action on the given models.
     *
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $user = $models->first();
        User::emulateUser($user->id);

        return Action::redirect('/');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
