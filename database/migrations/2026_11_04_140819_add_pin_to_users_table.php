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
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin', 8)->nullable()->unique()->after('email');
        });

        foreach (DB::table('users')->orderBy('id')->pluck('id') as $userId) {
            do {
                $pin = (string) random_int(10000000, 99999999);
            } while (DB::table('users')->where('pin', $pin)->exists());

            DB::table('users')->where('id', $userId)->update(['pin' => $pin]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('pin', 8)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pin']);
            $table->dropColumn('pin');
        });
    }
};
