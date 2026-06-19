<?php

namespace App\Http\Controllers\BankFlow;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MoneyHelper;
use Illuminate\Http\JsonResponse;

class BankFlowController extends Controller
{
    public function bankBalance(User $bank): JsonResponse
    {
        if (! $bank->isBank() || ! $bank->is_active) {
            abort(404);
        }

        return response()->json([
            'balance' => MoneyHelper::format($bank->freshBalance()),
        ]);
    }
}
