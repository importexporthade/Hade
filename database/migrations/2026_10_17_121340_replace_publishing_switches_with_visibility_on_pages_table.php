<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Publicada" and "En el pie" become one choice of where the page shows up; existing
     * pages keep the place they had.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('visibility')->default('menu_and_footer')->after('content');
        });

        DB::table('pages')->where('is_published', false)->update(['visibility' => 'hidden']);
        DB::table('pages')->where('is_published', true)->where('show_in_footer', false)->update(['visibility' => 'menu']);

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['is_published', 'show_in_footer']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('content');
            $table->boolean('show_in_footer')->default(true)->after('is_published');
        });

        DB::table('pages')->where('visibility', 'hidden')->update(['is_published' => false]);
        DB::table('pages')->where('visibility', 'menu')->update(['show_in_footer' => false]);

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
