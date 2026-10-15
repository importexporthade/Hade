<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The complaints book: one row per "hoja de reclamación", numbered per year.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->string('type');
            $table->string('consumer_name');
            $table->string('document_type');
            $table->string('document_number', 20);
            $table->string('address');
            $table->string('phone', 30);
            $table->string('email');
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name')->nullable();
            $table->string('item_type');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('item_description');
            $table->text('detail');
            $table->text('consumer_request');
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
