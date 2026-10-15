<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A banner now points at a product, category or page of the store instead of a typed
     * address; link_url stays for addresses outside the store.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('link_type')->default('none')->after('mobile_image');
            $table->foreignId('link_product_id')->nullable()->after('link_type')->constrained('products')->nullOnDelete();
            $table->foreignId('link_category_id')->nullable()->after('link_product_id')->constrained('categories')->nullOnDelete();
            $table->foreignId('link_page_id')->nullable()->after('link_category_id')->constrained('pages')->nullOnDelete();
        });

        DB::table('banners')->whereNotNull('link_url')->update(['link_type' => 'external']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('link_product_id');
            $table->dropConstrainedForeignId('link_category_id');
            $table->dropConstrainedForeignId('link_page_id');
            $table->dropColumn('link_type');
        });
    }
};
