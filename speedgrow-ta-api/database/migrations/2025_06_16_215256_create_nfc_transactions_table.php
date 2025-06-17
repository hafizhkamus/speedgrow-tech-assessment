<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('nfc_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nfc_id', 64);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign key and indexes
            $table->foreign('nfc_id')->references('id')->on('nfc_devices')->onDelete('cascade');
            $table->index('user_id');
            $table->index('nfc_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfc_transactions');
    }
};
