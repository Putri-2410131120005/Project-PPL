<?php

namespace App\Support;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Throwable;

class NavigationHierarchy
{
    /**
     * Resolve the structural parent of the current named route.
     *
     * Parent URLs are generated only from application route names, matched route
     * parameters, and explicitly allow-listed query parameters.
     *
     * @return array{route: ?string, parent: ?string, root: bool}
     */
    public function resolve(Request $request): array
    {
        $name = $request->route()?->getName();

        if (! $name) {
            return ['route' => null, 'parent' => null, 'root' => false];
        }

        if ($this->isDashboard($name)) {
            return ['route' => $name, 'parent' => null, 'root' => true];
        }

        [$parent, $parameters, $query] = $this->definition($request, $name)
            ?? [null, [], []];

        return [
            'route' => $name,
            'parent' => $parent ? $this->routeUrl($parent, $parameters, $request, $query) : null,
            'root' => false,
        ];
    }

    private function isDashboard(string $name): bool
    {
        // Dashboard role apa pun adalah batas teratas navigasi. Dengan pola ini,
        // sidebar/role baru tidak perlu didaftarkan manual agar Back PWA di root
        // diserahkan ke Android tanpa menjalankan logout.
        return $name === 'dev.preview' || str_ends_with($name, '.dashboard');
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: list<string>}|null
     */
    private function definition(Request $request, string $name): ?array
    {
        $parameter = fn (string $key) => $request->route($key);

        $static = [
            // Admin module roots.
            'dev.admin.academic-years.index' => ['dev.preview', [], []],
            'dev.admin.users.index' => ['dev.preview', [], []],
            'dev.admin.students.index' => ['dev.preview', [], []],
            'dev.admin.classes.index' => ['dev.preview', [], []],
            'dev.admin.subjects.index' => ['dev.preview', [], []],
            'dev.admin.mappings.guru-mapel-kelas.index' => ['dev.preview', [], []],
            'dev.admin.mappings.pendamping-siswa.index' => ['dev.preview', [], []],
            'dev.admin.mappings.orang-tua-siswa.index' => ['dev.preview', [], []],
            'dev.admin.mappings.access-rights.index' => ['dev.preview', [], []],
            'dev.admin.staff.index' => ['dev.admin.users.index', [], []],
            'dev.admin.parents.index' => ['dev.admin.users.index', [], []],
            'dev.admin.academic-years.create' => ['dev.admin.academic-years.index', [], []],
            'dev.admin.users.create' => ['dev.admin.users.index', [], []],
            'dev.admin.users.parents.create' => ['dev.admin.users.index', [], ['role']],
            'dev.admin.staff.create' => ['dev.admin.users.index', [], []],
            'dev.admin.students.create' => ['dev.admin.students.index', [], []],
            'dev.admin.classes.create' => ['dev.admin.classes.index', [], []],
            'dev.admin.subjects.create' => ['dev.admin.subjects.index', [], []],

            // GPK.
            'dev.gpk.students.index' => ['dev.gpk.dashboard', [], []],
            'dev.gpk.notes.index' => ['dev.gpk.dashboard', [], []],
            'dev.gpk.history.index' => ['dev.gpk.dashboard', [], []],
            'dev.gpk.recaps.index' => ['dev.gpk.dashboard', [], []],
            'dev.gpk.reports.index' => ['dev.gpk.dashboard', [], []],
            'dev.gpk.parents.index' => ['dev.gpk.dashboard', [], []],

            // Tim Inklusi.
            'dev.tim-inklusi.students.index' => ['dev.tim-inklusi.dashboard', [], []],
            'dev.tim-inklusi.notes.index' => ['dev.tim-inklusi.dashboard', [], []],

            // Kepala Sekolah.
            'dev.principal.students' => ['dev.principal.dashboard', [], []],
            'dev.principal.recaps' => ['dev.principal.dashboard', [], []],
            'dev.principal.archives' => ['dev.principal.dashboard', [], []],

            // Orang Tua.
            'dev.parent.notes.index' => ['dev.parent.dashboard', [], []],
            'dev.parent.reports.index' => ['dev.parent.dashboard', [], []],
        ];

        if (isset($static[$name])) {
            return $static[$name];
        }

        if (str_starts_with($name, 'guru-mapel.')) {
            return $this->guruMapelDefinition($request, $name, 'guru-mapel.', $parameter);
        }

        return match ($name) {
            // Admin parameterized forms.
            'dev.admin.academic-years.edit' => ['dev.admin.academic-years.index', [], []],
            'dev.admin.users.edit', 'dev.admin.staff.edit' => ['dev.admin.users.index', [], ['role', 'status', 'cari', 'page', 'per_page']],
            'dev.admin.students.edit' => ['dev.admin.students.index', [], ['cari', 'tingkat', 'status', 'page', 'per_page']],
            'dev.admin.classes.edit' => ['dev.admin.classes.index', [], ['cari', 'tingkat', 'status', 'page', 'per_page']],
            'dev.admin.subjects.edit' => ['dev.admin.subjects.index', [], ['cari', 'status', 'page', 'per_page']],

            // GPK detail hierarchy.
            'dev.gpk.students.show' => ['dev.gpk.students.index', [], ['cari', 'tingkat', 'page']],
            'dev.gpk.notes.show' => ['dev.gpk.notes.index', [], ['status', 'jenis', 'cari', 'page']],
            'dev.gpk.recaps.show' => ['dev.gpk.recaps.index', [], ['semester', 'tahun_ajaran', 'tingkat', 'cari', 'page']],
            'dev.gpk.recaps.pdf.preview' => ['dev.gpk.recaps.show', ['siswa' => $parameter('siswa')], ['semester', 'tahun_ajaran']],
            'dev.gpk.reports.show' => ['dev.gpk.reports.index', [], ['semester', 'tahun_ajaran']],
            'dev.gpk.reports.pdf.preview' => ['dev.gpk.reports.show', ['laporan' => $parameter('laporan')], []],

            // Tim Inklusi detail hierarchy.
            'dev.tim-inklusi.students.show' => ['dev.tim-inklusi.students.index', [], ['cari', 'tingkat', 'page']],
            'dev.tim-inklusi.students.recommendation' => ['dev.tim-inklusi.students.show', ['siswa' => $parameter('siswa')], []],
            'dev.tim-inklusi.development-notes.show' => ['dev.tim-inklusi.students.show', ['siswa' => $parameter('siswa')], []],
            'dev.tim-inklusi.notes.create' => ['dev.tim-inklusi.students.show', ['siswa' => $parameter('siswa')], []],
            'dev.tim-inklusi.notes.show' => ['dev.tim-inklusi.notes.index', [], ['status', 'cari', 'page']],
            'dev.tim-inklusi.notes.edit' => ['dev.tim-inklusi.notes.show', ['catatan' => $parameter('catatan')], []],

            // Kepala Sekolah detail hierarchy.
            'dev.principal.student' => ['dev.principal.students', [], ['q', 'tingkat', 'page', 'per_page']],
            'dev.principal.verified-recap' => ['dev.principal.recaps', [], ['semester', 'tahun_ajaran', 'q', 'tingkat', 'catatan_page', 'per_page']],
            'dev.principal.verified-recap.preview' => ['dev.principal.verified-recap', ['siswa' => $parameter('siswa')], ['semester', 'tahun_ajaran']],
            'dev.principal.recap' => ['dev.principal.recaps', [], ['periode', 'tahun_ajaran', 'tingkat', 'page', 'per_page']],
            'dev.principal.note' => ['dev.principal.recap', ['rekap' => $parameter('rekap')], []],
            'dev.principal.semester' => ['dev.principal.recap', ['rekap' => $parameter('rekap')], []],
            'dev.principal.recap.preview' => ['dev.principal.recap', ['rekap' => $parameter('rekap')], []],
            'dev.principal.period' => ['dev.principal.archives', [], ['bulan', 'tahun', 'page', 'per_page']],
            'dev.principal.period.preview' => ['dev.principal.period', [], ['periode', 'siswa', 'page', 'per_page']],

            // Orang Tua detail hierarchy.
            'dev.parent.notes.show' => ['dev.parent.notes.index', [], ['semester', 'page']],

            default => null,
        };
    }

    /**
     * @param  callable(string): mixed  $parameter
     * @return array{0: string, 1: array<string, mixed>, 2: list<string>}|null
     */
    private function guruMapelDefinition(Request $request, string $name, string $prefix, callable $parameter): ?array
    {
        $suffix = substr($name, strlen($prefix));

        return match ($suffix) {
            'students.index', 'notes.status' => [$prefix.'dashboard', [], []],
            'students.show' => [$prefix.'students.index', [], ['q', 'tingkat', 'page']],
            'recommendations.show' => [$prefix.'students.show', ['siswa' => $parameter('siswa')], []],
            'references.show' => [$prefix.'students.show', ['siswa' => $this->referenceStudentId($request)], []],
            'notes.create' => [$prefix.'students.show', ['siswa' => $parameter('siswa')], []],
            'notes.show' => [$prefix.'notes.status', [], ['q', 'status', 'page']],
            'notes.revise' => [$prefix.'notes.show', ['id' => $parameter('id')], []],
            default => null,
        };
    }

    private function referenceStudentId(Request $request): ?int
    {
        $model = $request->route('source') === 'perkembangan'
            ? CatatanPerkembangan::class
            : CatatanPendampingan::class;

        return $model::query()->whereKey($request->route('id'))->value('id_siswa');
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  list<string>  $queryKeys
     */
    private function routeUrl(string $routeName, array $parameters, Request $request, array $queryKeys): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        $query = collect($queryKeys)
            ->mapWithKeys(fn (string $key) => [$key => $request->query($key)])
            ->filter(fn ($value) => is_scalar($value) && $value !== '')
            ->all();

        try {
            return route($routeName, array_filter($parameters, fn ($value) => $value !== null) + $query);
        } catch (Throwable) {
            return null;
        }
    }
}
