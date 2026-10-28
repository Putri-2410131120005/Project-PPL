<?php

namespace Database\Seeders;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\Kelas;
use App\Models\LaporanBulanan;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\RiwayatKelasSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\GpkVerifikasiService;
use App\Services\LaporanBulananService;
use App\Support\ParentCredentials;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConnectivityAuditSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        DB::transaction(function (): void {
            $roles = Role::query()->pluck('id_role', 'nama_role');
            $year = TahunAjaran::resolveLabel('2026/2027');
            $year->forceFill(['status' => 'Aktif'])->save();

            $classX = Kelas::query()->updateOrCreate(
                ['nama_kelas' => 'X-A Integrasi', 'tahun_ajaran' => $year->nama],
                ['id_tahun_ajaran' => $year->getKey(), 'tingkat' => 'X', 'status_kelas' => 'Aktif'],
            );
            $classXi = Kelas::query()->updateOrCreate(
                ['nama_kelas' => 'XI-B Integrasi', 'tahun_ajaran' => $year->nama],
                ['id_tahun_ajaran' => $year->getKey(), 'tingkat' => 'XI', 'status_kelas' => 'Aktif'],
            );
            $math = MataPelajaran::query()->updateOrCreate(['nama_mapel' => 'Matematika Integrasi'], ['status_mapel' => 'Aktif']);
            $science = MataPelajaran::query()->updateOrCreate(['nama_mapel' => 'IPA Terpadu Integrasi'], ['status_mapel' => 'Aktif']);
            $classX->mataPelajaran()->syncWithoutDetaching([$math->getKey(), $science->getKey()]);
            $classXi->mataPelajaran()->syncWithoutDetaching([$science->getKey()]);

            $admin = $this->user($roles['Admin'], 'admin.integrasi', 'Admin Integrasi Kedua', 'admin.integrasi@sekolah.test', '199001012020011001');
            if (! Pengguna::query()->where('is_super_admin', true)->exists()) {
                $admin->forceFill(['is_super_admin' => true, 'status_akun' => 'Aktif'])->save();
            } elseif (! $admin->is_super_admin) {
                $admin->forceFill(['status_akun' => 'Nonaktif'])->save();
            }
            $principal = $this->user($roles['Kepala Sekolah'], 'kepala.integrasi', 'Ratna Kepala Sekolah', 'kepala.integrasi@sekolah.test', '197805122002122001');
            $gpk = $this->user($roles['GPK'], 'gpk.integrasi', 'Dewi GPK Integrasi', 'gpk.integrasi@sekolah.test', '198404222010012003', 'Ketua');
            $secretary = $this->user($roles['GPK'], 'gpk.sekretaris.integrasi', 'Rina GPK Integrasi', 'gpk.sekretaris.integrasi@sekolah.test', '198505122011012004', 'Sekretaris');
            $treasurer = $this->user($roles['GPK'], 'gpk.bendahara.integrasi', 'Yuni GPK Integrasi', 'gpk.bendahara.integrasi@sekolah.test', '198606132012012005', 'Bendahara');
            Pengguna::query()->where('id_role', $roles['GPK'])->where('status_akun', 'Aktif')
                ->whereNotIn('id_pengguna', [$gpk->getKey(), $secretary->getKey(), $treasurer->getKey()])
                ->whereNull('jabatan_gpk')->update(['status_akun' => 'Nonaktif']);
            $teacher = $this->user($roles['Guru Mapel'], 'guru.integrasi', 'Arif Guru Matematika', 'guru.integrasi@sekolah.test', '198702152014021004');
            $companion = $this->user($roles['Tim Inklusi'], 'tim.integrasi', 'Maya Tim Inklusi', 'tim.integrasi@sekolah.test', '198909092015032005');

            $studentA = Siswa::query()->updateOrCreate(['nis' => 'AUDIT2601'], [
                'id_kelas' => $classX->getKey(), 'nama_siswa' => 'Nadia Audit Integrasi',
                'kebutuhan_khusus' => 'Kesulitan pemusatan perhatian', 'status_siswa' => 'Aktif',
            ]);
            $studentB = Siswa::query()->updateOrCreate(['nis' => 'AUDIT2602'], [
                'id_kelas' => $classX->getKey(), 'nama_siswa' => 'Fajar Audit Integrasi',
                'kebutuhan_khusus' => 'Hambatan belajar spesifik', 'status_siswa' => 'Aktif',
            ]);

            foreach ([$studentA, $studentB] as $student) {
                RiwayatKelasSiswa::query()->firstOrCreate([
                    'id_siswa' => $student->getKey(), 'id_kelas' => $classX->getKey(), 'tanggal_mulai' => '2026-07-01',
                ], ['status' => 'Aktif']);
                RelasiPendampingSiswa::query()->firstOrCreate(['id_pengguna' => $gpk->getKey(), 'id_siswa' => $student->getKey()]);
                RelasiPendampingSiswa::query()->firstOrCreate(['id_pengguna' => $companion->getKey(), 'id_siswa' => $student->getKey()]);
            }
            RelasiGuruMapelKelas::query()->firstOrCreate([
                'id_pengguna' => $teacher->getKey(), 'id_mapel' => $math->getKey(), 'id_kelas' => $classX->getKey(),
            ]);

            $parent = $this->parent($roles['Orang Tua'], $studentA);
            $parentData = OrangTua::query()->updateOrCreate(['id_pengguna' => $parent->getKey()], [
                'no_telepon' => '081234567890', 'alamat' => 'Jl. Integrasi Sekolah No. 1',
            ]);
            RelasiOrangTuaSiswa::query()->firstOrCreate([
                'id_orang_tua' => $parentData->getKey(), 'id_siswa' => $studentA->getKey(),
            ], ['hubungan' => 'Ibu']);

            $decisions = [
                ['date' => '2026-09-05', 'student' => $studentA, 'suffix' => 'tanpa rekomendasi', 'decision' => 'Terverifikasi', 'comment' => null],
                ['date' => '2026-09-12', 'student' => $studentA, 'suffix' => 'dengan rekomendasi', 'decision' => 'Terverifikasi', 'comment' => 'Pertahankan bantuan visual dan berikan waktu pengerjaan bertahap.'],
                ['date' => '2026-09-19', 'student' => $studentB, 'suffix' => 'dikembalikan', 'decision' => 'Perlu Revisi', 'comment' => 'Lengkapi respons siswa setelah tindakan dan rencana tindak lanjut.'],
            ];
            $verification = app(GpkVerifikasiService::class);
            foreach ($decisions as $item) {
                $development = $this->developmentNote($item, $teacher, $math);
                $this->decide($verification, 'perkembangan', $development, $gpk, $item['decision'], $item['comment']);
                $assistance = $this->assistanceNote($item, $companion);
                $this->decide($verification, 'pendampingan', $assistance, $gpk, $item['decision'], $item['comment']);
            }

            app(LaporanBulananService::class)->synchronizeCompletedMonths();
            $report = LaporanBulanan::query()->whereDate('periode_bulan', '2026-09-01')->firstOrFail();

            $this->command?->info("Dataset audit dibuat: {$admin->nama}, {$principal->nama}, {$gpk->nama}, {$teacher->nama}, {$companion->nama}; laporan September 2026 siap ditinjau dan belum dipublikasikan.");
        }, 3);
    }

    private function user(int $role, string $username, string $name, string $email, string $nip, ?string $gpkPosition = null): Pengguna
    {
        return Pengguna::query()->updateOrCreate(['username' => $username], [
            'id_role' => $role, 'nama' => $name, 'nip' => $nip, 'email' => $email,
            'password' => 'Integrasi123!', 'status_akun' => 'Aktif', 'jabatan_gpk' => $gpkPosition,
        ]);
    }

    private function parent(int $role, Siswa $student): Pengguna
    {
        return Pengguna::query()->updateOrCreate(['username' => ParentCredentials::username($student)], [
            'id_role' => $role, 'nama' => 'Sari Wali Nadia', 'email' => 'wali.integrasi@sekolah.test',
            'password' => ParentCredentials::temporaryPassword($student), 'wajib_ganti_password' => true, 'status_akun' => 'Aktif',
        ]);
    }

    private function developmentNote(array $item, Pengguna $teacher, MataPelajaran $subject): CatatanPerkembangan
    {
        $marker = 'Audit integrasi Guru Mapel '.$item['suffix'];
        return CatatanPerkembangan::query()->firstOrCreate(['materi_kegiatan' => $marker], [
            'id_siswa' => $item['student']->getKey(), 'id_pembuat' => $teacher->getKey(), 'id_mapel' => $subject->getKey(),
            'tanggal_catatan' => $item['date'], 'kendala_peristiwa' => 'Siswa memerlukan penyajian materi yang lebih terstruktur.',
            'tindakan' => 'Guru menggunakan contoh konkret dan lembar kerja bertahap.',
            'hasil_tindakan' => 'Siswa mampu menyelesaikan latihan dengan bantuan minimal.',
            'catatan_tambahan' => $item['suffix'] === 'dengan rekomendasi' ? 'Lanjutkan strategi visual pada pertemuan berikutnya.' : null,
            'status_pengajuan' => 'Dikirim', 'versi_pengajuan' => 1, 'tanggal_kirim' => $item['date'].' 10:00:00',
        ]);
    }

    private function assistanceNote(array $item, Pengguna $companion): CatatanPendampingan
    {
        $marker = 'Audit integrasi Tim Inklusi '.$item['suffix'];
        return CatatanPendampingan::query()->firstOrCreate(['kegiatan_pendampingan' => $marker], [
            'id_siswa' => $item['student']->getKey(), 'id_pembuat' => $companion->getKey(), 'tanggal_catatan' => $item['date'],
            'kendala' => 'Konsentrasi menurun pada tugas dengan instruksi panjang.',
            'tindak_lanjut' => 'Gunakan instruksi singkat, jeda teratur, dan pemantauan mingguan.',
            'status_pengajuan' => 'Dikirim', 'versi_pengajuan' => 1, 'tanggal_kirim' => $item['date'].' 11:00:00',
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    private function decide(GpkVerifikasiService $service, string $type, $note, Pengguna $gpk, string $decision, ?string $comment): void
    {
        if ($note->verifikasi()->where('versi_pengajuan', $note->versi_pengajuan)->exists()) {
            return;
        }
        $service->verifikasi($type, $note->getKey(), $gpk->getKey(), $decision, $comment);
    }
}
