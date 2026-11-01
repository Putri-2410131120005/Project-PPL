<?php

namespace Tests\Unit;

use App\Support\NavigationHierarchy;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationHierarchyTest extends TestCase
{
    #[DataProvider('hierarchyProvider')]
    public function test_named_routes_resolve_to_their_structural_parent(string $route, array $parameters, string $parent, array $parentParameters = []): void
    {
        $navigation = $this->resolve(route($route, $parameters));

        $this->assertFalse($navigation['root']);
        $this->assertSame(route($parent, $parentParameters), $navigation['parent']);
    }

    public static function hierarchyProvider(): array
    {
        return [
            'admin list to dashboard' => ['dev.admin.students.index', [], 'dev.preview'],
            'admin form to list' => ['dev.admin.students.edit', ['siswa' => 91], 'dev.admin.students.index'],
            'admin subsection to users' => ['dev.admin.staff.index', [], 'dev.admin.users.index'],
            'gpk list to dashboard' => ['dev.gpk.notes.index', [], 'dev.gpk.dashboard'],
            'gpk note to queue' => ['dev.gpk.notes.show', ['type' => 'perkembangan', 'id' => 81], 'dev.gpk.notes.index'],
            'gpk recap preview to detail' => ['dev.gpk.recaps.pdf.preview', ['siswa' => 71], 'dev.gpk.recaps.show', ['siswa' => 71]],
            'tim list to dashboard' => ['dev.tim-inklusi.students.index', [], 'dev.tim-inklusi.dashboard'],
            'tim student to list' => ['dev.tim-inklusi.students.show', ['siswa' => 61], 'dev.tim-inklusi.students.index'],
            'tim form to student' => ['dev.tim-inklusi.notes.create', ['siswa' => 61], 'dev.tim-inklusi.students.show', ['siswa' => 61]],
            'tim edit to note detail' => ['dev.tim-inklusi.notes.edit', ['catatan' => 51], 'dev.tim-inklusi.notes.show', ['catatan' => 51]],
            'guru list to dashboard' => ['guru-mapel.students.index', [], 'guru-mapel.dashboard'],
            'guru student to list' => ['guru-mapel.students.show', ['siswa' => 41], 'guru-mapel.students.index'],
            'guru form to student' => ['guru-mapel.notes.create', ['siswa' => 41], 'guru-mapel.students.show', ['siswa' => 41]],
            'guru revision to note' => ['guru-mapel.notes.revise', ['id' => 31], 'guru-mapel.notes.show', ['id' => 31]],
            'principal list to dashboard' => ['dev.principal.recaps', [], 'dev.principal.dashboard'],
            'principal recap detail to list' => ['dev.principal.verified-recap', ['siswa' => 21], 'dev.principal.recaps'],
            'principal source note to recap' => ['dev.principal.note', ['rekap' => 11, 'link' => 12], 'dev.principal.recap', ['rekap' => 11]],
            'principal period to archives' => ['dev.principal.period', ['periode' => 'Oktober 2026'], 'dev.principal.archives'],
            'parent notes to dashboard' => ['dev.parent.notes.index', [], 'dev.parent.dashboard'],
            'parent note to list' => ['dev.parent.notes.show', ['type' => 'pendampingan', 'id' => 10], 'dev.parent.notes.index'],
        ];
    }

    public function test_dashboard_routes_are_navigation_roots(): void
    {
        foreach ([
            ['dev.preview', ['role' => 'admin']],
            ['dev.gpk.dashboard', []],
            ['dev.tim-inklusi.dashboard', []],
            ['dev.principal.dashboard', []],
            ['dev.parent.dashboard', []],
            ['guru-mapel.dashboard', []],
        ] as [$route, $parameters]) {
            $navigation = $this->resolve(route($route, $parameters));
            $this->assertTrue($navigation['root'], $route);
            $this->assertNull($navigation['parent'], $route);
        }
    }

    public function test_only_allow_listed_query_context_is_added_to_parent(): void
    {
        $url = route('dev.gpk.recaps.show', [
            'siswa' => 7,
            'semester' => 'ganjil',
            'tahun_ajaran' => '2026/2027',
            'redirect' => 'https://example.test/phishing',
        ]);

        $navigation = $this->resolve($url);

        $this->assertSame(route('dev.gpk.recaps.index', [
            'semester' => 'ganjil',
            'tahun_ajaran' => '2026/2027',
        ]), $navigation['parent']);
        $this->assertStringNotContainsString('redirect', $navigation['parent']);
    }

    private function resolve(string $url): array
    {
        $request = Request::create($url);
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        return app(NavigationHierarchy::class)->resolve($request);
    }
}
