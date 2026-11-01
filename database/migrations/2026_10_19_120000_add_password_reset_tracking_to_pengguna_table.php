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
            $table->timestamp('password_diubah_pada')->nullable()->after('password');
        });
    }

    // Mengembalikan perubahan struktur saat migration dibatalkan.
    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropColumn('password_diubah_pada');
        });
    }
};
