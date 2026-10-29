<x-layouts.preview :role="$role" :roles="$roles" title="Preview {{ $profile['label'] }}">
    <x-preview.topbar title="Dashboard {{ $profile['label'] }}" subtitle="Preview fondasi antarmuka SMART-ABK." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', $role)" />
    <div class="abk-welcome">Selamat datang, {{ $profile['label'] }}</div>
    <section class="abk-panel abk-role-placeholder"><h2 class="abk-section-heading mt-0">Preview navigasi {{ $profile['label'] }}</h2><p>Role ini disediakan untuk meninjau konsistensi shell antarmuka. Modul dan aksi bisnis belum dibuat.</p></section>
</x-layouts.preview>
