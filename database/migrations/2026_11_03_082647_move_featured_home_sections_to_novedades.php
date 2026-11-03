<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Productos destacados" is gone: the products marked by hand now fill "Novedades". A
     * featured section becomes the Novedades section, unless the home page already has one,
     * so the same products never show twice.
     */
    public function up(): void
    {
        $featuredSections = DB::table('home_sections')->where('type', 'featured');

        if (DB::table('home_sections')->where('type', 'latest')->exists()) {
            $featuredSections->delete();

            return;
        }

        $firstFeaturedSectionId = (clone $featuredSections)->orderBy('sort_order')->value('id');

        DB::table('home_sections')->where('id', $firstFeaturedSectionId)->update(['type' => 'latest']);
        DB::table('home_sections')->where('type', 'featured')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
