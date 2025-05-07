<?php

namespace App\Nova\Filters;

use App\Models\App;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;

class UgcUserRelationFilter extends Filter
{
    public $component = 'select-filter';

    protected $fieldAttribute;

    protected $filterBy;

    protected $relationName = 'ugc_pois';

    /**
     * Imposta il nome della relazione da utilizzare
     *
     * @param  string  $relationName  Nome della relazione (ugc_pois, ugc_tracks, ugc_medias)
     * @return $this
     */
    public function setRelation($relationName)
    {
        $this->relationName = $relationName;

        return $this;
    }

    public function fieldAttribute($fieldAttribute)
    {
        $this->fieldAttribute = $fieldAttribute;

        return $this;
    }

    public function filterBy($filterBy)
    {
        $this->filterBy = $filterBy;

        return $this;
    }

    public function apply(Request $request, $query, $value)
    {
        return $query->where($this->filterBy, $value);
    }

    public function options(Request $request)
    {
        $users = [];
        $options = [];
        $appIds = [];
        $apps = [];

        if ($request->user()->can('Admin')) {
            $apps = App::all();
        } else {
            $appOwner = $request->user();
            $apps = $appOwner->apps;
        }

        $appIds = $apps->pluck('id')->toArray();
        $userIds = DB::table($this->relationName)
            ->whereIn('app_id', $appIds)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $users = User::whereIn('id', $userIds)
            ->orderBy('name')
            ->get()
            ->toArray();

        foreach ($users as $user) {
            $label = $user['name'];
            $options[$label] = $user['id'];
        }

        return $options;
    }
}
