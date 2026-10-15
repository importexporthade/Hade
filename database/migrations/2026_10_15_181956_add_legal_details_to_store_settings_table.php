<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The business behind the store, printed on every complaint sheet.
     */
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('store_name');
            $table->string('ruc', 11)->nullable()->after('legal_name');
            $table->string('legal_address')->nullable()->after('ruc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['legal_name', 'ruc', 'legal_address']);
        });
    }
};
