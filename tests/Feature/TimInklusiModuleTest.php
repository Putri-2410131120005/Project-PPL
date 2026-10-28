<?php

namespace Tests\Feature;

use App\Models\CatatanPendampingan;
use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\RelasiPendampingSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\VerifikasiCatatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TimInklusiModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_login_can_bind_a_specific_active_companion(): void
    {
        $first = $this->makeUser('Pendamping Pertama', 'Tim Inklusi');
        $selected = $this->makeUser('Pendamping Terpilih', 'Tim Inklusi');

        $this->post(route('dev.login.store'), [
            'role' => 'tim-inklusi',
            'pendamping_id' => $selected->getKey(),
        ])->assertRedirect(route('dev.preview', 'tim-inklusi'))
            ->assertSessionHas('dev_pengguna_id', $selected->getKey());

        $this->get(route('dev.preview', 'tim-inklusi'))
            ->assertRedirect(route('dev.tim-inklusi.dashboard'));

        $this->get(route('dev.tim-inklusi.dashboard'))
            ->assertOk()
            ->assertSee($selected->nama)
            ->assertDontSee($first->nama);
    }

    public function test_gpk_cannot_be_used_as_tim_inklusi_identity_even_in_development_mode(): void
    {
        $gpk = $this->makeUser('GPK Bukan Pendamping', 'GPK');

        $this->from(route('dev.login'))->post(route('dev.login.store'), [
            'role' => 'tim-inklusi',
            'pendamping_id' => $gpk->getKey(),
        ])->assertRedirect(route('dev.login'))
            ->assertSessionHasErrors('pendamping_id')
            ->assertSessionMissing(['dev_role', 'dev_pengguna_id']);

        $this->withSession(['dev_role' => 'tim-inklusi', 'dev_pengguna_id' => $gpk->getKey()])
            ->get(route('dev.tim-inklusi.dashboard'))
            ->assertRedirect(route('dev.login'))
            ->assertSessionHasErrors('pendamping_id')
            ->assertSessionMissing(['dev_role', 'dev_pengguna_id']);
    }

    public function test_dashboard_and_student_list_only_show_assigned_data(): void
    {
        [$pendamping, $assigned, $outside] = $this->fixture();
        $assigned->update(['foto' => 'foto-siswa/siswa-dampingan.jpg']);

        $this->asTimInklusi($pendamping)
            ->get(route('dev.tim-inklusi.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Guru Pendamping')
            ->assertSee('1')
            ->assertSee($assigned->nama_siswa);

        $this->get(route('dev.tim-inklusi.students.index'))
            ->assertOk()
            ->assertSee($assigned->nama_siswa)
            ->assertSee('/storage/foto-siswa/siswa-dampingan.jpg', false)
            ->assertDontSee($outside->nama_siswa);

        $this->get(route('dev.tim-inklusi.students.show', $assigned))
            ->assertOk()
            ->assertSee('/storage/foto-siswa/siswa-dampingan.jpg', false);

        $this->get(route('dev.tim-inklusi.students.show', $outside))->assertNotFound();
    }

    public function test_search_level_filter_and_pagination_are_applied_to_assigned_students(): void
    {
        [$pendamping, $assigned] = $this->fixture();

        $this->asTimInklusi($pendamping)
            ->get(route('dev.tim-inklusi.students.index', ['cari' => 'Siswa Dampingan', 'tingkat' => 'XI', 'per_halaman' => 10]))
            ->assertOk()
            ->assertSee($assigned->nama_siswa)
            ->assertSee('Total 1 siswa terdaftar');

        $this->get(route('dev.tim-inklusi.students.index', ['tingkat' => 'X']))
            ->assertOk()
            ->assertSee('Tidak ada data yang cocok');
    }

    public function test_student_history_can_be_filtered_by_note_source(): void
    {
        [$pendamping, $assigned] = $this->fixture();
        CatatanPendampingan::query()->forceCreate([
            'id_siswa' => $assigned->getKey(),
            'id_pembuat' => $pendamping->getKey(),
            'tanggal_catatan' => now()->toDateString(),
            'kegiatan_pendampingan' => 'Catatan sumber Tim Inklusi yang unik.',
            'status_pengajuan' => 'Draft',
            'versi_pengajuan' => 1,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->asTimInklusi($pendamping)
            ->get(route('dev.tim-inklusi.students.show', ['siswa' => $assigned, 'sumber' => 'pendampingan']))
            ->assertOk()
            ->assertSee('Guru Mata Pelajaran')
            ->assertSee('Tim Inklusi')
            ->assertSee('Catatan sumber Tim Inklusi yang unik.');

        $this->get(route('dev.tim-inklusi.students.show', ['siswa' => $assigned, 'sumber' => 'perkembangan']))
            ->assertOk()
            ->assertDontSee('Catatan sumber Tim Inklusi yang unik.');

        $this->get(route('dev.tim-inklusi.students.show', $assigned))
            ->assertOk()
            ->assertDontSee('Lihat Seluruh Riwayat Siswa');
    }

    public function test_draft_is_persisted_and_repeated_submission_is_idempotent(): void
    {
        [$pendamping, $assigned] = $this->fixture();
        $token = (string) Str::uuid();
        $payload = [
            'id_siswa' => $assigned->getKey(),
            'tanggal_catatan' => now()->toDateString(),
            'kegiatan_pendampingan' => 'Siswa mengikuti kegiatan dengan arahan.',
            'kendala' => 'Perlu pengulangan instruksi.',
            'tindak_lanjut' => 'Gunakan instruksi bertahap.',
            'aksi' => 'simpan',
            'idempotency_key' => $token,
        ];

        $this->asTimInklusi($pendamping)->post(route('dev.tim-inklusi.notes.store'), $payload)->assertRedirect();
        $this->post(route('dev.tim-inklusi.notes.store'), $payload)->assertRedirect();

        $this->assertDatabaseCount('catatan_pendampingan', 1);
        $this->assertDatabaseHas('catatan_pendampingan', [
            'id_pembuat' => $pendamping->getKey(),
            'id_siswa' => $assigned->getKey(),
            'status_pengajuan' => 'Draft',
            'idempotency_key' => $token,
        ]);
    }

    public function test_sent_note_is_locked_and_other_companion_cannot_access_it(): void
    {
        [$pendamping, $assigned] = $this->fixture();
        $other = $this->makeUser('Pendamping Lain', 'Tim Inklusi');
        RelasiPendampingSiswa::query()->create(['id_pengguna' => $other->getKey(), 'id_siswa' => $assigned->getKey()]);

        $this->asTimInklusi($pendamping)->post(route('dev.tim-inklusi.notes.store'), [
            'id_siswa' => $assigned->getKey(),
            'tanggal_catatan' => now()->toDateString(),
            'kegiatan_pendampingan' => 'Catatan yang dikirim.',
            'kendala' => null,
            'tindak_lanjut' => null,
            'aksi' => 'kirim',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $note = CatatanPendampingan::query()->firstOrFail();
        $this->assertSame('Dikirim', $note->status_pengajuan);
        $this->assertNotNull($note->tanggal_kirim);
        $this->get(route('dev.tim-inklusi.notes.edit', $note))->assertForbidden();

        $this->asTimInklusi($other)->get(route('dev.tim-inklusi.notes.show', $note))->assertForbidden();
    }

    public function test_returned_note_can_be_revised_and_resubmitted_with_new_version(): void
    {
        [$pendamping, $assigned] = $this->fixture();
        $gpk = $this->makeUser('GPK Sekolah', 'GPK');
        $note = CatatanPendampingan::query()->forceCreate([
            'id_siswa' => $assigned->getKey(),
            'id_pembuat' => $pendamping->getKey(),
            'tanggal_catatan' => now()->toDateString(),
            'kegiatan_pendampingan' => 'Catatan awal.',
            'status_pengajuan' => 'Perlu Revisi',
            'versi_pengajuan' => 1,
            'tanggal_kirim' => now(),
            'idempotency_key' => (string) Str::uuid(),
        ]);
        VerifikasiCatatan::query()->forceCreate([
            'id_catatan_pendampingan' => $note->getKey(),
            'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1,
            'status_verifikasi' => 'Perlu Revisi',
            'catatan_verifikasi' => 'Perjelas tindak lanjut.',
            'tanggal_verifikasi' => now(),
        ]);

        $this->asTimInklusi($pendamping)
            ->get(route('dev.tim-inklusi.notes.edit', $note))
            ->assertOk()
            ->assertSee('Perjelas tindak lanjut.');

        $this->put(route('dev.tim-inklusi.notes.update', $note), [
            'id_siswa' => $assigned->getKey(),
            'tanggal_catatan' => now()->toDateString(),
            'kegiatan_pendampingan' => 'Catatan telah diperjelas.',
            'kendala' => 'Kendala baru.',
            'tindak_lanjut' => 'Tindak lanjut terukur.',
            'aksi' => 'kirim',
        ])->assertRedirect(route('dev.tim-inklusi.notes.show', $note));

        $this->assertDatabaseHas('catatan_pendampingan', [
            'id_catatan_pendampingan' => $note->getKey(),
            'status_pengajuan' => 'Dikirim',
            'versi_pengajuan' => 2,
            'kegiatan_pendampingan' => 'Catatan telah diperjelas.',
        ]);
        $this->assertDatabaseHas('verifikasi_catatan', [
            'id_catatan_pendampingan' => $note->getKey(),
            'versi_pengajuan' => 1,
            'status_verifikasi' => 'Perlu Revisi',
        ]);
    }

    private function asTimInklusi(Pengguna $pendamping): static
    {
        return $this->withSession(['dev_role' => 'tim-inklusi', 'dev_pengguna_id' => $pendamping->getKey()]);
    }

    private function fixture(): array
    {
        $pendamping = $this->makeUser('Dimas Pendamping', 'Tim Inklusi');
        $classXi = Kelas::query()->create(['nama_kelas' => 'XI IPA 1', 'tingkat' => 'XI', 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif']);
        $classX = Kelas::query()->create(['nama_kelas' => 'X IPS 2', 'tingkat' => 'X', 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif']);
        $assigned = Siswa::query()->create(['id_kelas' => $classXi->getKey(), 'nis' => 'TI-001', 'nama_siswa' => 'Siswa Dampingan', 'kebutuhan_khusus' => 'Autisme', 'status_siswa' => 'Aktif']);
        $outside = Siswa::query()->create(['id_kelas' => $classX->getKey(), 'nis' => 'TI-002', 'nama_siswa' => 'Siswa Di Luar Penugasan', 'kebutuhan_khusus' => 'ADHD', 'status_siswa' => 'Aktif']);
        RelasiPendampingSiswa::query()->create(['id_pengguna' => $pendamping->getKey(), 'id_siswa' => $assigned->getKey()]);

        return [$pendamping, $assigned, $outside];
    }

    private function makeUser(string $name, string $roleName): Pengguna
    {
        $role = Role::query()->firstOrCreate(['nama_role' => $roleName]);

        return Pengguna::query()->create([
            'id_role' => $role->getKey(),
            'nama' => $name,
            'username' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'email' => Str::uuid().'@example.test',
            'password' => 'password',
            'status_akun' => 'Aktif',
        ]);
    }
}
