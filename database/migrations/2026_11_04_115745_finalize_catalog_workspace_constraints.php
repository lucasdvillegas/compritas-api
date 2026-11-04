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
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id')->nullable(false)->change();
            $table->unique(['workspace_id', 'name']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->unsignedBigInteger('workspace_id')->nullable(false)->change();
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->unique(['workspace_id', 'name']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropForeign(['category_id']);
            $table->dropUnique(['workspace_id', 'name']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropUnique(['workspace_id', 'name']);
        });
    }
};
