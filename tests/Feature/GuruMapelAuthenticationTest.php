<?php

namespace Tests\Feature;

use App\Models\CatatanPerkembangan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuruMapelAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private int $guruRole;

    private int $gpkRole;

    private int $class;

    private int $otherClass;

    private int $subject;

    private Pengguna $guru;

    private Pengguna $otherGuru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guruRole = DB::table('role')->insertGetId(['nama_role' => 'Guru Mapel'], 'id_role');
        $this->gpkRole = DB::table('role')->insertGetId(['nama_role' => 'GPK'], 'id_role');
        $this->guru = $this->user($this->guruRole, 'guru.budi', 'Budi Santoso', 'budi@sekolah.test');
        $this->otherGuru = $this->user($this->guruRole, 'guru.siti', 'Siti Rahayu', 'siti@sekolah.test');
        $this->class = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'VII A', 'tingkat' => 'VII', 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif',
        ], 'id_kelas');
        $this->otherClass = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'VIII A', 'tingkat' => 'VIII', 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif',
        ], 'id_kelas');
        $this->subject = DB::table('mata_pelajaran')->insertGetId([
            'nama_mapel' => 'Matematika', 'status_mapel' => 'Aktif',
        ], 'id_mapel');
        DB::table('relasi_guru_mapel_kelas')->insert([
            ['id_pengguna' => $this->guru->getKey(), 'id_mapel' => $this->subject, 'id_kelas' => $this->class],
            ['id_pengguna' => $this->otherGuru->getKey(), 'id_mapel' => $this->subject, 'id_kelas' => $this->otherClass],
        ]);
    }

    private function user(int $role, string $username, string $name, string $email, string $status = 'Aktif'): Pengguna
    {
        return Pengguna::create([
            'id_role' => $role,
            'username' => $username,
            'nama' => $name,
            'email' => $email,
            'password' => 'password-rahasia',
            'status_akun' => $status,
        ]);
    }

    private function login(string $identity = 'guru.budi', string $password = 'password-rahasia')
    {
        return $this->post(route('guru-mapel.login.store'), [
            'login' => $identity,
            'password' => $password,
        ]);
    }

    public function test_google_login_page_uses_shared_role_composition(): void
    {
        $this->get('/guru-mapel/masuk')
            ->assertOk()
            ->assertSee('Masuk Guru Mata Pelajaran')
            ->assertSee('Selamat datang, Guru Mata Pelajaran')
            ->assertSee('Masuk dengan Google')
            ->assertSee('abk-parent-access-frame', false)
            ->assertSee(route('auth.google.role.redirect', 'guru-mapel'), false);
    }

    public function test_teacher_can_login_with_username_or_email_and_profile_comes_from_database(): void
    {
        $this->login()->assertRedirect(route('guru-mapel.dashboard'));
        $this->assertAuthenticatedAs($this->guru, 'guru_mapel');
        $this->assertGuest('guru_dev');
        $this->get(route('guru-mapel.dashboard'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Ganti Peran')
            ->assertSee('action="'.route('guru-mapel.logout').'"', false);

        $this->post(route('guru-mapel.logout'))->assertRedirect(route('guru-mapel.login'));
        $this->assertGuest('guru_mapel');

        $this->login('budi@sekolah.test')->assertRedirect(route('guru-mapel.dashboard'));
        $this->assertAuthenticatedAs($this->guru, 'guru_mapel');
    }

    public function test_invalid_password_and_unknown_identity_are_rejected(): void
    {
        $this->login('guru.budi', 'salah')->assertSessionHasErrors('login');
        $this->assertGuest('guru_mapel');
        $this->login('tidak.ada')->assertSessionHasErrors('login');
        $this->assertGuest('guru_mapel');
    }

    public function test_inactive_and_non_teacher_accounts_are_rejected(): void
    {
        $inactive = $this->user($this->guruRole, 'guru.nonaktif', 'Guru Nonaktif', 'nonaktif@sekolah.test', 'Nonaktif');
        $gpk = $this->user($this->gpkRole, 'gpk.sekolah', 'GPK Sekolah', 'gpk@sekolah.test');

        $this->login($inactive->username)->assertSessionHasErrors('login');
        $this->assertGuest('guru_mapel');
        $this->login($gpk->username)->assertSessionHasErrors('login');
        $this->assertGuest('guru_mapel');
    }

    public function test_dashboard_requires_real_teacher_authentication(): void
    {
        $this->get(route('guru-mapel.dashboard'))->assertRedirect(route('guru-mapel.login'));

        $this->actingAs($this->guru, 'guru_dev')
            ->withSession(['dev_role' => 'guru-mapel', 'dev_pengguna_id' => $this->guru->getKey()])
            ->get(route('guru-mapel.dashboard'))
            ->assertRedirect(route('guru-mapel.login'));
    }

    public function test_teacher_only_sees_assigned_students_and_can_create_a_note(): void
    {
        $student = DB::table('siswa')->insertGetId([
            'id_kelas' => $this->class, 'nis' => '26070001', 'nama_siswa' => 'Siswa Budi', 'status_siswa' => 'Aktif',
        ], 'id_siswa');
        $outsider = DB::table('siswa')->insertGetId([
            'id_kelas' => $this->otherClass, 'nis' => '26080001', 'nama_siswa' => 'Siswa Siti', 'status_siswa' => 'Aktif',
        ], 'id_siswa');

        $this->login();
        $this->get(route('guru-mapel.students.index'))
            ->assertOk()
            ->assertSee('Siswa Budi')
            ->assertDontSee('Siswa Siti');
        $this->get(route('guru-mapel.students.show', $outsider))->assertNotFound();

        $this->withSession(['guru_note_creation_token' => 'real-login-token'])
            ->post(route('guru-mapel.notes.store', $student), [
                'creation_token' => 'real-login-token',
                'aksi' => 'draft',
                'tanggal_catatan' => '2026-10-01',
                'materi_kegiatan' => 'Pecahan sederhana',
            ])->assertRedirect();

        $note = CatatanPerkembangan::firstOrFail();
        $this->assertSame($this->guru->getKey(), $note->id_pembuat);
        $this->assertSame($this->subject, $note->id_mapel);
        $this->assertSame('Draft', $note->status_pengajuan);

        $this->post(route('guru-mapel.logout'));
        $this->login('guru.siti');
        $this->get(route('guru-mapel.students.index'))
            ->assertOk()
            ->assertSee('Siswa Siti')
            ->assertDontSee('Siswa Budi');
        $this->get(route('guru-mapel.students.show', $student))->assertNotFound();
    }

    public function test_teacher_without_an_assignment_can_login_without_receiving_automatic_data(): void
    {
        $unassigned = $this->user($this->guruRole, 'guru.baru', 'Guru Baru', 'baru@sekolah.test');

        $this->login($unassigned->username)->assertRedirect(route('guru-mapel.dashboard'));
        $this->get(route('guru-mapel.dashboard'))
            ->assertOk()
            ->assertSee('Guru Baru')
            ->assertSee('Belum ada catatan perkembangan.');
        $this->get(route('guru-mapel.students.index'))
            ->assertOk()
            ->assertSee('Total 0 siswa terdaftar');
    }

    public function test_development_login_still_uses_its_own_guard(): void
    {
        $this->post(route('dev.login.store'), [
            'role' => 'guru-mapel',
            'guru_mapel_id' => $this->guru->getKey(),
        ])->assertRedirect(route('dev.preview', 'guru-mapel'));

        $this->assertAuthenticatedAs($this->guru, 'guru_dev');
        $this->assertGuest('guru_mapel');
        $this->get(route('dev.guru-mapel.dashboard'))->assertOk()->assertSee('Ganti Peran');
    }
}
