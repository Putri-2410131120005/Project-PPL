<?php

namespace Database\Seeders;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\Kelas;
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

class RealisticDemoSeeder extends Seeder
{
    private const STAFF_PASSWORD = 'SmartABK2026!';

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        DB::transaction(function (): void {
            $roles = Role::query()->pluck('id_role', 'nama_role');
            $year = TahunAjaran::resolveLabel('2026/2027');
            TahunAjaran::query()->whereKeyNot($year->getKey())->where('status', 'Aktif')->update(['status' => 'Selesai']);
            $year->forceFill(['status' => 'Aktif'])->save();

            $classes = collect([
                ['nama_kelas' => 'VII A', 'tingkat' => 'VII'],
                ['nama_kelas' => 'VII B', 'tingkat' => 'VII'],
                ['nama_kelas' => 'VIII A', 'tingkat' => 'VIII'],
                ['nama_kelas' => 'IX A', 'tingkat' => 'IX'],
            ])->mapWithKeys(function (array $data) use ($year): array {
                $class = Kelas::query()->updateOrCreate(
                    ['nama_kelas' => $data['nama_kelas'], 'tahun_ajaran' => $year->nama],
                    ['id_tahun_ajaran' => $year->getKey(), 'tingkat' => $data['tingkat'], 'status_kelas' => 'Aktif'],
                );

                return [$data['nama_kelas'] => $class];
            });

            $subjects = collect([
                'Bahasa Indonesia', 'Matematika', 'Ilmu Pengetahuan Alam',
                'Ilmu Pengetahuan Sosial', 'Bahasa Inggris',
                'Pendidikan Pancasila', 'Informatika', 'Seni Budaya',
            ])->mapWithKeys(fn (string $name): array => [
                $name => MataPelajaran::query()->updateOrCreate(
                    ['nama_mapel' => $name],
                    ['status_mapel' => 'Aktif'],
                ),
            ]);

            foreach ($classes as $class) {
                $class->mataPelajaran()->syncWithoutDetaching($subjects->pluck('id_mapel')->all());
            }

            $principal = $this->staff($roles['Kepala Sekolah'], 'kepala.sekolah', 'Drs. Hendra Gunawan', '197108121998021003', 'hendra.gunawan@smartabk.test');
            $gpk = $this->staff($roles['GPK'], 'gpk.nuraini', 'Nur Aini, S.Pd.', '198304172009022004', 'nur.aini@smartabk.test', 'Ketua');
            $this->staff($roles['GPK'], 'gpk.lestari', 'Lestari Wulandari, S.Psi.', '198711052014032006', 'lestari.wulandari@smartabk.test', 'Sekretaris');
            $this->staff($roles['GPK'], 'gpk.rahmawati', 'Diah Rahmawati, S.Pd.', '198905212015032008', 'diah.rahmawati@smartabk.test', 'Bendahara');
            $teacherMath = $this->staff($roles['Guru Mapel'], 'guru.budi', 'Budi Santoso, S.Pd.', '198605122011011005', 'budi.santoso@smartabk.test');
            $teacherIndonesian = $this->staff($roles['Guru Mapel'], 'guru.siti', 'Siti Rahayu, S.Pd.', '198804092012022007', 'siti.rahayu@smartabk.test');
            $teacherScience = $this->staff($roles['Guru Mapel'], 'guru.andika', 'Andika Pratama, S.Pd.', '199001182016011009', 'andika.pratama@smartabk.test');
            $companionA = $this->staff($roles['Tim Inklusi'], 'inklusi.maya', 'Maya Puspitasari, S.Psi.', '198912032015032010', 'maya.puspitasari@smartabk.test');
            $companionB = $this->staff($roles['Tim Inklusi'], 'inklusi.rizky', 'Rizky Maulana, S.Pd.', '199203142018011004', 'rizky.maulana@smartabk.test');

            // Keep variables referenced so static analysers do not consider these deliberate accounts unused.
            unset($principal);

            $teacherMappings = [
                [$teacherMath, $subjects['Matematika'], ['VII A', 'VII B', 'VIII A']],
                [$teacherIndonesian, $subjects['Bahasa Indonesia'], ['VII A', 'VII B', 'IX A']],
                [$teacherScience, $subjects['Ilmu Pengetahuan Alam'], ['VII A', 'VIII A', 'IX A']],
            ];
            foreach ($teacherMappings as [$teacher, $subject, $classNames]) {
                foreach ($classNames as $className) {
                    RelasiGuruMapelKelas::query()->firstOrCreate([
                        'id_pengguna' => $teacher->getKey(),
                        'id_mapel' => $subject->getKey(),
                        'id_kelas' => $classes[$className]->getKey(),
                    ]);
                }
            }

            $studentRows = [
                ['26070101', 'Ahmad Fauzan', 'VII A', 'Kesulitan pemusatan perhatian', 'Ibu', 'Rina Marlina', '081234560101', 'Jl. Melati No. 12'],
                ['26070102', 'Nabila Putri Ramadhani', 'VII A', 'Disleksia', 'Ayah', 'Dedi Ramadhan', '081234560102', 'Jl. Kenanga No. 8'],
                ['26070103', 'Raka Aditya Pratama', 'VII A', 'Gangguan spektrum autisme ringan', 'Ibu', 'Yuni Astuti', '081234560103', 'Jl. Anggrek No. 21'],
                ['26070201', 'Alya Safitri', 'VII B', 'Hambatan pendengaran ringan', 'Ibu', 'Fitri Handayani', '081234560201', 'Jl. Mawar No. 17'],
                ['26070202', 'Muhammad Daffa', 'VII B', 'Kesulitan belajar spesifik', 'Ayah', 'Agus Setiawan', '081234560202', 'Jl. Flamboyan No. 5'],
                ['26080101', 'Keisha Maharani', 'VIII A', 'Kesulitan regulasi emosi', 'Ibu', 'Winda Lestari', '081234560301', 'Jl. Cempaka No. 31'],
                ['26080102', 'Bagas Saputra', 'VIII A', 'Hambatan intelektual ringan', 'Ayah', 'Joko Saputra', '081234560302', 'Jl. Dahlia No. 6'],
                ['26090101', 'Citra Ayu Lestari', 'IX A', 'Hambatan penglihatan ringan', 'Ibu', 'Sri Wahyuni', '081234560401', 'Jl. Kamboja No. 14'],
            ];

            $students = collect();
            foreach ($studentRows as [$nis, $name, $className, $need, $relationship, $parentName, $phone, $address]) {
                $student = Siswa::query()->updateOrCreate(['nis' => $nis], [
                    'id_kelas' => $classes[$className]->getKey(),
                    'nama_siswa' => $name,
                    'kebutuhan_khusus' => $need,
                    'status_siswa' => 'Aktif',
                ]);
                $students->put($nis, $student);

                RiwayatKelasSiswa::query()->firstOrCreate([
                    'id_siswa' => $student->getKey(),
                    'id_kelas' => $classes[$className]->getKey(),
                    'tanggal_mulai' => '2026-07-13',
                ], ['status' => 'Aktif']);

                $companion = in_array($className, ['VII A', 'VII B'], true) ? $companionA : $companionB;
                foreach ([$gpk, $companion] as $assistant) {
                    RelasiPendampingSiswa::query()->firstOrCreate([
                        'id_pengguna' => $assistant->getKey(),
                        'id_siswa' => $student->getKey(),
                    ]);
                }

                $parent = Pengguna::query()->updateOrCreate(
                    ['username' => ParentCredentials::username($student)],
                    [
                        'id_role' => $roles['Orang Tua'],
                        'nama' => $parentName,
                        'email' => null,
                        'password' => ParentCredentials::temporaryPassword($student),
                        'wajib_ganti_password' => true,
                        'status_akun' => 'Aktif',
                    ],
                );
                $parentData = OrangTua::query()->updateOrCreate(
                    ['id_pengguna' => $parent->getKey()],
                    ['no_telepon' => $phone, 'alamat' => $address],
                );
                RelasiOrangTuaSiswa::query()->firstOrCreate([
                    'id_orang_tua' => $parentData->getKey(),
                    'id_siswa' => $student->getKey(),
                ], ['hubungan' => $relationship]);
            }

            $developmentNotes = [
                ['2026-08-06', '26070101', $teacherMath, 'Matematika', 'Operasi bilangan bulat menggunakan garis bilangan', 'Perhatian mudah teralihkan saat instruksi diberikan sekaligus.', 'Instruksi dipecah menjadi tiga langkah dan siswa duduk di area minim distraksi.', 'Mampu menyelesaikan 8 dari 10 soal secara mandiri.', 'Terverifikasi', null],
                ['2026-08-13', '26070102', $teacherIndonesian, 'Bahasa Indonesia', 'Menemukan gagasan utama dalam teks deskripsi', 'Membaca lambat dan beberapa kali tertukar pada kata berimbuhan.', 'Menggunakan penanda warna pada kalimat utama dan memberi waktu tambahan.', 'Dapat menemukan gagasan utama pada dua dari tiga paragraf.', 'Terverifikasi', 'Pertahankan penggunaan penanda visual dan bacaan dengan ukuran huruf yang nyaman.'],
                ['2026-08-20', '26070103', $teacherScience, 'Ilmu Pengetahuan Alam', 'Klasifikasi makhluk hidup', 'Kesulitan mengikuti diskusi kelompok yang ramai.', 'Memberikan bagan klasifikasi dan peran pencatat dalam kelompok kecil.', 'Menyelesaikan bagan klasifikasi dengan benar dan mengikuti diskusi selama 20 menit.', 'Terverifikasi', null],
                ['2026-09-03', '26070202', $teacherMath, 'Matematika', 'Pecahan senilai dan perbandingan sederhana', 'Masih keliru membedakan pembilang dan penyebut.', 'Menggunakan potongan kertas pecahan dan latihan bertahap.', 'Memahami pecahan senilai, tetapi masih memerlukan bantuan pada soal cerita.', 'Perlu Revisi', 'Tambahkan hasil pengamatan pada soal cerita dan rencana latihan berikutnya.'],
                ['2026-09-10', '26080101', $teacherScience, 'Ilmu Pengetahuan Alam', 'Sistem pencernaan manusia', 'Menunjukkan kecemasan saat diminta presentasi spontan.', 'Memberi pilihan presentasi berpasangan dengan kartu urutan.', 'Berhasil menjelaskan empat organ pencernaan dengan tenang.', 'Terverifikasi', 'Koordinasikan jadwal presentasi lebih awal agar siswa dapat mempersiapkan diri.'],
                ['2026-09-17', '26080102', $teacherMath, 'Matematika', 'Pola bilangan sederhana', 'Membutuhkan contoh konkret sebelum mengerjakan pola abstrak.', 'Menggunakan balok warna untuk menyusun pola berulang.', 'Mampu melanjutkan tiga pola dan menuliskan dua pola dalam angka.', 'Terverifikasi', null],
                ['2026-10-08', '26090101', $teacherIndonesian, 'Bahasa Indonesia', 'Menyusun teks pidato persuasif', 'Kesulitan membaca teks pada proyektor dari tempat duduk belakang.', 'Memindahkan tempat duduk ke depan dan menyediakan salinan cetak berhuruf besar.', 'Mampu menyusun kerangka pidato lengkap.', 'Dikirim', null],
                ['2026-10-15', '26070101', $teacherMath, 'Matematika', 'Persamaan linear satu variabel', 'Terburu-buru pada tahap pemindahan ruas.', 'Menggunakan kartu langkah pengerjaan dan pemeriksaan mandiri.', 'Benar pada enam dari delapan soal latihan.', 'Draft', null],
            ];

            foreach ($developmentNotes as $row) {
                [$date, $nis, $teacher, $subjectName, $activity, $obstacle, $action, $result, $status, $review] = $row;
                $note = CatatanPerkembangan::query()->firstOrCreate(
                    ['id_siswa' => $students[$nis]->getKey(), 'tanggal_catatan' => $date, 'materi_kegiatan' => $activity],
                    [
                        'id_pembuat' => $teacher->getKey(),
                        'id_mapel' => $subjects[$subjectName]->getKey(),
                        'kendala_peristiwa' => $obstacle,
                        'tindakan' => $action,
                        'hasil_tindakan' => $result,
                        'catatan_tambahan' => 'Evaluasi dilakukan melalui observasi kelas dan hasil latihan.',
                        'status_pengajuan' => $status === 'Draft' ? 'Draft' : 'Dikirim',
                        'versi_pengajuan' => 1,
                        'tanggal_kirim' => $status === 'Draft' ? null : $date.' 14:00:00',
                    ],
                );
                $this->verifyIfNeeded('perkembangan', $note, $gpk, $status, $review);
            }

            $assistanceNotes = [
                ['2026-08-07', '26070101', $companionA, 'Latihan menyusun jadwal belajar visual', 'Siswa masih melewatkan urutan ketika jadwal berisi lebih dari lima kegiatan.', 'Gunakan jadwal maksimal empat kegiatan per sesi dan berikan tanda centang setelah selesai.', 'Terverifikasi', null],
                ['2026-08-14', '26070102', $companionA, 'Pendampingan membaca multisensori', 'Masih tertukar membaca suku kata dengan pola yang mirip.', 'Lanjutkan latihan fonologis 15 menit, tiga kali seminggu.', 'Terverifikasi', 'Catat pasangan suku kata yang paling sering tertukar untuk evaluasi dua minggu berikutnya.'],
                ['2026-08-21', '26070103', $companionA, 'Latihan komunikasi dua arah dan pergantian giliran', 'Kontak mata belum konsisten ketika percakapan berlangsung lebih dari dua menit.', 'Gunakan kartu giliran dan topik yang diminati siswa.', 'Terverifikasi', null],
                ['2026-09-04', '26070201', $companionA, 'Orientasi posisi duduk dan penggunaan instruksi tertulis', 'Instruksi lisan kurang tertangkap ketika kelas ramai.', 'Pastikan siswa melihat wajah guru dan sertakan instruksi singkat secara tertulis.', 'Terverifikasi', null],
                ['2026-09-11', '26080101', $companionB, 'Latihan mengenali tanda awal emosi dan teknik pernapasan', 'Siswa belum konsisten meminta jeda sebelum merasa kewalahan.', 'Sediakan kartu jeda dan latihan pernapasan sebelum pelajaran presentasi.', 'Terverifikasi', null],
                ['2026-09-18', '26080102', $companionB, 'Latihan kemandirian mengikuti instruksi tiga langkah', 'Langkah kedua sering terlewat tanpa pengingat.', 'Gunakan daftar cek visual dan kurangi bantuan secara bertahap.', 'Perlu Revisi', 'Tambahkan indikator keberhasilan yang terukur untuk pertemuan berikutnya.'],
                ['2026-10-09', '26090101', $companionB, 'Pelatihan penggunaan fitur pembesar pada perangkat belajar', 'Belum lancar berpindah antara dokumen dan fitur pembesar.', 'Latihan mandiri dua kali seminggu dan siapkan materi digital sebelum kelas.', 'Dikirim', null],
                ['2026-10-16', '26070202', $companionA, 'Strategi memahami soal cerita dengan kata kunci', 'Masih membutuhkan arahan untuk menentukan operasi hitung.', 'Gunakan lembar penanda informasi diketahui, ditanya, dan operasi.', 'Draft', null],
            ];

            foreach ($assistanceNotes as $row) {
                [$date, $nis, $companion, $activity, $obstacle, $followUp, $status, $review] = $row;
                $note = CatatanPendampingan::query()->firstOrCreate(
                    ['id_siswa' => $students[$nis]->getKey(), 'tanggal_catatan' => $date, 'kegiatan_pendampingan' => $activity],
                    [
                        'id_pembuat' => $companion->getKey(),
                        'kendala' => $obstacle,
                        'tindak_lanjut' => $followUp,
                        'status_pengajuan' => $status === 'Draft' ? 'Draft' : 'Dikirim',
                        'versi_pengajuan' => 1,
                        'tanggal_kirim' => $status === 'Draft' ? null : $date.' 15:00:00',
                        'idempotency_key' => (string) Str::uuid(),
                    ],
                );
                $this->verifyIfNeeded('pendampingan', $note, $gpk, $status, $review);
            }

            app(LaporanBulananService::class)->synchronizeCompletedMonths();

            $this->command?->info('Dataset demo realistis berhasil dibuat: 8 siswa, 8 wali, 9 staf, 16 catatan perkembangan/pendampingan.');
        }, 3);
    }

    private function staff(int $roleId, string $username, string $name, string $nip, string $email, ?string $gpkPosition = null): Pengguna
    {
        return Pengguna::query()->updateOrCreate(['username' => $username], [
            'id_role' => $roleId,
            'nama' => $name,
            'nip' => $nip,
            'email' => $email,
            'password' => self::STAFF_PASSWORD,
            'status_akun' => 'Aktif',
            'jabatan_gpk' => $gpkPosition,
        ]);
    }

    private function verifyIfNeeded(string $type, $note, Pengguna $gpk, string $status, ?string $review): void
    {
        if (! in_array($status, ['Terverifikasi', 'Perlu Revisi'], true)) {
            return;
        }
        if ($note->verifikasi()->where('versi_pengajuan', $note->versi_pengajuan)->exists()) {
            return;
        }

        app(GpkVerifikasiService::class)->verifikasi($type, $note->getKey(), $gpk->getKey(), $status, $review);
    }
}
