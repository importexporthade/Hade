<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What shipping to Lima and to the provinces costs and takes, written by the owner and
     * shown on the product pages.
     */
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->text('shipping_capital')->nullable()->after('footer_text');
            $table->text('shipping_province')->nullable()->after('shipping_capital');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['shipping_capital', 'shipping_province']);
        });
    }
};
