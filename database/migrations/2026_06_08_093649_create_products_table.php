<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->decimal('original_price', 15, 2)->nullable();
            $table->string('unit');
            $table->string('origin');
            $table->string('badge')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('ic');
            $table->string('bg');
            $table->text('svg');
            $table->string('t1');
            $table->string('t2');
            $table->text('desc')->nullable();
            $table->string('pack');
            $table->string('rating_text')->nullable();
            $table->integer('rating_stars')->default(5);
            $table->decimal('rating_value', 3, 1)->default(5.0);
            $table->integer('reviews_count')->default(0);
            $table->integer('sold_count')->default(0);
            $table->text('nutrition')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
