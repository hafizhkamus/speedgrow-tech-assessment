<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add indexes
        Schema::table('transactions', function ($table) {
            $table->index('user_id');
            $table->index('created_at');
            $table->index('nfc_id');
        });

        // Create materialized view
        DB::statement('
            CREATE MATERIALIZED VIEW transaction_stats AS
            SELECT
                user_id,
                COUNT(*) as total_count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MAX(created_at) as last_transaction
            FROM transactions
            GROUP BY user_id
        ');

        // Create function to check for recent duplicates
        DB::statement('
            CREATE OR REPLACE FUNCTION check_recent_duplicate(
                p_user_id INTEGER,
                p_nfc_id VARCHAR,
                p_amount DECIMAL,
                p_timeframe INTERVAL DEFAULT \'5 minutes\'
            ) RETURNS BOOLEAN AS $$
            BEGIN
                RETURN EXISTS (
                    SELECT 1 FROM transactions
                    WHERE user_id = p_user_id
                    AND nfc_id = p_nfc_id
                    AND amount = p_amount
                    AND created_at >= (NOW() - p_timeframe)
                );
            END;
            $$ LANGUAGE plpgsql;
        ');
    }

    public function down()
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS transaction_stats');
        DB::statement('DROP FUNCTION IF EXISTS check_recent_duplicate');

        Schema::table('transactions', function ($table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['nfc_id']);
        });
    }
};
