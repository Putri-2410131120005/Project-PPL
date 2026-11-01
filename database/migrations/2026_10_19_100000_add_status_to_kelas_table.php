<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menjalankan perubahan struktur database untuk fitur terkait.
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->string('status_kelas', 20)->default('Aktif')->after('tahun_ajaran');
        });
    }

    // Mengembalikan perubahan struktur saat migration dibatalkan.
    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn('status_kelas');
        });
    }
};
