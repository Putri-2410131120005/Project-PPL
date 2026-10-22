<?php

namespace Database\Seeders;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\Kelas;
use App\Models\LaporanPerkembangan;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RekapPerkembangan;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SumberRekapCatatan;
use App\Models\VerifikasiCatatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParentPortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $parent = $this->account('Orang Tua', 'Wali Demo SMART-ABK', 'ortu_demo', 'ortu.demo@smart-abk.test', 'Demo123!');
            $teacher = $this->account('Guru Mapel', 'Ibu Rina Kartika', 'guru_demo_portal', 'guru.demo.portal@smart-abk.test');
            $companion = $this->account('Tim Inklusi', 'Bapak Fajar Wicaksono', 'pendamping_demo_portal', 'pendamping.demo.portal@smart-abk.test');
            $gpk = $this->account('GPK', 'Ibu Sari Maharani', 'gpk_demo_portal', 'gpk.demo.portal@smart-abk.test');

            $class = Kelas::query()->updateOrCreate(
                ['nama_kelas' => 'X-B Demo', 'tahun_ajaran' => '2026/2027'],
                ['tingkat' => 'X', 'status_kelas' => 'Aktif']
            );
            $photo = collect(Storage::disk('public')->files('foto-siswa'))
                ->first(fn (string $path) => Str::endsWith(Str::lower($path), ['.jpg', '.jpeg', '.png']));
            $student = Siswa::query()->updateOrCreate(
                ['nis' => 'DEMO-ORTU-01'],
                [
                    'id_kelas' => $class->getKey(),
                    'nama_siswa' => 'Aulia Rahma Putri',
                    'foto' => $photo,
                    'kebutuhan_khusus' => 'Hambatan pemusatan perhatian',
                    'status_siswa' => 'Aktif',
                ]
            );

            $parentProfile = OrangTua::query()->updateOrCreate(
                ['id_pengguna' => $parent->getKey()],
                ['no_telepon' => '081234567890', 'alamat' => 'Makassar']
            );
            RelasiOrangTuaSiswa::query()->updateOrCreate(
                ['id_orang_tua' => $parentProfile->getKey(), 'id_siswa' => $student->getKey()],
                ['hubungan' => 'Ibu']
            );
            RelasiPendampingSiswa::query()->firstOrCreate([
                'id_pengguna' => $companion->getKey(),
                'id_siswa' => $student->getKey(),
            ]);

            $mathematics = MataPelajaran::query()->updateOrCreate(
                ['nama_mapel' => 'Matematika'],
                ['status_mapel' => 'Aktif']
            );
            $indonesian = MataPelajaran::query()->updateOrCreate(
                ['nama_mapel' => 'Bahasa Indonesia'],
                ['status_mapel' => 'Aktif']
            );

            $notes = collect([
                $this->developmentNote($student, $teacher, $gpk, $mathematics, now()->subDays(1), [
                    'materi_kegiatan' => 'Penjumlahan dan pengurangan bilangan dua angka menggunakan benda konkret.',
                    'kendala_peristiwa' => 'Aulia masih mudah terdistraksi ketika instruksi diberikan sekaligus.',
                    'tindakan' => 'Instruksi dipecah menjadi tiga langkah singkat dan disertai kartu angka.',
                    'hasil_tindakan' => 'Aulia menyelesaikan 8 dari 10 soal secara mandiri dan meminta bantuan dengan tepat.',
                    'catatan_tambahan' => 'Berikan jeda singkat setelah 15 menit belajar.',
                ]),
                $this->assistanceNote($student, $companion, $gpk, now()->subDays(3), [
                    'kegiatan_pendampingan' => 'Latihan mempertahankan fokus saat mengikuti kegiatan kelas dan menyusun jadwal visual.',
                    'kendala' => 'Fokus menurun ketika suasana kelas mulai ramai.',
                    'tindak_lanjut' => 'Gunakan jadwal visual di meja dan berikan penguatan positif setelah satu tugas selesai.',
                ]),
                $this->developmentNote($student, $teacher, $gpk, $indonesian, now()->subDays(6), [
                    'materi_kegiatan' => 'Membaca teks pendek dan menemukan gagasan utama.',
                    'kendala_peristiwa' => 'Membutuhkan waktu tambahan saat membaca paragraf kedua.',
                    'tindakan' => 'Teks dibagi per paragraf dan kata kunci diberi penanda warna.',
                    'hasil_tindakan' => 'Aulia mampu menyebutkan gagasan utama dari dua paragraf dengan kalimat sendiri.',
                    'catatan_tambahan' => 'Latihan membaca 10 menit setiap sore bersama keluarga.',
                ]),
                $this->assistanceNote($student, $companion, $gpk, now()->subDays(9), [
                    'kegiatan_pendampingan' => 'Simulasi meminta bantuan dan menyampaikan kebutuhan secara sopan.',
                    'kendala' => 'Masih ragu berbicara ketika berada dalam kelompok besar.',
                    'tindak_lanjut' => 'Mulai latihan dari kelompok kecil berisi dua sampai tiga teman.',
                ]),
                $this->developmentNote($student, $teacher, $gpk, $mathematics, now()->subDays(13), [
                    'materi_kegiatan' => 'Pengenalan nilai tempat satuan dan puluhan.',
                    'kendala_peristiwa' => 'Tertukar ketika membaca angka dengan posisi yang berdekatan.',
                    'tindakan' => 'Menggunakan papan nilai tempat dan balok hitung.',
                    'hasil_tindakan' => 'Aulia dapat mengelompokkan satuan dan puluhan pada 7 dari 8 latihan.',
                    'catatan_tambahan' => 'Pertahankan penggunaan media konkret sebelum beralih ke simbol.',
                ]),
                $this->assistanceNote($student, $companion, $gpk, now()->subDays(17), [
                    'kegiatan_pendampingan' => 'Pendampingan transisi dari waktu istirahat menuju pelajaran berikutnya.',
                    'kendala' => 'Perlu diingatkan lebih dari satu kali untuk menyiapkan alat belajar.',
                    'tindak_lanjut' => 'Pasang daftar cek bergambar pada bagian dalam meja siswa.',
                ]),
            ]);

            $semester = now()->month >= 7 ? 'Ganjil' : 'Genap';
            $startYear = now()->month >= 7 ? now()->year : now()->year - 1;
            $period = $semester.' '.$startYear.'/'.($startYear + 1);
            $recap = RekapPerkembangan::query()->updateOrCreate(
                ['id_siswa' => $student->getKey(), 'periode' => $period],
                [
                    'id_pembuat' => $gpk->getKey(),
                    'ringkasan_perkembangan' => 'Aulia menunjukkan perkembangan dalam mempertahankan fokus, mengikuti instruksi bertahap, dan menyampaikan kebutuhan belajar.',
                    'tanggal_rekap' => now()->subDay(),
                ]
            );

            foreach ($notes as $note) {
                $key = $note instanceof CatatanPerkembangan
                    ? ['id_rekap' => $recap->getKey(), 'id_catatan_perkembangan' => $note->getKey()]
                    : ['id_rekap' => $recap->getKey(), 'id_catatan_pendampingan' => $note->getKey()];
                SumberRekapCatatan::query()->firstOrCreate($key);
            }

            LaporanPerkembangan::query()->updateOrCreate(
                ['id_rekap' => $recap->getKey(), 'periode' => $period],
                [
                    'id_pembuat' => $gpk->getKey(),
                    'isi_laporan' => 'Selama semester ini Aulia menunjukkan kemajuan yang konsisten. Aulia semakin mampu mengikuti instruksi yang dibagi menjadi langkah-langkah singkat, menyelesaikan tugas akademik dengan bantuan visual, dan mulai berani meminta bantuan. Dukungan berikutnya difokuskan pada konsistensi jadwal visual, latihan membaca singkat di rumah, serta penguatan positif setelah tugas selesai.',
                    'status_laporan' => 'Tersedia',
                    'tanggal_laporan' => now()->subDay(),
                    'id_publikator' => $gpk->getKey(),
                    'tanggal_publikasi' => now()->subDay(),
                ]
            );
        });
    }

    private function account(string $roleName, string $name, string $username, string $email, string $password = 'Demo123!'): Pengguna
    {
        $role = Role::query()->firstOrCreate(['nama_role' => $roleName]);

        return Pengguna::query()->updateOrCreate(
            ['username' => $username],
            [
                'id_role' => $role->getKey(),
                'nama' => $name,
                'email' => $email,
                'password' => $password,
                'wajib_ganti_password' => false,
                'password_diubah_pada' => now(),
                'status_akun' => 'Aktif',
            ]
        );
    }

    private function developmentNote(Siswa $student, Pengguna $author, Pengguna $gpk, MataPelajaran $subject, Carbon $date, array $content): CatatanPerkembangan
    {
        $note = CatatanPerkembangan::query()->firstOrNew([
            'id_siswa' => $student->getKey(),
            'id_pembuat' => $author->getKey(),
            'id_mapel' => $subject->getKey(),
            'tanggal_catatan' => $date->toDateString(),
        ]);
        $note->forceFill(array_merge($content, [
            'status_pengajuan' => 'Terverifikasi',
            'versi_pengajuan' => 1,
            'tanggal_kirim' => $date->copy()->endOfDay(),
        ]))->save();
        $this->publish($note, $gpk, $date);

        return $note;
    }

    private function assistanceNote(Siswa $student, Pengguna $author, Pengguna $gpk, Carbon $date, array $content): CatatanPendampingan
    {
        $note = CatatanPendampingan::query()->firstOrNew([
            'id_siswa' => $student->getKey(),
            'id_pembuat' => $author->getKey(),
            'tanggal_catatan' => $date->toDateString(),
        ]);
        $note->forceFill(array_merge($content, [
            'status_pengajuan' => 'Terverifikasi',
            'versi_pengajuan' => 1,
            'tanggal_kirim' => $date->copy()->endOfDay(),
            'idempotency_key' => $note->idempotency_key ?: (string) Str::uuid(),
        ]))->save();
        $this->publish($note, $gpk, $date);

        return $note;
    }

    private function publish(CatatanPerkembangan|CatatanPendampingan $note, Pengguna $gpk, Carbon $date): void
    {
        $foreignKey = $note instanceof CatatanPerkembangan
            ? 'id_catatan_perkembangan'
            : 'id_catatan_pendampingan';
        $verification = VerifikasiCatatan::query()->firstOrNew([
            $foreignKey => $note->getKey(),
            'versi_pengajuan' => 1,
        ]);
        $verification->forceFill([
            'id_verifikator' => $gpk->getKey(),
            'status_verifikasi' => 'Terverifikasi',
            'catatan_verifikasi' => 'Catatan demo telah ditinjau dan layak dipublikasikan.',
            'tanggal_verifikasi' => $date->copy()->addHours(2),
            'id_publikator' => $gpk->getKey(),
            'tanggal_publikasi' => $date->copy()->addHours(3),
        ])->save();
    }
}
