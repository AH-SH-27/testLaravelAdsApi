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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 15, 2)->nullable()->comment('Denormalized price field for quick filtering');
            $table->enum('status', ['draft', 'published', 'sold', 'expired'])->default('published');
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('status');
            $table->index(['user_id', 'created_at']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
