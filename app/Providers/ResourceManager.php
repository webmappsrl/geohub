<?php

namespace Laravel\Nova\Tools;

use App\Nova\App;
use App\Nova\EcMedia;
use App\Nova\EcPoi;
use App\Nova\EcTrack;
use App\Nova\Layer;
use App\Nova\OverlayLayer;
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

        $adminArray = [];
        $adminResources = [
            User::class,
            Role::class,
            Permission::class,
        ];
        $editorialContentArray = [];
        $editorialContentResources = [
            App::class,
            EcMedia::class,
            EcTrack::class,
            EcPoi::class,
            Layer::class,
            OverlayLayer::class,
        ];

        $UgcArray = [];
        $UgcResources = [
            UgcPoi::class,
            UgcTrack::class,
            UgcMedia::class,
        ];

        $TaxonomiesArray = [];
        $TaxonomiesResources = [
            TaxonomyWhere::class,
            TaxonomyActivity::class,
            TaxonomyPoiType::class,
            TaxonomyWhen::class,
            TaxonomyTarget::class,
            TaxonomyTheme::class,
        ];

        foreach ($adminResources as $resource) {
            if ($resource::authorizedToViewAny($request)) {
                $adminArray[] = $resource;
            }
        }

        foreach ($editorialContentResources as $resource) {
            if ($resource::authorizedToViewAny($request)) {
                $editorialContentArray[] = $resource;
            }
        }

        foreach ($UgcResources as $resource) {
            if ($resource::authorizedToViewAny($request)) {
                $UgcArray[] = $resource;
            }
        }

        foreach ($TaxonomiesResources as $resource) {
            if ($resource::authorizedToViewAny($request)) {
                $TaxonomiesArray[] = $resource;
            }
        }

        $newNavigation = collect([
            'Editorial Content' => collect($editorialContentArray),
            'Taxonomies' => collect($TaxonomiesArray),
            'User Generated Content' => collect($UgcArray),
            'Admin' => collect($adminArray),

        ]);

        foreach ($newNavigation as $group => $collection) {
            if (count($collection) == 0) {
                $newNavigation->forget($group);
            }
        }

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
