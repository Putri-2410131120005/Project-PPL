@props(['title', 'subtitle', 'profile'])
<header class="abk-topbar">
    <div>
        <h1 class="abk-title">{{ $title }}</h1>
        <p class="abk-subtitle">{{ $subtitle }}</p>
    </div>
    <div class="abk-topbar-actions">
        <div class="abk-pwa-actions">
            <button class="abk-pwa-button" type="button" data-pwa-install hidden><span class="abk-pwa-label-full">Instal aplikasi</span><span class="abk-pwa-label-short">Instal</span></button>
            <button class="abk-pwa-button" type="button" data-push-enable hidden><span class="abk-pwa-label-full">Aktifkan notifikasi</span><span class="abk-pwa-label-short">Notifikasi</span></button>
            <span class="abk-pwa-hint" data-pwa-install-hint hidden></span>
        </div>
        <div class="abk-profile">
            <div class="abk-avatar">{{ $profile['initials'] }}</div>
            <div class="abk-profile-copy"><div class="abk-profile-name">{{ $profile['name'] }}</div><div class="abk-profile-role">{{ $profile['label'] }}</div></div>
        </div>
    </div>
</header>
