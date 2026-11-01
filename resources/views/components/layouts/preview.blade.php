{{-- Layout bersama untuk halaman role dengan sidebar, topbar, flash message, dan konten utama. --}}
@props(['role', 'roles', 'title' => 'Preview SMART-ABK', 'active' => 'dashboard'])
@php($navigation = app(\App\Support\NavigationHierarchy::class)->resolve(request()))

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <x-pwa.meta />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-preview"
      data-navigation-route="{{ $navigation['route'] }}"
      @if ($navigation['parent']) data-navigation-parent="{{ $navigation['parent'] }}" @endif
      @if ($navigation['root']) data-navigation-root="true" @endif>
    <div class="abk-shell">
        <x-preview.sidebar :role="$role" :roles="$roles" :active="$active" />
        <main class="abk-main abk-main-{{ $role }}">
            {{ $slot }}
        </main>
    </div>
    <x-preview.confirmation-modal />
</body>
</html>
