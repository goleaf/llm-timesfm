<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crypto_assets', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 32)->unique();
            $table->string('base_asset', 16)->index();
            $table->string('quote_asset', 16)->index();
            $table->unsignedSmallInteger('rank')->default(999)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->decimal('sort_quote_volume', 32, 12)->default(0);
            $table->timestampTz('first_seen_at', 3)->nullable();
            $table->timestampTz('last_seen_at', 3)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_assets');
    }
};
