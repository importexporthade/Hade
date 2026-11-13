<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One switch per destination instead of one for both, so a product can ship to Lima
     * only or to the provinces only. Each product keeps what its single switch said.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('ships_to_capital')->default(true)->after('is_in_stock');
            $table->boolean('ships_to_province')->default(true)->after('ships_to_capital');
        });

        DB::table('products')->update([
            'ships_to_capital' => DB::raw('shows_shipping'),
            'ships_to_province' => DB::raw('shows_shipping'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('shows_shipping');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('shows_shipping')->default(true)->after('is_in_stock');
        });

        DB::table('products')->update([
            'shows_shipping' => DB::raw('ships_to_capital OR ships_to_province'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['ships_to_capital', 'ships_to_province']);
        });
    }
};
