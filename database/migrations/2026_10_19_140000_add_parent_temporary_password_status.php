<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menjalankan perubahan struktur database untuk fitur terkait.
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->boolean('wajib_ganti_password')->nullable()->default(null)->after('password_diubah_pada');
        });

        Schema::table('relasi_orang_tua_siswa', function (Blueprint $table) {
            $table->unique('id_siswa', 'uq_relasi_orang_tua_siswa_siswa');
        });
    }

    // Mengembalikan perubahan struktur saat migration dibatalkan.
    public function down(): void
    {
        Schema::table('relasi_orang_tua_siswa', function (Blueprint $table) {
            $table->index('id_siswa', 'idx_relasi_orang_tua_siswa_siswa');
            $table->dropUnique('uq_relasi_orang_tua_siswa_siswa');
        });

        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropColumn('wajib_ganti_password');
        });
    }
};
