<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shopping_list_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->timestamps();

            $table->unique(['shopping_list_id', 'user_id']);
            $table->index('user_id');
        });

        $now = now();

        foreach (DB::table('shopping_lists')->get(['id', 'workspace_id']) as $list) {
            $ownerId = DB::table('workspaces')
                ->where('id', $list->workspace_id)
                ->value('owner_id');

            if ($ownerId === null) {
                continue;
            }

            DB::table('shopping_list_user')->insert([
                'shopping_list_id' => $list->id,
                'user_id' => $ownerId,
                'role' => 'owner',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_list_user');
    }
};
