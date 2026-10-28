@props(['profile','title','subtitle'=>'','active'=>'dashboard','detail'=>false,'breadcrumbs'=>[]])
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }} — SMART-ABK</title>
@vite(['resources/css/app.css','resources/css/principal.css','resources/css/role-parity.css','resources/js/app.js','resources/js/principal.js'])</head>
<body class="abk-preview ks"><div class="abk-shell">
<x-preview.sidebar role="kepala-sekolah" :roles="['kepala-sekolah' => $profile]" :active="$active" />
<main class="abk-main {{ $detail?'ks-detail':'' }}">
<x-preview.topbar :title="$title" :subtitle="$subtitle" :profile="$profile" />
<x-principal.breadcrumbs :items="$breadcrumbs" />
<div class="abk-role-content">
@if($errors->any())<div class="ks-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
{{ $slot }}
</div></main></div>
<x-preview.confirmation-modal />
<div class="ks-toast" role="status" hidden><span class="ks-success-icon" aria-hidden="true">✓</span><div><strong data-download-title>Unduhan siap</strong><p data-download-message>File berhasil disiapkan untuk diunduh.</p></div><button type="button" aria-label="Tutup notifikasi" data-toast-close>×</button></div>
</body></html>
