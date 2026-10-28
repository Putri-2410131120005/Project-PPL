@props(['role', 'roles', 'title' => 'Preview SMART-ABK', 'active' => 'dashboard'])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-preview">
    <div class="abk-shell">
        <x-preview.sidebar :role="$role" :roles="$roles" :active="$active" />
        <main class="abk-main abk-main-{{ $role }}">
            {{ $slot }}
        </main>
    </div>
    <x-preview.confirmation-modal />
</body>
</html>
