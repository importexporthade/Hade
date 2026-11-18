<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the menu group the owner picks for each category. Existing categories keep the
     * group the menu gave them by the words in their name, so the menu looks the same.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('menu_group')->default('discover')->after('is_visible');
        });

        DB::table('categories')->get(['id', 'name'])->each(function (object $category): void {
            $name = mb_strtolower($category->name);

            $group = match (true) {
                str_contains($name, 'manicur') || str_contains($name, 'pedicur') => 'nails',
                str_contains($name, 'lámpara') || str_contains($name, 'lampara') || str_contains($name, 'accesorio') => 'complements',
                default => 'discover',
            };

            DB::table('categories')->where('id', $category->id)->update(['menu_group' => $group]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('menu_group');
        });
    }
};
