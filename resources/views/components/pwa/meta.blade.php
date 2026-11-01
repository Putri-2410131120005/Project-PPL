<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="pwa-service-worker" content="{{ asset('sw.js') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="push-vapid-key-url" content="{{ route('push.vapid-key') }}">
<meta name="push-subscription-url" content="{{ route('push.subscriptions.store') }}">
<meta name="theme-color" content="#2563eb">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
