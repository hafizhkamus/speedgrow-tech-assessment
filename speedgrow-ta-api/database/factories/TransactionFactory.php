<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'nfc_id' => null, // You can override this during seeding
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'currency' => 'USD',
            'status' => 'completed',
            'metadata' => ['note' => $this->faker->sentence],
            'created_at' => now(),
        ];
    }
}
