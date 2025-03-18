<?php

namespace App\Nova;

use App\Nova\Actions\DownloadMyUgcMediaAction;
use App\Nova\Actions\EmulateUser;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Vyuldashev\NovaPermission\RoleSelect;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name',
        'email',
    ];

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->can('Admin')) {
            return $query;
        }
        if ($request->user()->apps->count() > 0 && $request->user()->apps[0]->dashboard_show == true) {
            $skus = $request->user()->apps->pluck('sku')->toArray();

            return $query->whereIn('sku', $skus);
        } else {
            return $query->where('id', $request->user()->id);
        }
    }

    public static function group()
    {
        return __('Admin');
    }

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        return [
            Avatar::make('Avatar')->disk('public')
                ->hideFromIndex()
                ->hideFromDetail()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->help(__('Upload an avatar for the user. This field is hidden in most views.')),
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255')
                ->help(__('Enter the first name of the user.')),
            Text::make('Last Name')
                ->sortable()
                ->rules('required', 'max:255')
                ->help(__('Enter the last name of the user.')),
            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}')
                ->help(__('Enter the user\'s email address.')),
            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'string', 'min:8')
                ->updateRules('nullable', 'string', 'min:8')
                ->help(__('Set the password for the user.')),
            Text::make(__('Sku'))
                ->onlyOnDetail()
                ->sortable(),
            RoleSelect::make('Role', 'roles')
                ->showOnCreating(function () {
                    $user = \App\Models\User::getEmulatedUser();

                    return $user->hasRole('Admin');
                })
                ->showOnUpdating(function () {
                    $user = \App\Models\User::getEmulatedUser();

                    return $user->hasRole('Admin');
                })
                ->help(__('Select the role of the user. It is important to select "Contributor" for users who use the apps.')),
            \Laravel\Nova\Fields\HasMany::make('Apps'),
            \Laravel\Nova\Fields\HasMany::make('EcTracks'),
        ];
    }

    /**
     * Get the cards available for the request.
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     */
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     */
    public function actions(Request $request): array
    {
        return [
            (new DownloadMyUgcMediaAction)->onlyOnDetail(),
            (new EmulateUser)
                ->canSee(function ($request) {
                    return $request->user()->can('emulate', $this->resource);
                })
                ->canRun(function ($request, $zone) {
                    return $request->user()->can('emulate', $zone);
                }),

        ];
    }
}
