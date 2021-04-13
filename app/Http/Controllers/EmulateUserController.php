<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Redirect;

class EmulateUserController extends Controller {
    public function restore() {
        User::restoreEmulatedUser();

        return redirect()->route('home');
    }
}
