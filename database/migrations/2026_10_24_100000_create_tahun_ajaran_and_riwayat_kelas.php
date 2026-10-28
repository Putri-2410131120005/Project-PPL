<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->increments('id_tahun_ajaran');
            $table->string('nama', 9)->unique();
            $table->date('mulai_ganjil');
            $table->date('selesai_ganjil');
            $table->date('mulai_genap');
            $table->date('selesai_genap');
            $table->enum('status', ['Akan Datang', 'Aktif', 'Selesai'])->default('Akan Datang');
            $table->timestamps();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedInteger('id_tahun_ajaran')->nullable()->after('id_kelas');
            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran')->restrictOnDelete();
        });

        $today = Carbon::today('Asia/Makassar');
        foreach (DB::table('kelas')->select('tahun_ajaran')->distinct()->pluck('tahun_ajaran') as $label) {
            if (! preg_match('/^(\d{4})\/(\d{4})$/', (string) $label, $matches) || (int) $matches[2] !== (int) $matches[1] + 1) {
                continue;
            }

            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];
            $start = Carbon::create($startYear, 7, 1, 0, 0, 0, 'Asia/Makassar');
            $end = Carbon::create($endYear, 6, 30, 0, 0, 0, 'Asia/Makassar');
            $status = $today->lt($start) ? 'Akan Datang' : ($today->gt($end) ? 'Selesai' : 'Aktif');
            $id = DB::table('tahun_ajaran')->insertGetId([
                'nama' => $label,
                'mulai_ganjil' => $start->toDateString(),
                'selesai_ganjil' => Carbon::create($startYear, 12, 31)->toDateString(),
                'mulai_genap' => Carbon::create($endYear, 1, 1)->toDateString(),
                'selesai_genap' => $end->toDateString(),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_tahun_ajaran');
            DB::table('kelas')->where('tahun_ajaran', $label)->update(['id_tahun_ajaran' => $id]);
        }

        Schema::create('riwayat_kelas_siswa', function (Blueprint $table) {
            $table->increments('id_riwayat_kelas_siswa');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_kelas');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['Aktif', 'Selesai'])->default('Aktif');
            $table->timestamps();
            $table->unique(['id_siswa', 'id_kelas', 'tanggal_mulai'], 'riwayat_siswa_kelas_mulai_unique');
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->restrictOnDelete();
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->restrictOnDelete();
        });

        $students = DB::table('siswa')->join('kelas', 'kelas.id_kelas', '=', 'siswa.id_kelas')
            ->leftJoin('tahun_ajaran', 'tahun_ajaran.id_tahun_ajaran', '=', 'kelas.id_tahun_ajaran')
            ->select('siswa.id_siswa', 'siswa.id_kelas', 'tahun_ajaran.mulai_ganjil')
            ->get();
        foreach ($students as $student) {
            DB::table('riwayat_kelas_siswa')->insert([
                'id_siswa' => $student->id_siswa,
                'id_kelas' => $student->id_kelas,
                'tanggal_mulai' => $student->mulai_ganjil ?: $today->toDateString(),
                'tanggal_selesai' => null,
                'status' => 'Aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_siswa');
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['id_tahun_ajaran']);
            $table->dropColumn('id_tahun_ajaran');
        });
        Schema::dropIfExists('tahun_ajaran');
    }
};
