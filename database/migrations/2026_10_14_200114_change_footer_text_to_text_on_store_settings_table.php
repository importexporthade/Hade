<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The footer carries the store's "about" paragraph, longer than a string column holds.
     */
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->text('footer_text')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('footer_text')->nullable()->change();
        });
    }
};
