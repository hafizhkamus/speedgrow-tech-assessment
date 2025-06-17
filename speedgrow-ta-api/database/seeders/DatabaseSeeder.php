<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\TransactionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(5)->create()->each(function ($user) {
            $device = \App\Models\NfcDevice::factory()->create([
                'user_id' => $user->id,
            ]);

            \App\Models\Transaction::factory(10)->create([
                'user_id' => $user->id,
                'nfc_id' => $device->id,
            ]);
        });
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
