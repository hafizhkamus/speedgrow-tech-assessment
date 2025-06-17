<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nfc_id',
        'amount',
        'currency',
        'status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nfcDevice()
    {
        return $this->belongsTo(NfcDevice::class, 'nfc_id', 'id');
    }

    public static function getPaginatedTransactions($userId, $perPage = 10, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId)
            ->select(['id', 'amount', 'currency', 'status', 'created_at'])
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->paginate($perPage);
    }

    public static function getStatistics($userId)
    {
        $cacheKey = "user:{$userId}:stats";

        return Cache::remember($cacheKey, now()->addMinutes(5), function() use ($userId) {
            // Refresh materialized view first
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY transaction_stats');

            return DB::table('transaction_stats')
                ->where('user_id', $userId)
                ->first();
        });
    }

    public static function checkRecentDuplicate($userId, $nfcId, $amount, $timeframe = '5 minutes')
    {
        return self::where('user_id', $userId)
            ->where('nfc_id', $nfcId)
            ->where('amount', $amount)
            ->where('created_at', '>=', now()->sub($timeframe))
            ->exists();
    }
}
