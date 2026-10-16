@props(['title', 'subtitle', 'profile'])
<header class="abk-topbar">
    <div>
        <h1 class="abk-title">{{ $title }}</h1>
        <p class="abk-subtitle">{{ $subtitle }}</p>
    </div>
    <div class="abk-profile">
        <div class="abk-avatar">{{ $profile['initials'] }}</div>
        <div class="abk-profile-copy"><div class="abk-profile-name">{{ $profile['name'] }}</div><div class="abk-profile-role">{{ $profile['label'] }}</div></div>
    </div>
</header>
