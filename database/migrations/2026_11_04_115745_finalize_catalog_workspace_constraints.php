<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id')->nullable(false)->change();

            if (! $this->uniqueExists('categories', ['workspace_id', 'name'])) {
                $table->unique(['workspace_id', 'name']);
            }

            if ($this->foreignKeyName('categories', 'workspace_id') === null) {
                $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            }
        });

        $categoryForeignKey = $this->foreignKeyName('products', 'category_id');

        Schema::table('products', function (Blueprint $table) use ($categoryForeignKey) {
            if ($categoryForeignKey !== null) {
                $table->dropForeign($categoryForeignKey);
            }

            $table->unsignedBigInteger('workspace_id')->nullable(false)->change();
            $table->unsignedBigInteger('category_id')->nullable()->change();

            if (! $this->uniqueExists('products', ['workspace_id', 'name'])) {
                $table->unique(['workspace_id', 'name']);
            }

            if ($this->foreignKeyName('products', 'workspace_id') === null) {
                $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            }

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if ($name = $this->foreignKeyName('products', 'workspace_id')) {
                $table->dropForeign($name);
            }

            if ($name = $this->foreignKeyName('products', 'category_id')) {
                $table->dropForeign($name);
            }

            if ($this->uniqueExists('products', ['workspace_id', 'name'])) {
                $table->dropUnique(['workspace_id', 'name']);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if ($name = $this->foreignKeyName('categories', 'workspace_id')) {
                $table->dropForeign($name);
            }

            if ($this->uniqueExists('categories', ['workspace_id', 'name'])) {
                $table->dropUnique(['workspace_id', 'name']);
            }
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function uniqueExists(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))->contains(
            fn (array $index): bool => ($index['unique'] ?? false) && ($index['columns'] ?? []) === $columns,
        );
    }

    private function foreignKeyName(string $table, string $column): ?string
    {
        $foreignKey = collect(Schema::getForeignKeys($table))->first(
            fn (array $foreignKey): bool => in_array($column, $foreignKey['columns'] ?? [], true),
        );

        return $foreignKey['name'] ?? null;
    }
};
