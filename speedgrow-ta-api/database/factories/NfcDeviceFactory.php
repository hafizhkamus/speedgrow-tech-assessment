<?php

namespace Database\Factories;

use App\Models\NfcDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

class NfcDeviceFactory extends Factory
{
    protected $model = NfcDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(), // Or null, if optional
        ];
    }
}
