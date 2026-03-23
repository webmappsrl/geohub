<?php

namespace App\Helpers;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\ResourceDetailRequest;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;

class NovaCurrentResourceActionHelper
{
    public static function isIndex($request)
    {
        return $request instanceof ResourceIndexRequest;
    }

    public static function isDetail($request)
    {
        return $request instanceof ResourceDetailRequest;
    }

    public static function isForm($request)
    {
        return $request instanceof NovaRequest;
    }

    public static function isCreate($request)
    {
        return $request instanceof NovaRequest &&
            $request->editMode === 'create';
    }

    public static function isUpdate($request)
    {
        return $request instanceof NovaRequest &&
            $request->editMode === 'update';
    }
}
