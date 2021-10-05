<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller {
    public function buy(Request $request): JsonResponse {
        $user = auth('api')->user();
        $amount = 1; // the amount should be calculated from the product id

        if ($this->_validatePurchase()) {
            $user->balance += $amount;
            $user->save();

            return response()->json(['balance' => $user->balance], 200);
        } else
            return response()->json(['error' => 'The purchase could not be validated: invalid receipt'], 400);
    }

    private function _validatePurchase(): bool {
        // Validation for android: https://developer.android.com/google/play/developer-api#subscriptions_api_overview
        // Validation for ios: https://developer.apple.com/documentation/storekit/original_api_for_in-app_purchase/validating_receipts_with_the_app_store
        return true;
    }
}
