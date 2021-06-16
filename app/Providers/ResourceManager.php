<?php


namespace Laravel\Nova\Tools;

use App\Nova\App;
use App\Nova\EcMedia;
use App\Nova\EcPoi;
use App\Nova\EcTrack;
use App\Nova\TaxonomyActivity;
use App\Nova\TaxonomyPoiType;
use App\Nova\TaxonomyTarget;
use App\Nova\TaxonomyTheme;
use App\Nova\TaxonomyWhen;
use App\Nova\TaxonomyWhere;
use App\Nova\UgcMedia;
use App\Nova\UgcPoi;
use App\Nova\UgcTrack;
use App\Nova\User;
use Illuminate\Http\Request;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;
use Vyuldashev\NovaPermission\Permission;
use Vyuldashev\NovaPermission\Role;

class ResourceManager extends Tool
{
    /**
     * Perform any tasks that need to happen on tool registration.
     *
     * @return void
     */
    public function boot()
    {
        Nova::provideToScript([
            'resources' => function (Request $request) {
                return Nova::resourceInformation($request);
            },
        ]);
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        $request = request();
        $groups = Nova::groups($request);

        $newNavigation = collect([
            'Editorial Content' => collect([
                App::class,
                EcMedia::class,
                EcTrack::class,
                EcPoi::class,
            ]),

            'Taxonomies' => collect([
                TaxonomyWhere::class,
                TaxonomyActivity::class,
                TaxonomyPoiType::class,
                TaxonomyWhen::class,
                TaxonomyTarget::class,
                TaxonomyTheme::class,
            ]),

            'Admin' => collect([
                User::class,
                Role::class,
                Permission::class,
            ]),

            'User Generated Content' => collect([
                UgcPoi::class,
                UgcTrack::class,
                UgcMedia::class,
            ]),

        ]);

        return view('nova::resources.navigation', [
            'navigation' => $newNavigation,
            'groups' => $groups,
        ]);
    }
    /** OLD FUNCTION
     * public function renderNavigation()
     * {
     * $request = request();
     * $groups = Nova::groups($request);
     * $navigation = Nova::groupedResourcesForNavigation($request);
     *
     * return view('nova::resources.navigation', [
     * 'navigation' => $navigation,
     * 'groups' => $groups,
     * ]);
     * } **/


}

