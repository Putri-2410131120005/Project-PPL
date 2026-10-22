<?php

namespace Tests\Feature;

use App\Models\CatatanPerkembangan;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\Siswa;
use App\Services\GuruMapelAccess;
use Database\Seeders\A16DemoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class A16DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $credentialsFile;

    private array $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentialsFile = tempnam(sys_get_temp_dir(), 'a16-test-');
        $this->credentials = array_fill_keys(['demo.a16.guru1', 'demo.a16.guru2', 'demo.a16.gpk'], bin2hex(random_bytes(16)));
        file_put_contents($this->credentialsFile, json_encode($this->credentials));
        putenv('A16_DEMO_CREDENTIALS_FILE='.$this->credentialsFile);
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        putenv('A16_DEMO_CREDENTIALS_FILE');
        unlink($this->credentialsFile);
        parent::tearDown();
    }

    public function test_demo_is_idempotent_hashed_and_scoped_and_pages_use_database(): void
    {
        $this->seed(A16DemoSeeder::class);
        $before = Pengguna::orderBy('id_pengguna')->get()->map->getRawOriginal()->all();
        $this->seed(A16DemoSeeder::class);
        $this->assertSame($before, Pengguna::orderBy('id_pengguna')->get()->map->getRawOriginal()->all());
        foreach (['pengguna' => 3, 'kelas' => 3, 'mata_pelajaran' => 2, 'siswa' => 3, 'relasi_kelas_mapel' => 3, 'relasi_guru_mapel_kelas' => 3, 'catatan_perkembangan' => 0, 'verifikasi_catatan' => 0] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $one = Pengguna::where('username', 'demo.a16.guru1')->firstOrFail();
        $two = Pengguna::where('username', 'demo.a16.guru2')->firstOrFail();
        $this->assertTrue(Hash::check($this->credentials[$one->username], $one->password));
        $access = app(GuruMapelAccess::class);
        $idsOne = $access->students($one)->pluck('id_siswa')->all();
        $idsTwo = $access->students($two)->pluck('id_siswa')->all();
        $this->assertCount(2, $idsOne);
        $this->assertCount(1, $idsTwo);
        $this->assertSame([], array_intersect($idsOne, $idsTwo));
        foreach ([$one, $two] as $teacher) {
            $this->post(route('dev.login.store'), ['role' => 'guru-mapel'])->assertRedirect(route('dev.preview', 'guru-mapel'));
            if ($teacher->is($two)) {
                $this->post(route('dev.guru-mapel.demo.switch'))->assertRedirect(route('dev.guru-mapel.dashboard'));
            }
            $own = $access->students($teacher)->firstOrFail();
            $foreign = $access->students($teacher->is($one) ? $two : $one)->firstOrFail();
            $this->get(route('dev.guru-mapel.students.index'))->assertOk()->assertSee($own->nama_siswa)->assertDontSee($foreign->nama_siswa);
            $this->get(route('dev.guru-mapel.students.show', $own->getKey()))->assertOk();
            $this->get(route('dev.guru-mapel.notes.create', $own->getKey()))->assertOk();
            $this->get(route('dev.guru-mapel.students.show', $foreign->getKey()))->assertNotFound();
            $this->get(route('dev.guru-mapel.notes.create', $foreign->getKey()))->assertNotFound();
            $this->post(route('dev.logout'))->assertRedirect();
        }
        $this->withSession(['dev_role' => 'admin'])->get(route('dev.admin.students.index'))->assertOk()->assertSee('DATA DEMO Siswa Satu');
    }

    public function test_conflict_rolls_back_instead_of_overwriting_existing_student(): void
    {
        $this->seed(A16DemoSeeder::class);
        $student = Siswa::where('nis', 'DEMO-A16-001')->firstOrFail();
        $student->update(['nama_siswa' => 'Existing record changed by owner']);
        try {
            $this->seed(A16DemoSeeder::class);
            $this->fail('Conflict was not rejected');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Konflik', $e->getMessage());
        }
        $this->assertSame('Existing record changed by owner', $student->fresh()->nama_siswa);
        $this->assertDatabaseCount('siswa', 3);
    }

    public function test_production_and_additional_teacher_access_are_rejected(): void
    {
        $this->seed(A16DemoSeeder::class);
        $users = Pengguna::whereHas('role', fn ($q) => $q->where('nama_role', 'Guru Mapel'))->orderBy('id_pengguna')->get();
        $assignment = $users[1]->relasiGuruMapelKelas()->firstOrFail();
        $users[0]->relasiGuruMapelKelas()->create(['id_mapel' => $assignment->id_mapel, 'id_kelas' => $assignment->id_kelas]);
        try {
            $this->seed(A16DemoSeeder::class);
            $this->fail('Additional access was not rejected');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Penugasan tambahan', $e->getMessage());
        }
        $this->app['env'] = 'production';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('local/testing');
        (new A16DemoSeeder)->run();
    }

    public function test_all_six_dropdown_roles_keep_their_dashboard_without_extra_credentials(): void
    {
        $this->seed(A16DemoSeeder::class);
        foreach (['admin', 'guru-mapel', 'tim-inklusi', 'gpk', 'kepala-sekolah', 'orang-tua'] as $role) {
            $this->post(route('dev.login.store'), ['role' => $role])->assertRedirect(route('dev.preview', $role));
            if ($role === 'guru-mapel') {
                $this->get(route('dev.preview', $role))->assertRedirect(route('dev.guru-mapel.dashboard'));
                $this->get(route('dev.guru-mapel.dashboard'))->assertOk()->assertSee('DATA DEMO Guru Satu')->assertDontSee('type="password"', false);
            } else {
                $this->get(route('dev.preview', $role))->assertOk();
                $this->assertGuest('guru_dev');
                $this->assertFalse(session()->has('dev_guru_demo'));
            }
        }
    }

    public function test_missing_demo_configuration_and_legacy_login_do_not_allow_access(): void
    {
        $this->from(route('dev.login'))->post(route('dev.login.store'), ['role' => 'guru-mapel'])
            ->assertRedirect(route('dev.login'))->assertSessionHasErrors('role');
        $this->get(route('dev.login'))->assertSee('Konfigurasi development');
        $this->assertGuest('guru_dev');
        $this->get('/_dev/guru-mapel/masuk')->assertRedirect(route('dev.login'));
        $this->post('/_dev/guru-mapel/masuk', ['identity' => 'anything', 'password' => 'anything'])->assertStatus(405);
        $this->get(route('dev.guru-mapel.students.index'))->assertRedirect(route('dev.login'));
    }

    public function test_dropdown_actor_owns_draft_and_submission_and_switch_clears_form_tokens(): void
    {
        $this->seed(A16DemoSeeder::class);
        $one = Pengguna::where('username', 'demo.a16.guru1')->firstOrFail();
        $two = Pengguna::where('username', 'demo.a16.guru2')->firstOrFail();
        $assignment = $one->relasiGuruMapelKelas()->firstOrFail();
        $student = Siswa::where('id_kelas', $assignment->id_kelas)->firstOrFail();
        $this->post(route('dev.login.store'), ['role' => 'guru-mapel', 'id_pengguna' => $two->getKey()])->assertRedirect();
        $this->get(route('dev.guru-mapel.notes.create', $student->getKey()))->assertOk();
        $data = ['aksi' => 'draft', 'creation_token' => session('guru_note_creation_token'), 'id_pembuat' => $two->getKey(), 'id_mapel' => $assignment->id_mapel, 'tanggal_catatan' => '2026-01-10', 'materi_kegiatan' => 'Materi demo', 'kendala_peristiwa' => 'Pengamatan demo', 'tindakan' => 'Tindakan demo', 'hasil_tindakan' => 'Respons demo'];
        $this->post(route('dev.guru-mapel.notes.store', $student->getKey()), $data)->assertRedirect();
        $note = CatatanPerkembangan::firstOrFail();
        $this->assertEquals($one->getKey(), $note->id_pembuat);
        $this->assertSame('Draft', $note->status_pengajuan);
        $data['aksi'] = 'kirim';
        $data['revision_token'] = hash('sha256', json_encode($note->getRawOriginal()));
        $this->put(route('dev.guru-mapel.notes.update', $note->getKey()), $data)->assertRedirect();
        $this->assertSame('Dikirim', $note->fresh()->status_pengajuan);
        $this->assertNotNull($note->fresh()->tanggal_kirim);
        $this->withSession(['guru_note_creation_token' => 'old-form']);
        $this->post(route('dev.guru-mapel.demo.switch'), ['id_pengguna' => 999999])->assertRedirect()->assertSessionHas('dev_guru_demo', 'guru-2')->assertSessionMissing('guru_note_creation_token');
        $this->assertAuthenticatedAs($two, 'guru_dev');
        $this->get(route('dev.guru-mapel.students.show', $student->getKey()))->assertNotFound();
        $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertNotFound();
        $this->post(route('dev.logout'))->assertRedirect()->assertSessionMissing('dev_guru_demo');
        $this->assertGuest('guru_dev');
    }

    public function test_real_student_and_real_subject_cannot_be_read_or_written_by_demo_login(): void
    {
        $this->seed(A16DemoSeeder::class);
        $one = Pengguna::where('username', 'demo.a16.guru1')->firstOrFail();
        $assignment = $one->relasiGuruMapelKelas()->firstOrFail();
        $real = Siswa::create(['id_kelas' => $assignment->id_kelas, 'nis' => 'REAL-NON-DEMO', 'nama_siswa' => 'Restricted Student', 'status_siswa' => 'Aktif']);
        $realSubject = MataPelajaran::create(['nama_mapel' => 'Restricted Subject', 'status_mapel' => 'Aktif']);
        $one->relasiGuruMapelKelas()->create(['id_kelas' => $assignment->id_kelas, 'id_mapel' => $realSubject->getKey()]);
        $this->post(route('dev.login.store'), ['role' => 'guru-mapel'])->assertRedirect();
        $this->get(route('dev.guru-mapel.students.index'))->assertOk()->assertDontSee('Restricted Student');
        $this->get(route('dev.guru-mapel.students.show', $real->getKey()))->assertNotFound();
        $this->get(route('dev.guru-mapel.notes.create', $real->getKey()))->assertNotFound();
        $student = Siswa::where('nis', 'DEMO-A16-001')->firstOrFail();
        $this->get(route('dev.guru-mapel.notes.create', $student->getKey()))->assertOk()->assertDontSee('Restricted Subject');
        $this->post(route('dev.guru-mapel.notes.store', $student->getKey()), ['aksi' => 'draft', 'creation_token' => session('guru_note_creation_token'), 'tanggal_catatan' => '2026-01-10', 'materi_kegiatan' => 'Demo', 'id_mapel' => $realSubject->getKey()])->assertForbidden();
        $this->assertDatabaseCount('catatan_perkembangan', 0);
    }

    public function test_demo_endpoints_are_denied_in_production_even_with_a_stale_session(): void
    {
        $this->seed(A16DemoSeeder::class);
        $this->post(route('dev.login.store'), ['role' => 'guru-mapel'])->assertRedirect();
        $this->app['env'] = 'production';
        $this->get(route('dev.guru-mapel.dashboard'))->assertNotFound();
        $this->withSession(['_token' => 'production-csrf'])->post(route('dev.guru-mapel.demo.switch'), ['_token' => 'production-csrf'])->assertNotFound();
    }
}
