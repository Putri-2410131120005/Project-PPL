@props(['profile', 'title', 'subtitle' => '', 'active' => 'dashboard', 'detail' => false])
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }} — SMART-ABK</title>@vite(['resources/css/app.css', 'resources/css/guru-mapel.css', 'resources/js/app.js'])</head>
<body class="abk-preview gm"><div class="abk-shell">
<aside class="abk-sidebar">
<div class="abk-brand">INKLUSI</div><div class="abk-brand-subtitle">Monitoring ABK</div>
<nav class="abk-nav d-flex flex-column" aria-label="Navigasi Guru Mata Pelajaran">
@foreach(['dashboard' => ['Dashboard', 'dashboard'], 'students' => ['Data Siswa', 'students.index'], 'verification' => ['Status Verifikasi', 'notes.status'], 'history' => ['Riwayat Perkembangan', 'history.index']] as $key => [$label, $route])
<a href="{{ route('dev.guru-mapel.'.$route) }}" class="abk-nav-link {{ $active === $key ? 'active' : '' }}" @if($active === $key) aria-current="page" @endif>@if($active === $key)<img src="{{ asset('images/guru-mapel/active.svg') }}" width="8" height="8" alt="">@endif{{ $label }}</a>
@endforeach</nav>
<div class="gm-footer"><div>Guru Mata Pelajaran</div><form method="post" action="{{ route('dev.logout') }}">@csrf<button>Keluar</button></form><form method="post" action="{{ route('dev.change-role') }}">@csrf<button>Ganti Peran</button></form><form method="post" action="{{ route('dev.guru-mapel.demo.switch') }}">@csrf<button>Ganti Guru Demo</button></form></div>
</aside>
<main class="abk-main {{ $detail ? 'gm-detail' : '' }}">
<x-preview.topbar :title="$title" :subtitle="$subtitle" :profile="$profile" />
@if(session('success') && session('success') !== 'Draft Tersimpan')<div class="gm-notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="gm-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}
</main></div></body></html>
