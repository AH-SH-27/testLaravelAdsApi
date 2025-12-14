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
        Schema::create('category_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->integer('external_id')->nullable()->comment('Field ID from API');
            $table->string('attribute')->comment('Field attribute name from API (e.g., price, agent_code)');
            $table->string('name')->comment('Human-readable field name');
            $table->enum('value_type', ['string', 'float', 'integer', 'enum', 'boolean'])->default('string');
            $table->string('filter_type')->nullable()->comment('range, single_choice, etc.');
            $table->boolean('is_mandatory')->default(false);
            $table->json('roles')->nullable()->comment('Array of roles like filterable, exclude_from_post_an_ad');
            $table->enum('state', ['active', 'inactive'])->default('active');
            $table->decimal('min_value', 15, 2)->nullable();
            $table->decimal('max_value', 15, 2)->nullable();
            $table->integer('min_length')->nullable();
            $table->integer('max_length')->nullable();
            $table->integer('display_priority')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'attribute']);
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_fields');
    }
};
