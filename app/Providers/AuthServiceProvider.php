<?php

namespace App\Providers;

use App\Models\EcMedia;
use App\Models\EcPoi;
use App\Models\Layer;
use App\Models\TaxonomyActivity;
use App\Models\TaxonomyPoiType;
use App\Models\TaxonomyTarget;
use App\Models\TaxonomyTheme;
use App\Models\TaxonomyWhen;
use App\Models\TaxonomyWhere;
use App\Models\UgcMedia;
use App\Models\UgcPoi;
use App\Models\UgcTrack;
use App\Policies\EcMediaPolicy;
use App\Policies\EcPoiPolicy;
use App\Policies\LayerPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\TaxonomyActivityPolicy;
use App\Policies\TaxonomyPoiTypePolicy;
use App\Policies\TaxonomyTargetPolicy;
use App\Policies\TaxonomyThemePolicy;
use App\Policies\TaxonomyWhenPolicy;
use App\Policies\TaxonomyWherePolicy;
use App\Policies\UgcMediaPolicy;
use App\Policies\UgcPoiPolicy;
use App\Policies\UgcTrackPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        EcMedia::class => EcMediaPolicy::class,
        EcPoi::class => EcPoiPolicy::class,
        Layer::class => LayerPolicy::class,
        TaxonomyActivity::class => TaxonomyActivityPolicy::class,
        TaxonomyPoiType::class => TaxonomyPoiTypePolicy::class,
        TaxonomyTarget::class => TaxonomyTargetPolicy::class,
        TaxonomyTheme::class => TaxonomyThemePolicy::class,
        TaxonomyWhen::class => TaxonomyWhenPolicy::class,
        TaxonomyWhere::class => TaxonomyWherePolicy::class,
        UgcMedia::class => UgcMediaPolicy::class,
        UgcPoi::class => UgcPoiPolicy::class,
        UgcTrack::class => UgcTrackPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();

        Gate::after(function ($user, $ability) {
            return $user->hasRole('Admin'); // note this returns boolean
        });
    }
}
