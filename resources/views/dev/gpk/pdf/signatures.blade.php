<div class="abk-pdf-signatures">
    <div class="abk-pdf-signature">
        <div>Kepala Sekolah</div><div class="abk-pdf-signature-space"></div>
        <strong>{{ $headmaster?->nama ?: '(Nama Kepala Sekolah)' }}</strong>
        <span>NIP. {{ $headmaster?->nip ?: '________________' }}</span>
    </div>
    <div class="abk-pdf-signature">
        <div>GPK</div><div class="abk-pdf-signature-space"></div>
        <strong>{{ $documentGpk->nama }}</strong>
        <span>NIP. {{ $documentGpk->nip ?: '________________' }}</span>
    </div>
</div>
