<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One "Envíos y delivery" text instead of one for Lima and one for the provinces. What the
     * owner already wrote is kept, each part under its destination.
     */
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->text('shipping')->nullable()->after('footer_text');
        });

        DB::table('store_settings')->get()->each(function (object $settings): void {
            $parts = array_filter([
                filled($settings->shipping_capital) ? 'Lima: '.trim($settings->shipping_capital) : null,
                filled($settings->shipping_province) ? 'Provincia: '.trim($settings->shipping_province) : null,
            ]);

            DB::table('store_settings')->where('id', $settings->id)->update(['shipping' => $parts !== [] ? implode("\n\n", $parts) : null]);
        });

        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['shipping_capital', 'shipping_province']);
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->text('shipping_capital')->nullable()->after('footer_text');
            $table->text('shipping_province')->nullable()->after('shipping_capital');
        });

        DB::table('store_settings')->update(['shipping_capital' => DB::raw('shipping')]);

        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('shipping');
        });
    }
};
