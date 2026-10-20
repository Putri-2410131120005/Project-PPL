@props(['status'])
@php
    $display = match ($status) {
        'Dikirim' => 'Menunggu Verifikasi',
        'Perlu Revisi' => 'Dikembalikan',
        default => $status,
    };
    $class = match ($status) {
        'Dikirim' => 'abk-status-pending',
        'Terverifikasi' => 'abk-status-verified',
        'Perlu Revisi' => 'abk-status-returned',
        default => 'abk-status-draft',
    };
@endphp
<span {{ $attributes->class(['abk-status', $class]) }}>{{ $display }}</span>
