<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('footer_section')->default('information')->after('visibility');
        });

        $legalPages = DB::table('pages')->whereIn('slug', [
            'politica-de-privacidad', 'terminos-y-condiciones', 'cambios-y-devoluciones',
        ]);

        (clone $legalPages)->update(['footer_section' => 'legal']);
        $legalPages->where('visibility', '!=', 'hidden')->update(['visibility' => 'footer']);
    }

    /**
     * The previous schema has no footer-only placement or configurable footer section.
     * Published footer-only pages return to the previous menu-and-footer value.
     */
    public function down(): void
    {
        DB::table('pages')->where('visibility', 'footer')->update(['visibility' => 'menu_and_footer']);

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('footer_section');
        });
    }
};
