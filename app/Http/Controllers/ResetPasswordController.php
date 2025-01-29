<?php

namespace App\Http\Controllers;

use Laravel\Nova\Http\Controllers\ResetPasswordController as NovaResetPasswordController;

class ResetPasswordController extends NovaResetPasswordController
{
    public function redirectPath()
    {
        return route('nova.password.reset.success');
    }
}
