@props(['student', 'closeUrl', 'viewer' => 'gpk'])

@php
    $highlights = collect($student['ringkasan'] ?? [])->filter()->values();
    $records = collect($student['records'] ?? []);
@endphp

<div class="abk-report-summary-backdrop" data-report-summary-modal data-close-url="{{ $closeUrl }}">
<section class="abk-panel abk-report-student-summary" role="dialog" aria-modal="true" aria-labelledby="student-summary-title" tabindex="-1">
    <header class="abk-report-summary-header">
        <div>
            <span class="abk-report-summary-eyebrow">Ringkasan siswa</span>
            <h2 id="student-summary-title">{{ $student['nama_siswa'] }}</h2>
            <p>{{ $student['kelas'] }} · {{ $student['jumlah_catatan'] }} catatan terverifikasi</p>
        </div>
        <a class="{{ $viewer === 'principal' ? 'ks-button ks-outline' : 'abk-outline-button' }}" href="{{ $closeUrl }}">Tutup Ringkasan</a>
    </header>

    <div class="abk-report-summary-grid">
        <div class="abk-report-highlight-card">
            <h3>Ringkasan perkembangan</h3>
            @forelse ($highlights as $highlight)
                <p><span aria-hidden="true">•</span>{{ $highlight }}</p>
            @empty
                <p class="abk-report-empty-copy">Belum ada perkembangan yang dirangkum pada periode ini.</p>
            @endforelse
        </div>

        <div class="abk-report-records">
            <h3>Catatan pendukung</h3>
            @forelse ($records as $record)
                @php
                    $hasVerificationRecommendation = array_key_exists('verification_recommendation', $record);
                    $recommendation = $hasVerificationRecommendation ? $record['verification_recommendation'] : ($record['recommendation'] ?? null);
                @endphp
                <article class="abk-report-record">
                    <div class="abk-report-record-meta">
                        <strong>{{ $record['source'] ?? 'Catatan perkembangan' }}</strong>
                        <time>{{ \Illuminate\Support\Carbon::parse($record['date'])->locale('id')->translatedFormat('d M Y') }}</time>
                    </div>
                    @if (! empty($record['author']))<small>Dicatat oleh {{ $record['author'] }}</small>@endif
                    <p>{{ $record['summary'] ?? 'Tidak ada ringkasan catatan.' }}</p>
                    <div class="abk-report-recommendation {{ empty($recommendation) ? 'is-empty' : '' }}">
                        <span>{{ $hasVerificationRecommendation ? 'Rekomendasi GPK' : 'Tindak lanjut / rekomendasi' }}</span>
                        <strong>{{ $recommendation ?: 'Tanpa rekomendasi tambahan' }}</strong>
                        @if (! empty($record['verified_by']))<small>Diverifikasi oleh {{ $record['verified_by'] }}</small>@endif
                    </div>
                </article>
            @empty
                <p class="abk-report-empty-copy">Tidak ada rincian catatan pendukung.</p>
            @endforelse
        </div>
    </div>
</section>
</div>
