<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The "Grilla de categorías" section type is gone; the categories still show in the
     * home page's "Universo Hade" row.
     */
    public function up(): void
    {
        DB::table('home_sections')->where('type', 'categories')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
