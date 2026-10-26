@props(['profile','title','subtitle'=>'','active'=>'dashboard','detail'=>false,'breadcrumbs'=>[]])
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }} — SMART-ABK</title>
@vite(['resources/css/app.css','resources/css/principal.css','resources/js/app.js','resources/js/principal.js'])</head>
<body class="abk-preview ks"><div class="abk-shell">
<aside class="abk-sidebar"><div class="abk-brand">INKLUSI</div><div class="abk-brand-subtitle">Monitoring ABK</div>
<nav class="abk-nav d-flex flex-column" aria-label="Navigasi Kepala Sekolah">
@foreach(['dashboard'=>['Dashboard','dashboard'],'students'=>['Data Siswa','students'],'recaps'=>['Rekap','recaps'],'archives'=>['Arsip Laporan','archives']] as $key=>[$label,$route])
<a href="{{ route('dev.principal.'.$route) }}" class="abk-nav-link {{ $active===$key?'active':'' }}" @if($active===$key) aria-current="page" @endif>@if($active===$key)<img src="{{ asset('images/guru-mapel/active.svg') }}" width="8" height="8" alt="">@endif{{ $label }}</a>
@endforeach</nav>
<div class="ks-sidebar-footer"><div>Kepala Sekolah</div><a href="{{ route('dev.principal.logout') }}">Keluar</a><form method="post" action="{{ route('dev.change-role') }}">@csrf<button>Ganti Peran</button></form></div>
</aside>
<main class="abk-main {{ $detail?'ks-detail':'' }}">
<x-preview.topbar :title="$title" :subtitle="$subtitle" :profile="$profile" />
<x-principal.breadcrumbs :items="$breadcrumbs" />
@if($errors->any())<div class="ks-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
{{ $slot }}
</main></div>
<div class="ks-toast" role="status" hidden><span class="ks-success-icon" aria-hidden="true">✓</span><div><strong data-download-title>Unduhan siap</strong><p data-download-message>File berhasil disiapkan untuk diunduh.</p></div><button type="button" aria-label="Tutup notifikasi" data-toast-close>×</button></div>
</body></html>
