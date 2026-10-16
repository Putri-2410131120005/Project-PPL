<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumber_rekap_catatan', function (Blueprint $table) {
            $table->increments('id_sumber_rekap');
            $table->unsignedInteger('id_rekap');
            $table->unsignedInteger('id_catatan_perkembangan')->nullable();
            $table->unsignedInteger('id_catatan_pendampingan')->nullable();

            $table->foreign('id_rekap')->references('id_rekap')->on('rekap_perkembangan')->restrictOnDelete();
            $table->foreign('id_catatan_perkembangan')->references('id_catatan_perkembangan')->on('catatan_perkembangan')->restrictOnDelete();
            $table->foreign('id_catatan_pendampingan')->references('id_catatan_pendampingan')->on('catatan_pendampingan')->restrictOnDelete();
            $table->unique(['id_rekap', 'id_catatan_perkembangan'], 'uq_sumber_rekap_catat_1');
            $table->unique(['id_rekap', 'id_catatan_pendampingan'], 'uq_sumber_rekap_catat_2');
        });

        // MariaDB 10.4+: perlindungan konsistensi tambahan.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `sumber_rekap_catatan` ADD CONSTRAINT `chk_sumber_rekap_xor` CHECK (((id_catatan_perkembangan IS NOT NULL AND id_catatan_pendampingan IS NULL) OR (id_catatan_perkembangan IS NULL AND id_catatan_pendampingan IS NOT NULL)))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sumber_rekap_catatan');
    }
};
