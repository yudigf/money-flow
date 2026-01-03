<?php

declare(strict_types=1);

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
        Schema::create('investments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "BCA", "Bitcoin", "BBRI"
            $table->string('symbol')->nullable(); // e.g., "BBRI.JK", "BTC"
            $table->enum('type', ['STOCK', 'CRYPTO', 'MUTUAL_FUND', 'GOLD', 'BOND', 'PROPERTY', 'OTHER'])->default('STOCK');
            $table->decimal('quantity', 20, 8); // Shares/units owned
            $table->decimal('buy_price', 15, 2); // Average buy price per unit
            $table->decimal('current_price', 15, 2); // Current market price per unit
            $table->date('buy_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
