<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'is_featured'], 'idx_products_active_featured');
            $table->index(['is_active', 'show_on_hero'], 'idx_products_active_hero');
            $table->index('price', 'idx_products_price');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'is_approved'], 'idx_reviews_product_approved');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_orders_status_created');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active_featured');
            $table->dropIndex('idx_products_active_hero');
            $table->dropIndex('idx_products_price');
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_product_approved');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status_created');
        });
    }
};
