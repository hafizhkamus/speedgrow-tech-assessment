<?php

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth:api')->get('/transactions/{id}', function (Request $request, $id) {

    $validated = $request->validate([
        'id' => 'required|integer'
    ]);

    // Depends on validation type used
    if ((int)$id !== Auth::id() && !Auth::user()->isAdmin()) {
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }

    $transactions = Transaction::select([
            'id',
            'amount',
            'currency'
        ])
        ->where('user_id', $validated['id'])
        ->paginate(10);

    return response()->json([
        'data' => $transactions->items(),
        'meta' => [
            'current_page' => $transactions->currentPage(),
            'total_pages' => $transactions->lastPage(),
            'total_items' => $transactions->total()
        ]
    ]);
});
