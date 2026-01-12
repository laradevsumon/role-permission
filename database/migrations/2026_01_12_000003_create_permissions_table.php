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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // "Stock Management"
            $table->string('slug')->unique();                    // "fmdf.stock"
            $table->string('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // Self-referencing for hierarchy
            $table->string('type')->default('module');           // 'module', 'action'
            $table->string('route_name')->nullable();            // 'fmdf.stock'
            $table->integer('order')->default(0);                // Display order
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('permissions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
