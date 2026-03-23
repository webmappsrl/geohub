<?php

namespace Laravel\Nova\Tools;

use Illuminate\Contracts\View\View;
use Laravel\Nova\Tool;

class Dashboard extends Tool
{
    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return View
     */
    public function renderNavigation()
    {
        return view('nova::dashboard.navigation');
    }
}
