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
        Schema::create('category_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_field_id')->constrained('category_fields')->onDelete('cascade');
            $table->integer('external_id')->nullable()->comment('Choice ID from API');
            $table->string('value')->comment('Option value (e.g., yes, no)');
            $table->string('label')->comment('Display label (e.g., Available)');
            $table->string('slug')->nullable();
            $table->integer('display_priority')->default(0);
            $table->timestamps();

            $table->index('category_field_id');
            $table->index(['category_field_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_options');
    }
};
