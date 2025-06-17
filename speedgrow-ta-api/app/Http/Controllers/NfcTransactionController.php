<?php

namespace App\Http\Controllers;

use App\Models\NfcTransaction;
use App\Models\NfcDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NfcTransactionController extends Controller
{
    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nfc_id' => [
                'required',
                'string',
                'size:64',
                'regex:/^[a-f0-9]+$/i',
                function ($attribute, $value, $fail) {
                    if (!NfcDevice::where('id', $value)->where('is_active', true)->exists()) {
                        $fail('The NFC device is invalid or inactive.');
                    }
                }
            ],
            'amount' => 'required|numeric|min:0.01|max:10000|decimal:0,2',
            'currency' => 'required|string|size:3|uppercase|in:USD,EUR,GBP',
            'metadata' => 'nullable|array',
            'metadata.*' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $duplicateKey = 'nfc_transaction:'.$request->nfc_id.':'.round($request->amount, 2);
        if (Cache::has($duplicateKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Possible duplicate transaction detected'
            ], 409);
        }

        return DB::transaction(function () use ($request, $duplicateKey) {
            try {
                $device = NfcDevice::where('id', $request->nfc_id)
                    ->where('user_id', $request->user()->id)
                    ->firstOrFail();

                if ($device->last_used_at && $device->last_used_at->gt(now()->subSeconds(5))) {
                    throw new \Exception('NFC device used too frequently');
                }

                $transaction = NfcTransaction::create([
                    'user_id' => $request->user()->id,
                    'nfc_id' => $request->nfc_id,
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                    'metadata' => $request->metadata,
                    'status' => 'pending'
                ]);

                $device->update(['last_used_at' => now()]);

                Cache::put($duplicateKey, true, 30);

                $paymentSuccess = $this->processPayment($transaction);

                if (!$paymentSuccess) {
                    throw new \Exception('Payment processing failed');
                }

                $transaction->update(['status' => 'completed']);

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'status' => $transaction->status,
                        'timestamp' => $transaction->created_at
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ], 400);
            }
        });
    }

    protected function processPayment($transaction)
    {
        return true; //logic
    }
}
