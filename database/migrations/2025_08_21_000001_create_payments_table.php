<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('tx_ref')->unique();
            $table->string('provider')->default('chapa');
            $table->string('status')->default('initialized'); // initialized|pending|success|failed|cancelled
            $table->string('currency', 10)->default('ETB');
            $table->decimal('amount', 12, 2);
            $table->json('init_response')->nullable();
            $table->json('verify_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
