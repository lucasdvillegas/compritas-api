<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userIds = DB::table('users')->orderBy('id')->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $now = now();
        $workspaceIdsByUser = [];

        foreach ($userIds as $userId) {
            $workspaceId = DB::table('workspaces')->insertGetId([
                'name' => 'Espacio personal',
                'owner_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('workspace_user')->insert([
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'role' => 'owner',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $workspaceIdsByUser[$userId] = $workspaceId;
        }

        $sourceWorkspaceId = $workspaceIdsByUser[$userIds->first()];

        DB::table('categories')
            ->whereNull('workspace_id')
            ->update(['workspace_id' => $sourceWorkspaceId]);

        DB::table('products')
            ->whereNull('workspace_id')
            ->update(['workspace_id' => $sourceWorkspaceId]);

        $sourceCategories = DB::table('categories')
            ->where('workspace_id', $sourceWorkspaceId)
            ->get();

        $sourceProducts = DB::table('products')
            ->where('workspace_id', $sourceWorkspaceId)
            ->get();

        foreach ($userIds->slice(1) as $userId) {
            $workspaceId = $workspaceIdsByUser[$userId];
            $categoryMap = [];

            foreach ($sourceCategories as $category) {
                $categoryMap[$category->id] = DB::table('categories')->insertGetId([
                    'workspace_id' => $workspaceId,
                    'name' => $category->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($sourceProducts as $product) {
                DB::table('products')->insert([
                    'workspace_id' => $workspaceId,
                    'name' => $product->name,
                    'category_id' => $product->category_id !== null
                        ? ($categoryMap[$product->category_id] ?? null)
                        : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $sourceWorkspaceId = DB::table('workspaces')->orderBy('id')->value('id');

        if ($sourceWorkspaceId !== null) {
            DB::table('products')
                ->where('workspace_id', '!=', $sourceWorkspaceId)
                ->delete();

            DB::table('categories')
                ->where('workspace_id', '!=', $sourceWorkspaceId)
                ->delete();
        }

        DB::table('workspaces')->delete();

        DB::table('categories')->update(['workspace_id' => null]);
        DB::table('products')->update(['workspace_id' => null]);
    }
};
