<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('jabatan_gpk')->index();
        });

        $adminRoleIds = DB::table('role')->whereIn('nama_role', ['Admin', 'Admin / Pengelola'])->pluck('id_role');
        $primary = DB::table('pengguna')->whereIn('id_role', $adminRoleIds)->where('status_akun', 'Aktif')->orderBy('id_pengguna')->first();
        if ($primary) {
            DB::table('pengguna')->where('id_pengguna', $primary->id_pengguna)->update(['is_super_admin' => true]);
            DB::table('pengguna')->whereIn('id_role', $adminRoleIds)->where('id_pengguna', '!=', $primary->id_pengguna)
                ->update(['status_akun' => 'Nonaktif', 'is_super_admin' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropIndex(['is_super_admin']);
            $table->dropColumn('is_super_admin');
        });
    }
};
