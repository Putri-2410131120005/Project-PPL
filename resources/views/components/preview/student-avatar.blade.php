@props(['student', 'size' => 'default'])
@php
    $initials = collect(explode(' ', $student->nama_siswa))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('');
@endphp

@if ($student->foto_url)
    <img {{ $attributes->class(['abk-student-avatar', 'abk-student-avatar-sm' => $size === 'small']) }} src="{{ $student->foto_url }}" alt="Foto {{ $student->nama_siswa }}">
@else
    <div {{ $attributes->class(['abk-student-avatar', 'abk-student-initials', 'abk-student-avatar-sm' => $size === 'small']) }} aria-label="Foto {{ $student->nama_siswa }} belum tersedia">{{ $initials }}</div>
@endif
