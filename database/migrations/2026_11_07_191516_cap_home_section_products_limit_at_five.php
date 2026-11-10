<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A home section shows at most five products, one row of cards on a computer, so the
     * limits saved above that come down to five and new sections start there.
     */
    public function up(): void
    {
        DB::table('home_sections')->where('products_limit', '>', 5)->update(['products_limit' => 5]);

        Schema::table('home_sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('products_limit')->default(5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('products_limit')->default(8)->change();
        });
    }
};
