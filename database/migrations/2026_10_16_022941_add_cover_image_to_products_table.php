<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A square photo the owner crops for the product tile, apart from the gallery.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('images');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
};
