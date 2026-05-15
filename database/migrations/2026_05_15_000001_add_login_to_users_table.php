<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login', 255)->nullable()->unique()->after('name');
        });

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $login = null;

            if (is_string($user->email) && str_contains($user->email, '@')) {
                $login = strtolower((string) strstr($user->email, '@', true));
            }

            if ($login === null || $login === '') {
                $login = strtolower(preg_replace('/\s+/u', '.', trim((string) $user->name)) ?? '');
            }

            if ($login === '') {
                $login = 'user'.$user->id;
            }

            DB::table('users')->where('id', $user->id)->update(['login' => $login]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
        });
    }
};
