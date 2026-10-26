<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\Role;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** Explicitly invoked development data; never called by DatabaseSeeder. */
class A16DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('A16 DATA DEMO hanya untuk local/testing.');
        }
        $connection = DB::connection();
        if (app()->environment('local')) {
            $server = $connection->selectOne('SELECT DATABASE() AS db, @@port AS port');
            $configuredPort = (int) $connection->getConfig('port');
            if ($connection->getDriverName() !== 'mysql' || $connection->getConfig('host') !== '127.0.0.1'
                || $server->db !== 'smart_abk' || (int) $server->port !== $configuredPort) {
                throw new RuntimeException('Target database A16 tidak sesuai audit.');
            }
        } elseif ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Pengujian seeder wajib SQLite :memory: terisolasi.');
        }

        $ran = DB::table('migrations')->pluck('migration')->all();
        $files = array_map(fn ($f) => basename($f, '.php'), glob(database_path('migrations/*.php')));
        if (array_diff($files, $ran)) {
            throw new RuntimeException('Masih ada migration pending.');
        }
        // Secrets are supplied privately, never embedded in code or console output.
        $path = getenv('A16_DEMO_CREDENTIALS_FILE');
        if (! $path || ! is_file($path)) {
            throw new RuntimeException('Berkas kredensial development privat belum disiapkan.');
        }
        $credentials = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        foreach (['demo.a16.guru1', 'demo.a16.guru2', 'demo.a16.gpk'] as $username) {
            if (! is_string($credentials[$username] ?? null) || strlen($credentials[$username]) < 20) {
                throw new RuntimeException('Kredensial DATA DEMO tidak lengkap atau terlalu pendek.');
            }
        }

        DB::transaction(function () use ($credentials) {
            $teacherRole = Role::where('nama_role', 'Guru Mapel')->lockForUpdate()->sole();
            $gpkRole = Role::where('nama_role', 'GPK')->lockForUpdate()->sole();
            $teachers = [];
            foreach ([
                ['demo.a16.guru1', 'DATA DEMO Guru Satu', 'DEMO-A16-GURU-01', $teacherRole->getKey()],
                ['demo.a16.guru2', 'DATA DEMO Guru Dua', 'DEMO-A16-GURU-02', $teacherRole->getKey()],
                ['demo.a16.gpk', 'DATA DEMO GPK', 'DEMO-A16-GPK-01', $gpkRole->getKey()],
            ] as [$username, $name, $nip, $role]) {
                $email = $username.'@example.test';
                $existing = Pengguna::where(fn ($q) => $q->where('username', $username)->orWhere('email', $email)->orWhere('nip', $nip))->get();
                $attrs = ['username' => $username, 'email' => $email, 'nip' => $nip, 'nama' => $name, 'id_role' => $role, 'status_akun' => 'Aktif'];
                if ($existing->isNotEmpty()) {
                    if ($existing->count() !== 1) {
                        throw new RuntimeException('Konflik identitas DATA DEMO; tidak ada data ditimpa.');
                    }
                    $user = $existing->first();
                    $this->assertMatches($user, $attrs);
                    if (! Hash::check($credentials[$username], $user->password)) {
                        throw new RuntimeException('Kredensial berbeda dari akun yang tersedia; tidak direset.');
                    }
                } else {
                    $user = Pengguna::create($attrs + ['password' => $credentials[$username]]);
                }
                $teachers[] = $user;
            }

            $math = $this->record(MataPelajaran::class, ['nama_mapel' => 'DATA DEMO Matematika'], ['status_mapel' => 'Aktif']);
            $language = $this->record(MataPelajaran::class, ['nama_mapel' => 'DATA DEMO Bahasa Indonesia'], ['status_mapel' => 'Aktif']);
            $expectedAssignments = [];
            foreach ([['X IPA 1', 'X', $teachers[0], $math], ['XI IPA 1', 'XI', $teachers[0], $math], ['XI IPA 2', 'XI', $teachers[1], $language]] as $i => [$name, $level, $teacher, $subject]) {
                $class = $this->record(Kelas::class, ['nama_kelas' => 'DATA DEMO '.$name], ['tingkat' => $level, 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif']);
                $this->record(Siswa::class, ['nis' => 'DEMO-A16-00'.($i + 1)], ['nama_siswa' => 'DATA DEMO Siswa '.['Satu', 'Dua', 'Tiga'][$i], 'id_kelas' => $class->getKey(), 'kebutuhan_khusus' => 'DATA DEMO - dukungan pembelajaran sintetis', 'status_siswa' => 'Aktif']);
                // Pivot uses the existing Eloquent relationship, adding only missing pairs.
                if (! $class->mataPelajaran()->whereKey($subject->getKey())->exists()) {
                    $class->mataPelajaran()->attach($subject->getKey());
                }
                $assignment = $this->record(RelasiGuruMapelKelas::class, ['id_pengguna' => $teacher->getKey(), 'id_mapel' => $subject->getKey(), 'id_kelas' => $class->getKey()], []);
                $expectedAssignments[] = $assignment->getKey();
            }
            // Do not silently retain additional access on a pre-existing demo account.
            if (RelasiGuruMapelKelas::whereIn('id_pengguna', [$teachers[0]->getKey(), $teachers[1]->getKey()])
                ->whereNotIn('id_relasi_guru_mapel', $expectedAssignments)->exists()) {
                throw new RuntimeException('Penugasan tambahan pada akun DATA DEMO; perlu pemeriksaan.');
            }
        });
        $this->command?->info('DATA DEMO A16 siap. Tidak ada catatan perkembangan atau verifikasi dibuat.');
    }

    private function record(string $class, array $key, array $attributes): Model
    {
        $matches = $class::where($key)->lockForUpdate()->limit(2)->get();
        if ($matches->count() > 1) {
            throw new RuntimeException('Identitas DATA DEMO ambigu; tidak ada record dipilih secara acak.');
        }
        $model = $matches->first();
        if ($model) {
            $this->assertMatches($model, $key + $attributes);

            return $model;
        }

        return $class::create($key + $attributes);
    }

    private function assertMatches(Model $model, array $attributes): void
    {
        foreach ($attributes as $field => $value) {
            if ((string) $model->getAttribute($field) !== (string) $value) {
                throw new RuntimeException('Konflik record DATA DEMO; transaksi dibatalkan tanpa menimpa data.');
            }
        }
    }
}
