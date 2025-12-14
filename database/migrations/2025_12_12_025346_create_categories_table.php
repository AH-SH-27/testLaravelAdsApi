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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique()->comment('External ID from OLX API');
            $table->string('name');
            $table->string('name_l1')->nullable()->comment('Arabic name');
            $table->string('slug');
            $table->integer('level')->default(0)->comment('Hierarchy level');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->integer('display_priority')->default(0);
            $table->string('purpose')->nullable()->comment('for-sale, for-rent, etc.');
            $table->json('roles')->nullable()->comment('Array of roles from API');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('external_id');
            $table->index('parent_id');
            $table->index(['parent_id', 'display_priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
