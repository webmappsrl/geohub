<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait ValidationTrait
{
    protected function checkValidation($data, $rules)
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $currentErrors = json_decode($validator->errors(), true);
            $errors = [];
            foreach ($currentErrors as $key => $error) {
                $errors[$key] = $error;
            }

            return response(['error' => $errors], 400);
        }
    }
}
