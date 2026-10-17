<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What customers do with each product (view it, add it to the cart, remove it, order
     * it), one row per action, for the manager's metrics.
     */
    public function up(): void
    {
        Schema::create('product_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkout_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->uuid('visitor_id')->nullable()->index();
            $table->timestamp('created_at');

            $table->index(['type', 'created_at']);
            $table->index(['product_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_events');
    }
};
