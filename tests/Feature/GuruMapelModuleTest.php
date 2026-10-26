<?php

namespace Tests\Feature;

use App\Models\CatatanPerkembangan;
use App\Models\Pengguna;
use App\Services\GpkVerifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuruMapelModuleTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $guru;

    private Pengguna $other;

    private Pengguna $gpk;

    private int $student;

    private int $outsider;

    private int $subject;

    private int $class;

    protected function setUp(): void
    {
        parent::setUp();
        $role = DB::table('role')->insertGetId(['nama_role' => 'Guru Mapel'], 'id_role');
        $gpkRole = DB::table('role')->insertGetId(['nama_role' => 'GPK'], 'id_role');
        $this->guru = Pengguna::create(['id_role' => $role, 'username' => 'demo.a16.guru1', 'nama' => 'DATA DEMO Guru Satu', 'nip' => 'DEMO-A16-GURU-01', 'email' => 'demo.a16.guru1@example.test', 'password' => 'testing-password', 'status_akun' => 'Aktif']);
        $this->other = Pengguna::create(['id_role' => $role, 'username' => 'demo.a16.guru2', 'nama' => 'DATA DEMO Guru Dua', 'nip' => 'DEMO-A16-GURU-02', 'email' => 'demo.a16.guru2@example.test', 'password' => 'testing-password', 'status_akun' => 'Aktif']);
        $this->gpk = Pengguna::create(['id_role' => $gpkRole, 'nama' => 'DATA DEMO GPK Uji', 'email' => 'gpk@example.test', 'password' => 'testing-password', 'status_akun' => 'Aktif']);
        $this->class = DB::table('kelas')->insertGetId(['nama_kelas' => 'DATA DEMO XI A', 'tingkat' => 'XI', 'tahun_ajaran' => '2026/2027'], 'id_kelas');
        $otherClass = DB::table('kelas')->insertGetId(['nama_kelas' => 'DATA DEMO X B', 'tingkat' => 'X', 'tahun_ajaran' => '2026/2027'], 'id_kelas');
        $this->subject = DB::table('mata_pelajaran')->insertGetId(['nama_mapel' => 'DATA DEMO Matematika'], 'id_mapel');
        DB::table('relasi_guru_mapel_kelas')->insert(['id_pengguna' => $this->guru->getKey(), 'id_mapel' => $this->subject, 'id_kelas' => $this->class]);
        $this->student = DB::table('siswa')->insertGetId(['id_kelas' => $this->class, 'nis' => 'DEMO-A16-001', 'nama_siswa' => 'DATA DEMO Siswa Terhubung'], 'id_siswa');
        $this->outsider = DB::table('siswa')->insertGetId(['id_kelas' => $otherClass, 'nis' => 'DEMO-A16-002', 'nama_siswa' => 'DATA DEMO Siswa Rahasia'], 'id_siswa');
    }

    private function loginGuru(): void
    {
        $this->actingAs($this->guru, 'guru_dev')->withSession(['dev_role' => 'guru-mapel', 'dev_guru_demo' => 'guru-1']);
    }

    private function payload(string $action = 'draft'): array
    {
        $this->withSession(['guru_note_creation_token' => 'test-token']);

        return ['creation_token' => 'test-token', 'aksi' => $action, 'id_mapel' => $this->subject, 'tanggal_catatan' => '2026-01-10', 'materi_kegiatan' => 'Materi Uji', 'kendala_peristiwa' => 'Pengamatan Uji', 'tindakan' => 'Tindakan Uji', 'hasil_tindakan' => 'Respons Uji'];
    }

    private function createNote(string $action = 'draft'): CatatanPerkembangan
    {
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $this->payload($action))->assertRedirect();

        return CatatanPerkembangan::orderByDesc('id_catatan_perkembangan')->firstOrFail();
    }

    private function token(CatatanPerkembangan $n): string
    {
        return hash('sha256', json_encode($n->fresh()->getRawOriginal()));
    }

    /** Optional private HTML artifacts contain only isolated synthetic test fixtures. */
    private function review(string $name, $response): void
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $nav = '//nav[@aria-label="Breadcrumb"]';
        $this->assertCount(1, $xpath->query($nav), $name);
        $this->assertCount(1, $xpath->query($nav.'//li[last()]/span[@aria-current="page"]'), $name);
        $this->assertCount(1, $xpath->query($nav.'/preceding-sibling::*[1][contains(@class,"abk-topbar")]'), $name);
        foreach ($xpath->query($nav.'//a') as $link) {
            $this->get($link->getAttribute('href'))->assertOk();
        }
        if (! getenv('A16_VISUAL_EXPORT')) {
            return;
        }
        $dir = storage_path('app/a16-review');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $html = $response->getContent();
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = '';
        foreach (['resources/css/app.css', 'resources/css/guru-mapel.css'] as $entry) {
            $css .= file_get_contents(public_path('build/'.$manifest[$entry]['file']));
        }
        $html = preg_replace('/<link[^>]*>|<script[^>]*>.*?<\/script>/s', '', $html);
        $html = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
        $html = preg_replace('/src="[^"]*\/images\/guru-mapel\/active.svg"/', 'src="data:image/svg+xml;base64,'.base64_encode(file_get_contents(public_path('images/guru-mapel/active.svg'))).'"', $html);
        $html = str_replace('</body>', '<div style="position:fixed;bottom:4px;right:12px;font-size:10px">DATA DEMO — database uji terisolasi</div></body>', $html);
        file_put_contents($dir.'/'.$name.'.html', $html);
    }

    public function test_role_selector_alone_cannot_read_or_write_real_data(): void
    {
        $this->withSession(['dev_role' => 'guru-mapel'])->get(route('dev.guru-mapel.students.index'))->assertRedirect(route('dev.login'));
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $this->payload())->assertRedirect(route('dev.login'));
        $this->assertDatabaseCount('catatan_perkembangan', 0);
    }

    public function test_dropdown_resolves_server_demo_and_clears_identity_on_role_change(): void
    {
        DB::table('relasi_guru_mapel_kelas')->insert(['id_pengguna' => $this->other->getKey(), 'id_mapel' => $this->subject, 'id_kelas' => $this->class]);
        $this->post(route('dev.login.store'), ['role' => 'guru-mapel', 'guru_mapel_id' => $this->other->getKey()])
            ->assertRedirect(route('dev.preview', 'guru-mapel'))->assertSessionHas('dev_guru_demo', 'guru-2');
        $this->assertAuthenticatedAs($this->other, 'guru_dev');
        $this->post(route('dev.change-role'))->assertRedirect()->assertSessionMissing('dev_guru_demo');
        $this->assertGuest('guru_dev');
    }

    public function test_sidebar_only_keeps_logout_and_role_change_actions(): void
    {
        $this->loginGuru();
        $response = $this->get(route('dev.guru-mapel.dashboard'))->assertOk();
        foreach (['dev.logout', 'dev.change-role'] as $route) {
            $response->assertSee('action="'.route($route).'"', false);
        }
        $response->assertDontSee('Ganti Guru Demo')
            ->assertSee('data-confirm-title="Keluar?"', false)
            ->assertSee('id="abkConfirmationModal"', false);
        $this->post(route('dev.change-role'))->assertRedirect(route('dev.login'))->assertSessionMissing('dev_role');
        $this->assertGuest('guru_dev');
        $this->loginGuru();
        $this->post(route('dev.logout'))->assertRedirect(route('dev.login'))->assertSessionMissing('dev_role');
        $this->assertGuest('guru_dev');
    }

    public function test_all_pages_render_and_student_search_and_level_are_scoped(): void
    {
        $this->loginGuru();
        $this->get(route('dev.guru-mapel.students.index'))->assertOk()->assertSee('Siswa Terhubung')->assertDontSee('Siswa Rahasia');
        $this->get(route('dev.guru-mapel.students.index', ['q' => 'absent']))->assertOk()->assertSee('Tidak ada data yang cocok');
        $this->get(route('dev.guru-mapel.students.index', ['tingkat' => 'X']))->assertOk()->assertDontSee('Siswa Terhubung');
        $this->get(route('dev.guru-mapel.students.index', ['tingkat' => 'XI', 'per_page' => 30]))->assertOk()->assertSee('Siswa Terhubung');
        foreach (['dashboard', 'notes.status', 'history.index', 'students.index'] as $page) {
            $response = $this->get(route('dev.guru-mapel.'.$page))->assertOk();
            $this->review($page, $response);
        }
        foreach (['students.show', 'notes.create', 'recommendations.show'] as $page) {
            $response = $this->get(route('dev.guru-mapel.'.$page, $this->student))->assertOk();
            $this->review($page, $response);
        }
        $this->get(route('dev.guru-mapel.students.show', $this->outsider))->assertNotFound();
        $this->get(route('dev.guru-mapel.recommendations.show', $this->student))->assertSee('Rekomendasi resmi belum tersedia');
    }

    public function test_draft_submission_revision_resubmission_and_verification_keep_decisions(): void
    {
        $this->loginGuru();
        $note = $this->createNote();
        $this->review('notes.draft', $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertOk());
        $this->assertSame('Draft', $note->status_pengajuan);
        $this->assertNull($note->tanggal_kirim);
        $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertOk()->assertSee('Draft Tersimpan');
        $this->get(route('dev.guru-mapel.notes.revise', $note->getKey()))->assertOk();
        $url = route('dev.guru-mapel.notes.update', $note->getKey());
        $this->put($url, $this->payload('kirim') + ['revision_token' => $this->token($note)])->assertRedirect();
        $this->assertSame('Dikirim', $note->fresh()->status_pengajuan);
        $this->assertNotNull($note->fresh()->tanggal_kirim);
        $this->review('notes.waiting', $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertOk());
        $this->put($url, $this->payload() + ['revision_token' => $this->token($note)])->assertStatus(409);
        app(GpkVerifikasiService::class)->verifikasi('perkembangan', $note->getKey(), $this->gpk->getKey(), 'Perlu Revisi', 'Perjelas respons.');
        $this->get(route('dev.guru-mapel.notes.revise', $note->getKey()))->assertOk()->assertSee('Perjelas respons.');
        $this->review('notes.revision', $this->get(route('dev.guru-mapel.notes.revise', $note->getKey()))->assertOk());
        $this->put($url, $this->payload() + ['revision_token' => $this->token($note)])->assertRedirect();
        $this->assertSame(2, $note->fresh()->versi_pengajuan);
        $this->assertSame('Draft', $note->fresh()->status_pengajuan);
        $this->review('notes.revision-draft', $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertOk());
        $this->put($url, $this->payload('kirim') + ['revision_token' => $this->token($note)])->assertRedirect();
        $this->assertSame(2, $note->fresh()->versi_pengajuan);
        app(GpkVerifikasiService::class)->verifikasi('perkembangan', $note->getKey(), $this->gpk->getKey(), 'Terverifikasi');
        $this->assertDatabaseCount('verifikasi_catatan', 2);
        $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertOk()->assertSee('Perjelas respons.')->assertSee('Dipublikasikan');
        $history = route('dev.guru-mapel.history.show', ['source' => 'perkembangan', 'id' => $note->getKey()]);
        $this->get($history)->assertOk()->assertSee('Materi Uji');
        $this->review('history.show', $this->get($history)->assertOk());
        $this->review('history.populated', $this->get(route('dev.guru-mapel.history.index'))->assertOk());
        $this->get(route('dev.guru-mapel.history.index'))->assertOk()->assertSee('Siswa Terhubung');
        $this->get(route('dev.guru-mapel.history.index', ['sumber' => 'pendampingan']))->assertOk()->assertDontSee('Siswa Terhubung');
        $this->get(route('dev.guru-mapel.notes.status', ['status' => 'Terverifikasi', 'q' => 'Terhubung']))->assertOk()->assertSee('Siswa Terhubung');
    }

    public function test_foreign_identity_subject_and_stale_updates_are_rejected(): void
    {
        $this->loginGuru();
        $this->post(route('dev.guru-mapel.notes.store', $this->outsider), $this->payload())->assertNotFound();
        $input = $this->payload();
        $input['id_mapel'] = 99999;
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $input)->assertForbidden();
        $input = $this->payload();
        $input['id_pembuat'] = $this->other->getKey();
        $input['status_pengajuan'] = 'Terverifikasi';
        $input['versi_pengajuan'] = 99;
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $input)->assertRedirect();
        $note = CatatanPerkembangan::firstOrFail();
        $this->assertEquals($this->guru->getKey(), $note->id_pembuat);
        $this->assertSame('Draft', $note->status_pengajuan);
        $this->assertSame(1, $note->versi_pengajuan);
        $this->put(route('dev.guru-mapel.notes.update', $note->getKey()), $this->payload() + ['revision_token' => 'stale'])->assertStatus(409);
        $this->actingAs($this->other, 'guru_dev')->withSession(['dev_guru_demo' => 'guru-2']);
        $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertNotFound();
        $this->put(route('dev.guru-mapel.notes.update', $note->getKey()), $this->payload())->assertNotFound();
    }

    public function test_archived_assignments_retain_read_access_and_block_writes_and_inactive_actor_is_denied(): void
    {
        $this->loginGuru();
        $note = $this->createNote();
        DB::table('kelas')->where('id_kelas', $this->class)->update(['status_kelas' => 'Arsip']);
        $this->get(route('dev.guru-mapel.students.show', $this->student))->assertOk();
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $this->payload())->assertForbidden();
        $this->guru->update(['status_akun' => 'Nonaktif']);
        $this->get(route('dev.guru-mapel.dashboard'))->assertForbidden();
    }

    public function test_send_requires_observation_action_and_response_but_draft_accepts_minimum(): void
    {
        $this->loginGuru();
        $this->withSession(['guru_note_creation_token' => 'test-token']);
        $data = ['creation_token' => 'test-token', 'aksi' => 'kirim', 'id_mapel' => $this->subject, 'tanggal_catatan' => '2026-01-10', 'materi_kegiatan' => 'Materi'];
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $data)->assertSessionHasErrors(['kendala_peristiwa', 'tindakan', 'hasil_tindakan']);
        $this->assertDatabaseCount('catatan_perkembangan', 0);
        $data['aksi'] = 'draft';
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $data)->assertRedirect();
        $this->assertDatabaseCount('catatan_perkembangan', 1);
    }

    public function test_double_submission_of_creation_form_is_rejected(): void
    {
        $this->loginGuru();
        $payload = $this->payload('kirim');
        $url = route('dev.guru-mapel.notes.store', $this->student);
        $this->post($url, $payload)->assertRedirect();
        $this->post($url, $payload)->assertStatus(409);
        $this->assertDatabaseCount('catatan_perkembangan', 1);
    }

    public function test_direct_resubmission_advances_once_and_old_publication_cannot_leak(): void
    {
        $this->loginGuru();
        $note = $this->createNote('kirim');
        $service = app(GpkVerifikasiService::class);
        $service->verifikasi('perkembangan', $note->getKey(), $this->gpk->getKey(), 'Perlu Revisi', 'Lengkapi.');
        $payload = $this->payload('kirim') + ['revision_token' => $this->token($note)];
        $this->put(route('dev.guru-mapel.notes.update', $note->getKey()), $payload)->assertRedirect();
        $this->assertSame(2, $note->fresh()->versi_pengajuan);
        $service->verifikasi('perkembangan', $note->getKey(), $this->gpk->getKey(), 'Terverifikasi');
        // A stale published decision is insufficient for a different current version.
        $note->fresh()->forceFill(['versi_pengajuan' => 3])->save();
        $this->get(route('dev.guru-mapel.history.show', ['source' => 'perkembangan', 'id' => $note->getKey()]))->assertNotFound();
        $this->get(route('dev.guru-mapel.notes.revise', $note->getKey()))->assertStatus(409);
    }

    public function test_inclusion_history_requires_publication_and_assigned_student(): void
    {
        $this->loginGuru();
        $role = DB::table('role')->insertGetId(['nama_role' => 'Tim Inklusi'], 'id_role');
        $actor = Pengguna::create(['id_role' => $role, 'nama' => 'DATA DEMO Pendamping Uji', 'email' => 'tim@example.test', 'password' => 'testing-password', 'status_akun' => 'Aktif']);
        $ids = [];
        foreach ([$this->student, $this->outsider] as $student) {
            $id = DB::table('catatan_pendampingan')->insertGetId(['id_siswa' => $student, 'id_pembuat' => $actor->getKey(), 'tanggal_catatan' => '2026-01-10', 'kegiatan_pendampingan' => 'Kegiatan Tim Uji', 'kendala' => 'Kendala uji', 'tindak_lanjut' => 'Lanjut uji', 'status_pengajuan' => 'Dikirim', 'versi_pengajuan' => 1], 'id_catatan_pendampingan');
            app(GpkVerifikasiService::class)->verifikasi('pendampingan', $id, $this->gpk->getKey(), 'Terverifikasi');
            $ids[] = $id;
        }
        $response = $this->get(route('dev.guru-mapel.history.show', ['source' => 'pendampingan', 'id' => $ids[0]]))->assertOk()->assertSee('Kegiatan Tim Uji')->assertSee('Lanjut uji');
        $this->review('history.inclusion', $response);
        $this->get(route('dev.guru-mapel.history.show', ['source' => 'pendampingan', 'id' => $ids[1]]))->assertNotFound();
        $this->get(route('dev.guru-mapel.history.index', ['sumber' => 'pendampingan']))->assertOk()->assertSee('Siswa Terhubung')->assertDontSee('Siswa Rahasia');
        $this->get(route('dev.guru-mapel.history.index', ['q' => 'absent']))->assertOk()->assertDontSee('Siswa Terhubung');
    }

    public function test_removed_assignment_immediately_revokes_access_and_pagination_preserves_filters(): void
    {
        $this->loginGuru();
        for ($i = 0; $i < 12; $i++) {
            DB::table('siswa')->insert(['id_kelas' => $this->class, 'nis' => 'DEMO-A16-PAGE-'.$i, 'nama_siswa' => 'DATA DEMO Siswa Tambahan '.$i]);
        }
        $response = $this->get(route('dev.guru-mapel.students.index', ['q' => 'Siswa', 'tingkat' => 'XI', 'per_page' => 10]))->assertOk();
        $this->assertSame(13, $response->viewData('students')->total());
        $this->assertSame(10, $response->viewData('students')->count());
        $this->assertStringContainsString('tingkat=XI', $response->viewData('students')->nextPageUrl());
        $this->get(route('dev.guru-mapel.students.index', ['q' => 'Siswa', 'tingkat' => 'XI', 'page' => 2]))->assertOk()->assertViewHas('students', fn ($s) => $s->count() === 3);
        $note = $this->createNote();
        DB::table('relasi_guru_mapel_kelas')->where('id_pengguna', $this->guru->getKey())->delete();
        $this->get(route('dev.guru-mapel.students.show', $this->student))->assertNotFound();
        $this->get(route('dev.guru-mapel.notes.show', $note->getKey()))->assertNotFound();
        $this->post(route('dev.guru-mapel.notes.store', $this->student), $this->payload())->assertNotFound();
    }
}
