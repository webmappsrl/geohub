<?php

namespace App\Providers;

use App\Models\User;
use Laravel\Nova\Nova;
use Webmapp\Import\Import;
use App\Policies\RolePolicy;
use App\Nova\Metrics\NewUsers;
use App\Nova\Metrics\TotalUgc;
use App\Nova\Metrics\NewUgcPois;
use App\Nova\Metrics\TotalUsers;
use App\Nova\Metrics\NewUgcMedia;
use App\Nova\Metrics\NewUgcTracks;
use App\Policies\PermissionPolicy;
use Illuminate\Support\Facades\DB;
use App\Nova\Metrics\UserReferrers;
use Illuminate\Support\Facades\Gate;
use App\Nova\Metrics\NewUgcPoisPerDay;
use App\Nova\Metrics\NewUgcMediaPerDay;
use App\Nova\Metrics\NewUgcTracksPerDay;
use Giuga\LaravelNovaSidebar\NovaSidebar;
use Giuga\LaravelNovaSidebar\SidebarLink;
use Giuga\LaravelNovaSidebar\SidebarGroup;
use App\Nova\Metrics\NewUgcPoisByLoggedUser;
use App\Nova\Metrics\NewUgcMediaByLoggedUser;
use App\Nova\Metrics\NewUgcTracksByLoggedUser;
use Laravel\Nova\NovaApplicationServiceProvider;
use Vyuldashev\NovaPermission\NovaPermissionTool;
use Illuminate\Support\Facades\Auth;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
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
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            $usersEmails = DB::select('
                SELECT DISTINCT users.email as email
                FROM users JOIN model_has_roles
                    ON users.id = model_id
                           AND model_type = \'App\Models\User\'
                WHERE role_id IN (
                    SELECT id
                    FROM roles
                    WHERE LOWER(name) IN (\'admin\', \'author\', \'editor\')
                );');

            $emails = [];

            foreach ($usersEmails as $row) {
                $emails[] = $row->email;
            }

            return in_array($user->email, $emails);
        });
    }

    /**
     * Get the cards that should be displayed on the default Nova dashboard.
     *
     * @return array
     */
    protected function cards(): array
    {
        $cards = [];
        $currentUser = User::getEmulatedUser();

        if ($currentUser->hasRole('Admin')) {
            $cards[] = new TotalUsers();
            $cards[] = new NewUsers();
            $cards[] = new UserReferrers();
            $cards[] = new TotalUgc();
            $cards[] = new NewUgcTracks();
            $cards[] = new NewUgcPois();
            $cards[] = new NewUgcMedia();
            $cards[] = new NewUgcTracksPerDay();
            $cards[] = new NewUgcPoisPerDay();
            $cards[] = new NewUgcMediaPerDay();
        }

        if (
            $currentUser->hasRole('Admin') ||
            $currentUser->hasRole('Editor')
        ) {
            $cards[] = new NewUgcTracksByLoggedUser();
            $cards[] = new NewUgcPoisByLoggedUser();
            $cards[] = new NewUgcMediaByLoggedUser();
        }

        return $cards;
    }

    /**
     * Get the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools(): array
    {
        $currentUser = User::getEmulatedUser();
        $isAdmin =   $currentUser->hasRole('Admin');

        $horizonLink = (new SidebarLink())->setName('Horizon')->setUrl(url('/horizon'));
        $logsLink = (new SidebarLink())->setName('Logs')->setUrl(url('/logs'));
        $toolSidebar = (new NovaSidebar())->addLink($horizonLink)->addLink($logsLink);
        $res = [];
        if ($isAdmin) {
            $res[] = $toolSidebar;
        }
        $res[] = NovaPermissionTool::make()
            ->rolePolicy(RolePolicy::class)
            ->permissionPolicy(PermissionPolicy::class);
        $res[] = new Import;
        return $res;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Nova::sortResourcesBy(function ($resource) {
            return $resource::$priority ?? 99999;
        });
    }
}
