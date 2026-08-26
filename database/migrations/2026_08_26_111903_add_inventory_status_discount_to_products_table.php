<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->unsignedInteger('stock')
                ->default(0)
                ->after('price');

            $table->string('status')
                ->default('active')
                ->after('stock');

            $table->boolean('featured')
                ->default(false)
                ->after('status');

            $table->decimal('discount_percent', 5, 2)
                ->default(0)
                ->after('featured');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropColumn([
                'stock',
                'status',
                'featured',
                'discount_percent',
            ]);
        });
    }
};
