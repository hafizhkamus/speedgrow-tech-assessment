<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\NfcDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function processTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nfc_id' => [
                'required',
                'string',
                'size:64',
                'regex:/^[a-f0-9]+$/i',
                Rule::exists('nfc_devices', 'id')->where(function ($query) use ($request) {
                    $query->where('is_active', true)
                          ->where('user_id', $request->user()->id);
                })
            ],
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'currency' => 'required|string|size:3|uppercase',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (Transaction::checkRecentDuplicate(
            $request->user()->id,
            $request->nfc_id,
            $request->amount
        )) {
            return response()->json([
                'error' => 'Possible duplicate transaction'
            ], 409);
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'nfc_id' => $request->nfc_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'metadata' => $request->metadata,
            'status' => 'completed'
        ]);

        NfcDevice::where('id', $request->nfc_id)
            ->update(['last_used_at' => now()]);

        return response()->json([
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'timestamp' => $transaction->created_at
        ], 201);
    }

    public function getTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transactions = Transaction::getPaginatedTransactions(
            $request->user()->id,
            $request->per_page ?? 10,
            $request->start_date,
            $request->end_date
        );

        return response()->json($transactions);
    }

    public function getTransactionStats(Request $request)
    {
        $stats = Transaction::getStatistics($request->user()->id);

        return response()->json($stats ?? [
            'total_count' => 0,
            'total_amount' => 0,
            'avg_amount' => 0,
            'last_transaction' => null
        ]);
    }

    public function getTransaction($id)
    {
        $transaction = Transaction::with(['user', 'nfcDevice'])
            ->findOrFail($id);

        if ($transaction->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($transaction);
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|in:all,nfc,regular',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from'
        ]);

        $query = Transaction::query()
            ->select([
                'id',
                'amount',
                'currency',
                'status',
                'nfc_id',
                'created_at'
            ]);

        if ($request->type === 'nfc') {
            $query->whereNotNull('nfc_id');
        } elseif ($request->type === 'regular') {
            $query->whereNull('nfc_id');
        }

        if ($request->date_from) {
            $query->whereBetween('created_at', [
                $request->date_from,
                $request->date_to ?? now()
            ]);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);
    }
}
