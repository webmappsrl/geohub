<?php

namespace App\Providers;

use App\Models\User;
use App\Nova\Dashboards\MainDashboard;
use App\Nova\Metrics\NewUsers;
use App\Nova\Metrics\TotalUserGeneratedContent;
use App\Nova\Metrics\TotalUsers;
use App\Nova\Metrics\UserGeneratedContentMedia;
use App\Nova\Metrics\UserGeneratedContentPois;
use App\Nova\Metrics\UserGeneratedContentTracks;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Cards\Help;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Silvanite\NovaToolPermissions\NovaToolPermissions;
use Vyuldashev\NovaPermission\NovaPermissionTool;

class NovaServiceProvider extends NovaApplicationServiceProvider {
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        parent::boot();
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes() {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate() {
        Gate::define('viewNova', function ($user) {
            return true;
            //            in_array($user->email, [
            //                'team@webmapp.it'
            //            ]);
        });
    }

    /**
     * Get the cards that should be displayed on the default Nova dashboard.
     *
     * @return array
     */
    protected function cards() {
        $cards = [];
        $currentUser = User::getEmulatedUser();

        if ($currentUser->hasRole('Admin')) {
            $cards[] = new TotalUsers();
            $cards[] = new NewUsers();
        }

        $cards[] = new TotalUserGeneratedContent();
        $cards[] = new UserGeneratedContentTracks();
        $cards[] = new UserGeneratedContentPois();
        $cards[] = new UserGeneratedContentMedia();

        return $cards;;
    }

    /**
     * Get the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards() {
        return [];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools(): array {
        return [
            NovaPermissionTool::make()
                ->rolePolicy(RolePolicy::class)
                ->permissionPolicy(PermissionPolicy::class)
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }
}
