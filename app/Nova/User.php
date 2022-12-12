<?php

namespace App\Nova;

use App\Nova\Actions\DownloadMyUgcMediaAction;
use App\Nova\Actions\EmulateUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use League\Flysystem\Exception;
use Vyuldashev\NovaPermission\Permission;
use Vyuldashev\NovaPermission\PermissionBooleanGroup;
use Vyuldashev\NovaPermission\Role;
use Vyuldashev\NovaPermission\RoleBooleanGroup;
use Vyuldashev\NovaPermission\RoleSelect;

class User extends Resource {
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
        'id', 'name', 'email',
    ];

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->can('Admin')) {
            return $query;
        }
        if (count($request->user()->apps) > 0 && $request->user()->apps[0]->dashboard_show == true) {
            return $query->where('id', $request->user()->id)->orWhere('referrer', $request->user()->apps[0]->app_id);
        } else {
            return $query->where('id', $request->user()->id);
        }
    }

    public static function group() {
        return __('Admin');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function fields(Request $request): array {
        return [
            Avatar::make('Avatar')->disk('public'),
            /*Avatar::make('Avatar')->store(function (Request $request, $model) {
                $content = file_get_contents($request->avatar);
                $avatar = Storage::disk('public')->put('/avatars/test', $content);
                return $avatar ? [
                    'avatar' => $avatar,
                ] : function () {
                    throw new Exception(__("Il file caricato non è valido."));
                };
            }),*/

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Last Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'string', 'min:8')
                ->updateRules('nullable', 'string', 'min:8'),

            Text::make(__('Referrer'))->onlyOnDetail()->sortable(),

            RoleSelect::make('Role', 'roles')->showOnCreating(function () {
                $user = \App\Models\User::getEmulatedUser();

                return $user->hasRole('Admin');
            })->showOnUpdating(function () {
                $user = \App\Models\User::getEmulatedUser();

                return $user->hasRole('Admin');
            }),
            \Laravel\Nova\Fields\HasMany::make('Apps'),
            \Laravel\Nova\Fields\HasMany::make('EcTracks'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function cards(Request $request): array {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function filters(Request $request): array {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function lenses(Request $request): array {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param Request $request
     *
     * @return array
     */
    public function actions(Request $request): array {
        return [
            (new DownloadMyUgcMediaAction())->onlyOnDetail(),
            (new EmulateUser())
                ->canSee(function ($request) {
                    return $request->user()->can('emulate', $this->resource);
                })
                ->canRun(function ($request, $zone) {
                    return $request->user()->can('emulate', $zone);
                }),

        ];
    }
}
