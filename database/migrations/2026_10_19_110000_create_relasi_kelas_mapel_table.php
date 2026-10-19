<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relasi_kelas_mapel', function (Blueprint $table) {
            $table->increments('id_relasi_kelas_mapel');
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_mapel');

            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->restrictOnDelete();
            $table->foreign('id_mapel')->references('id_mapel')->on('mata_pelajaran')->restrictOnDelete();
            $table->unique(['id_kelas', 'id_mapel'], 'uq_relasi_kelas_mapel');
        });

        $existingAssignments = DB::table('relasi_guru_mapel_kelas')
            ->select(['id_kelas', 'id_mapel'])
            ->distinct()
            ->get()
            ->map(fn ($assignment) => [
                'id_kelas' => $assignment->id_kelas,
                'id_mapel' => $assignment->id_mapel,
            ])
            ->all();

        if ($existingAssignments !== []) {
            DB::table('relasi_kelas_mapel')->insertOrIgnore($existingAssignments);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('relasi_kelas_mapel');
    }
};
