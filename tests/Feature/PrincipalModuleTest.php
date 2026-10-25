<?php

namespace Tests\Feature;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\LaporanPerkembangan;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\RekapPerkembangan;
use App\Models\Role;
use App\Models\Siswa;
use App\Services\GpkVerifikasiService;
use App\Services\PrincipalReports;
use Database\Seeders\A16DemoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class PrincipalModuleTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as private refreshIsolatedDatabase;
    }

    public function refreshDatabase(): void
    {
        $connection = config('database.default');
        if ($connection !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('A19 tests require SQLite :memory: before RefreshDatabase can run.');
        }
        $this->refreshIsolatedDatabase();
    }

    private Pengguna $gpk;

    private Pengguna $guru;

    private Siswa $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $path = tempnam(sys_get_temp_dir(), 'a19-fixture-');
        file_put_contents($path, json_encode(array_fill_keys(['demo.a16.guru1', 'demo.a16.guru2', 'demo.a16.gpk'], bin2hex(random_bytes(20)))));
        putenv('A16_DEMO_CREDENTIALS_FILE='.$path);
        try {
            $this->seed([RoleSeeder::class, A16DemoSeeder::class]);
        } finally {
            putenv('A16_DEMO_CREDENTIALS_FILE');
            unlink($path);
        }
        $this->gpk = Pengguna::where('username', 'demo.a16.gpk')->sole();
        $this->guru = Pengguna::where('username', 'demo.a16.guru1')->sole();
        $this->student = Siswa::where('nis', 'DEMO-A16-001')->sole();
        $this->withSession(['dev_role' => 'kepala-sekolah']);
    }

    private function official(?Siswa $student = null, string $period = 'September 2026', string $date = '2026-09-25'): array
    {
        $student ??= $this->student;
        $note = new CatatanPerkembangan;
        $note->forceFill(['id_siswa' => $student->getKey(), 'id_pembuat' => $this->guru->getKey(),
            'id_mapel' => MataPelajaran::where('nama_mapel', 'DATA DEMO Matematika')->sole()->getKey(),
            'tanggal_catatan' => $date, 'materi_kegiatan' => 'DATA DEMO Materi pecahan',
            'kendala_peristiwa' => 'DATA DEMO Peristiwa sumber', 'tindakan' => 'DATA DEMO Tindakan guru',
            'hasil_tindakan' => 'DATA DEMO Hasil tindakan', 'catatan_tambahan' => '=SUM(1,2)',
            'status_pengajuan' => 'Dikirim', 'versi_pengajuan' => 1])->save();
        $service = app(GpkVerifikasiService::class);
        $verification = $service->verifikasi('perkembangan', $note->getKey(), $this->gpk->getKey(), 'Terverifikasi');
        $service->publikasikan($verification->getKey(), $this->gpk->getKey());
        $verification->refresh();
        $recap = RekapPerkembangan::create(['id_siswa' => $student->getKey(), 'id_pembuat' => $this->gpk->getKey(),
            'periode' => $period, 'ringkasan_perkembangan' => 'DATA DEMO Ringkasan resmi '.$student->nis, 'tanggal_rekap' => $date]);
        $link = $recap->sumberCatatan()->create(['id_catatan_perkembangan' => $note->getKey()]);
        $report = new LaporanPerkembangan;
        $report->forceFill(['id_rekap' => $recap->getKey(), 'id_pembuat' => $this->gpk->getKey(), 'periode' => $period,
            'isi_laporan' => 'DATA DEMO Isi laporan resmi '.$student->nis, 'tanggal_laporan' => $date,
            'status_laporan' => 'Tersedia', 'id_publikator' => $this->gpk->getKey(), 'tanggal_publikasi' => $date])->save();

        return compact('note', 'verification', 'recap', 'link', 'report');
    }

    public function test_dropdown_empty_states_and_read_only_routes(): void
    {
        $this->post(route('dev.login.store'), ['role' => 'kepala-sekolah'])->assertRedirect(route('dev.preview', 'kepala-sekolah'));
        $this->get(route('dev.preview', 'kepala-sekolah'))->assertRedirect(route('dev.principal.dashboard'));
        foreach (['dashboard', 'students', 'recaps', 'archives', 'logout'] as $page) {
            $response = $this->get(route('dev.principal.'.$page))->assertOk()->assertSee('DATA DEMO');
            $this->review('empty-'.$page, $response);
        }
        $this->get(route('dev.principal.dashboard'))->assertSee('Belum ada laporan resmi');
        $this->get(route('dev.principal.recaps.export', 'zip'))->assertNotFound();
        $this->get(route('dev.principal.period', ['periode' => 'September 2026']))->assertNotFound();
        $this->assertDatabaseCount('rekap_perkembangan', 0);
        $this->assertDatabaseCount('catatan_perkembangan', 0);
        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->$method(route('dev.principal.recaps'))->assertStatus(405);
        }
    }

    public function test_role_guard_also_protects_exports_and_is_disabled_in_production(): void
    {
        foreach (['admin', 'guru-mapel', 'gpk', 'tim-inklusi', 'orang-tua'] as $role) {
            $this->withSession(['dev_role' => $role]);
            foreach (['dashboard', 'students', 'recaps', 'archives'] as $page) {
                $this->get(route('dev.principal.'.$page))->assertForbidden();
            }
            $this->get(route('dev.principal.recaps.export', 'zip'))->assertForbidden();
            $this->get(route('dev.principal.recap.export', ['rekap' => 1, 'format' => 'pdf']))->assertForbidden();
            $this->get(route('dev.principal.period.export', ['format' => 'xlsx', 'periode' => 'September 2026']))->assertForbidden();
        }
        $this->flushSession();
        $this->get(route('dev.principal.dashboard'))->assertRedirect(route('dev.login'));
        $this->app->detectEnvironment(fn () => 'production');
        $this->withSession(['dev_role' => 'kepala-sekolah'])->get(route('dev.principal.dashboard'))->assertNotFound();
    }

    public function test_database_student_search_class_filter_and_real_students_are_private(): void
    {
        $other = Siswa::where('nis', 'DEMO-A16-003')->sole();
        $real = Siswa::create(['id_kelas' => $this->student->id_kelas, 'nis' => 'REAL-PRIVATE', 'nama_siswa' => 'Private student']);
        $this->get(route('dev.principal.students'))->assertOk()->assertSee($this->student->nama_siswa)->assertSee($other->nama_siswa)->assertDontSee('Private student');
        $this->get(route('dev.principal.students', ['tingkat' => 'X']))->assertSee($this->student->nama_siswa)->assertDontSee($other->nama_siswa);
        $this->get(route('dev.principal.students', ['q' => 'Tiga']))->assertSee($other->nama_siswa)->assertDontSee($this->student->nama_siswa);
        $this->get(route('dev.principal.student', $real->getKey()))->assertNotFound();
        $this->getJson(route('dev.principal.students', ['tingkat' => 'INVALID']))->assertUnprocessable();
        $this->getJson(route('dev.principal.archives', ['bulan' => 13]))->assertUnprocessable();
    }

    public function test_individual_reports_sources_and_filters_use_relations(): void
    {
        $f = $this->official();
        $other = $this->official(Siswa::where('nis', 'DEMO-A16-003')->sole(), 'Agustus 2025', '2025-08-20');
        $inclusion = Pengguna::create(['id_role' => Role::where('nama_role', 'Tim Inklusi')->sole()->getKey(), 'nama' => 'DATA DEMO Pendamping', 'email' => 'pendamping@example.test', 'password' => bin2hex(random_bytes(20)), 'status_akun' => 'Aktif']);
        $note = new CatatanPendampingan;
        $note->forceFill(['id_siswa' => $this->student->getKey(), 'id_pembuat' => $inclusion->getKey(), 'tanggal_catatan' => '2026-09-24', 'kegiatan_pendampingan' => 'DATA DEMO Pendampingan lengkap', 'tindak_lanjut' => 'DATA DEMO Tindak lanjut', 'kendala' => 'DATA DEMO Kendala', 'status_pengajuan' => 'Dikirim', 'versi_pengajuan' => 1])->save();
        $svc = app(GpkVerifikasiService::class);
        $v = $svc->verifikasi('pendampingan', $note->getKey(), $this->gpk->getKey(), 'Terverifikasi');
        $svc->publikasikan($v->getKey(), $this->gpk->getKey());
        $link = $f['recap']->sumberCatatan()->create(['id_catatan_pendampingan' => $note->getKey()]);
        foreach (['recap', 'semester', 'recap.preview'] as $page) {
            $r = $this->get(route('dev.principal.'.$page, $f['recap']->getKey()))->assertOk()->assertSee($this->student->nama_siswa);
            $this->review($page, $r);
        }
        foreach ([$f['link'], $link] as $i => $source) {
            $r = $this->get(route('dev.principal.note', ['rekap' => $f['recap']->getKey(), 'link' => $source->getKey()]))->assertOk()->assertSee('DATA DEMO Guru Satu')->assertSee('DATA DEMO Matematika')->assertSee('DATA DEMO Pendamping');
            $this->review('source-'.$i, $r);
        }
        $this->get(route('dev.principal.note', ['rekap' => $f['recap']->getKey(), 'link' => $other['link']->getKey()]))->assertNotFound();
        $this->get(route('dev.principal.recaps', ['periode' => 'September 2026', 'tahun_ajaran' => '2026/2027', 'tingkat' => 'X']))->assertSee($this->student->nama_siswa)->assertDontSee($other['recap']->siswa->nama_siswa);
        $this->get(route('dev.principal.recaps', ['tahun_ajaran' => '1999/2000']))->assertDontSee($this->student->nama_siswa);
        foreach (['dashboard', 'students', 'recaps', 'archives', 'logout'] as $page) {
            $this->review($page, $this->get(route('dev.principal.'.$page))->assertOk());
        }
        $this->review('student', $this->get(route('dev.principal.student', $this->student->getKey()))->assertOk());
        $this->review('period', $this->get(route('dev.principal.period', ['periode' => 'September 2026']))->assertOk());
    }

    public function test_archive_filters_use_report_dates_and_period_is_per_student(): void
    {
        $a = $this->official();
        $this->official(Siswa::where('nis', 'DEMO-A16-003')->sole(), 'Agustus 2025', '2025-08-20');
        $this->get(route('dev.principal.archives'))->assertSee('September 2026')->assertSee('Agustus 2025');
        foreach ([['bulan' => 9], ['tahun' => 2026], ['bulan' => 9, 'tahun' => 2026]] as $filter) {
            $this->get(route('dev.principal.archives', $filter))->assertSee('September 2026')->assertDontSee('Agustus 2025');
        }
        $this->get(route('dev.principal.archives', ['bulan' => 1]))->assertSee('Belum ada laporan resmi');
        $r = $this->get(route('dev.principal.period', ['periode' => 'September 2026', 'siswa' => $this->student->getKey()]))->assertOk()->assertSee($a['report']->isi_laporan)->assertDontSee('DEMO-A16-003');
        $this->review('period-filter', $r);
    }

    public function test_multiple_reports_do_not_double_count_sources_and_note_text_is_escaped(): void
    {
        $f = $this->official();
        $copy = $f['report']->replicate();
        $copy->save();
        $this->get(route('dev.principal.dashboard'))->assertOk()->assertViewHas('archive', fn ($a) => (int) $a->student_count === 1 && (int) $a->note_count === 1 && (int) $a->report_count === 2);
        $f['note']->forceFill(['materi_kegiatan' => '<script>alert("demo")</script>'])->save();
        $this->get(route('dev.principal.note', ['rekap' => $f['recap']->getKey(), 'link' => $f['link']->getKey()]))
            ->assertOk()->assertSee('<script>alert("demo")</script>')->assertDontSee('<script>alert("demo")</script>', false);
    }

    public function test_verified_is_not_published_and_invalid_sources_fail_closed(): void
    {
        $f = $this->official();
        $f['verification']->forceFill(['id_publikator' => null, 'tanggal_publikasi' => null])->save();
        $data = app(PrincipalReports::class);
        $this->assertSame(1, $data->notes('perkembangan', false)->count());
        $this->assertSame(0, $data->notes('perkembangan')->count());
        $this->get(route('dev.principal.recap', $f['recap']->getKey()))->assertNotFound();
        $f['verification']->forceFill(['id_publikator' => $this->gpk->getKey(), 'tanggal_publikasi' => now()])->save();
        $f['note']->forceFill(['versi_pengajuan' => 2])->save();
        $this->assertSame(0, $data->reports()->count());
        $f['note']->forceFill(['versi_pengajuan' => 1, 'id_siswa' => Siswa::where('nis', 'DEMO-A16-003')->sole()->getKey()])->save();
        $this->assertSame(0, $data->reports()->count());
    }

    public function test_draft_report_or_non_gpk_publisher_cannot_be_read_or_exported(): void
    {
        $f = $this->official();
        foreach ([['status_laporan' => 'Draft', 'id_publikator' => null, 'tanggal_publikasi' => null], ['status_laporan' => 'Tersedia', 'id_publikator' => $this->guru->getKey(), 'tanggal_publikasi' => now()]] as $attributes) {
            $f['report']->forceFill($attributes)->save();
            foreach (['recap', 'semester', 'recap.preview'] as $page) {
                $this->get(route('dev.principal.'.$page, $f['recap']->getKey()))->assertNotFound();
            }
            foreach (['pdf', 'xlsx'] as $format) {
                $this->get(route('dev.principal.recap.export', ['rekap' => $f['recap']->getKey(), 'format' => $format]))->assertNotFound();
            }
        }
    }

    private function unzip(string $bytes): array
    {
        $file = tempnam(sys_get_temp_dir(), 'ks-test-');
        file_put_contents($file, $bytes);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($file) === true);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($file);

        return $entries;
    }

    public function test_pdf_excel_zip_and_preview_export_real_filtered_files_without_writes(): void
    {
        $f = $this->official();
        $this->official(Siswa::where('nis', 'DEMO-A16-003')->sole(), 'Agustus 2025', '2025-08-20');
        $before = $this->snapshot();
        $params = ['rekap' => $f['recap']->getKey(), 'format' => 'pdf'];
        $pdf = $this->get(route('dev.principal.recap.export', $params))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('demo-a16-001-september-2026.pdf', $pdf->headers->get('Content-Disposition'));
        $this->get(route('dev.principal.recap.export', $params + ['inline' => '1']))->assertHeader('Content-Disposition', 'inline; filename="smart-abk-demo-a16-001-september-2026.pdf"');
        $xlsx = $this->get(route('dev.principal.period.export', ['format' => 'xlsx', 'periode' => 'September 2026', 'siswa' => $this->student->getKey()]))->assertOk();
        $entries = $this->unzip($xlsx->getContent());
        $sheet = $entries['xl/worksheets/sheet1.xml'];
        foreach (['DATA DEMO Guru Satu', 'DATA DEMO Matematika', 'DEMO-A16-001', '=SUM(1,2)', $f['report']->isi_laporan] as $value) {
            $this->assertStringContainsString($value, $sheet);
        }
        $this->assertStringNotContainsString('DEMO-A16-003', $sheet);
        $this->assertStringNotContainsString('<f>', $sheet);
        $this->assertNotFalse(simplexml_load_string($sheet));
        $zip = $this->get(route('dev.principal.recaps.export', ['format' => 'zip', 'periode' => 'September 2026']))->assertOk()->assertHeader('Content-Type', 'application/zip');
        $files = $this->unzip($zip->getContent());
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('%PDF-', reset($files));
        $this->assertStringContainsString('demo-a16-001', array_key_first($files));
        $this->get(route('dev.principal.period.preview', ['periode' => 'September 2026']))->assertOk()->assertSee('iframe', false);
        $this->get(route('dev.principal.recaps.export', ['format' => 'zip', 'q' => 'nonexistent']))->assertNotFound();
        $this->get(route('dev.principal.recaps.export', ['format' => 'exe']))->assertNotFound();
        $this->assertSame($before, $this->snapshot());
        if (getenv('A19_VISUAL_EXPORT')) {
            file_put_contents(storage_path('app/a19-review/report.pdf'), $pdf->getContent());
            file_put_contents(storage_path('app/a19-review/report.xlsx'), $xlsx->getContent());
            file_put_contents(storage_path('app/a19-review/report.zip'), $zip->getContent());
        }
    }

    private function snapshot(): array
    {
        $out = [];
        foreach (['siswa', 'pengguna', 'kelas', 'catatan_perkembangan', 'catatan_pendampingan', 'verifikasi_catatan', 'rekap_perkembangan', 'sumber_rekap_catatan', 'laporan_perkembangan'] as $table) {
            $out[$table] = DB::table($table)->get()->toJson();
        }

        return $out;
    }

    public function test_more_students_do_not_create_n_plus_one_queries_and_mutations_are_denied(): void
    {
        DB::enableQueryLog();
        $this->get(route('dev.principal.students'))->assertOk();
        $small = count(DB::getQueryLog());
        DB::disableQueryLog();
        foreach (range(10, 30) as $i) {
            Siswa::create(['id_kelas' => $this->student->id_kelas, 'nis' => 'DEMO-A16-'.$i, 'nama_siswa' => 'DATA DEMO Additional '.$i]);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('dev.principal.students', ['per_page' => 30]))->assertOk();
        $large = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual($small + 1, $large);
        $before = $this->snapshot();
        $this->post('/_dev/guru-mapel/siswa/'.$this->student->getKey().'/catatan', ['materi_kegiatan' => 'Unauthorized'])->assertRedirect();
        $this->post('/_dev/kepala-sekolah/rekap/1/publikasikan')->assertNotFound();
        $this->delete('/_dev/kepala-sekolah/rekap/1')->assertStatus(405);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_breadcrumbs_cover_principal_pages_and_link_to_existing_pages(): void
    {
        $f = $this->official();
        $pages = [
            'dashboard' => [], 'students' => [], 'student' => [$this->student->getKey()],
            'recaps' => [], 'recap' => [$f['recap']->getKey()],
            'semester' => [$f['recap']->getKey()],
            'note' => ['rekap' => $f['recap']->getKey(), 'link' => $f['link']->getKey()],
            'recap.preview' => [$f['recap']->getKey()], 'archives' => [],
            'period' => ['periode' => 'September 2026'],
            'period.preview' => ['periode' => 'September 2026'], 'logout' => [],
        ];
        foreach ($pages as $page => $parameters) {
            $response = $this->get(route('dev.principal.'.$page, $parameters))->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $nav = '//nav[@aria-label="Breadcrumb"]';
            $this->assertCount(1, $xpath->query($nav), $page);
            $this->assertCount(1, $xpath->query($nav.'//li[last()]/span[@aria-current="page"]'), $page);
            foreach ($xpath->query($nav.'//a') as $link) {
                $this->get($link->getAttribute('href'))->assertOk();
            }
            if (in_array($page, ['student', 'recap', 'semester', 'recap.preview', 'note'])) {
                $this->assertStringContainsString($this->student->nama_siswa, $xpath->query($nav)->item(0)->textContent);
            }
            $this->review(str_replace('.', '-', $page), $response);
        }
    }

    private function review(string $name, $response): void
    {
        if (! getenv('A19_VISUAL_EXPORT')) {
            return;
        }
        $dir = storage_path('app/a19-review');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = '';
        foreach (['resources/css/app.css', 'resources/css/principal.css'] as $entry) {
            $css .= file_get_contents(public_path('build/'.$manifest[$entry]['file']));
        }
        $html = preg_replace('/<link[^>]*>|<script[^>]*>.*?<\/script>/s', '', $response->getContent());
        $html = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
        $html = preg_replace('/src="[^"]*\/images\/guru-mapel\/active.svg"/', 'src="data:image/svg+xml;base64,'.base64_encode(file_get_contents(public_path('images/guru-mapel/active.svg'))).'"', $html);
        file_put_contents($dir.'/'.str_replace('.', '-', $name).'.html', $html);
    }
}
