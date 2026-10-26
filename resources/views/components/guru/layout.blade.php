@props(['profile', 'title', 'subtitle' => '', 'active' => 'dashboard', 'detail' => false, 'breadcrumbs' => []])
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }} — SMART-ABK</title>@vite(['resources/css/app.css', 'resources/css/guru-mapel.css', 'resources/js/app.js'])</head>
<body class="abk-preview gm"><div class="abk-shell">
<x-preview.sidebar role="guru-mapel" :roles="['guru-mapel' => $profile]" :active="$active" />
<main class="abk-main {{ $detail ? 'gm-detail' : '' }}">
<x-preview.topbar :title="$title" :subtitle="$subtitle" :profile="$profile" />
<x-guru.breadcrumbs :items="$breadcrumbs" />
@if(session('success') && session('success') !== 'Draft Tersimpan')<div class="gm-notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="gm-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}
</main></div><x-preview.confirmation-modal /></body></html>
