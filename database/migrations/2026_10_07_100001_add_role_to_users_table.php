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
            $table->string('role', 20)->default('student')->after('password');
            $table->index('role');
        });

        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        // Retrocompatibilidade: mapeia as roles legadas (spatie) para a nova coluna
        $adminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['super-admin', 'admin', 'manager'])
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->pluck('model_id');

        DB::table('users')->whereIn('id', $adminIds)->update(['role' => 'admin']);

        $teacherIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'employee')
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->pluck('model_id');

        DB::table('users')->whereIn('id', $teacherIds)->update(['role' => 'teacher']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
