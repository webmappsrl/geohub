<?php

namespace App\Http\Controllers;

use App\Models\User;

class EmulateUserController extends Controller
{
    public function restore()
    {
        User::restoreEmulatedUser();

        return redirect()->route('home');
    }
}
