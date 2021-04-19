<?php

namespace App\Nova\Metrics;

use App\Models\UgcMedia;
use App\Models\User;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;

class NewUgcMediaByLoggedUser extends Value {
    /**
     * Calculate the value of the metric.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     *
     * @return mixed
     */
    public function calculate(NovaRequest $request) {
        $user = User::getEmulatedUser();

        if (isset($user->id))
            return $this->count($request, UgcMedia::where('user_id', '=', $user->id));
        else return $this->result(0);
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges() {
        return [
            30 => __('30 Days'),
            60 => __('60 Days'),
            365 => __('365 Days'),
            'TODAY' => __('Today'),
            'MTD' => __('Month To Date'),
            'QTD' => __('Quarter To Date'),
            'YTD' => __('Year To Date'),
        ];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor() {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey() {
        return 'new-ugc-media-user';
    }
}
