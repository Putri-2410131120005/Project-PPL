<table class="abk-pdf-signatures">
    <tr>
    <td class="abk-pdf-signature">
        <div>Mengetahui,<br>Kepala Sekolah</div><div class="abk-pdf-signature-space"></div>
        <strong>{{ $headmaster?->nama ?: '(Nama Kepala Sekolah)' }}</strong>
        <span>NIP. {{ $headmaster?->nip ?: '________________' }}</span>
    </td>
    <td class="abk-pdf-signature">
        <div>Guru Pembimbing Khusus (GPK)</div><div class="abk-pdf-signature-space"></div>
        <strong>{{ $documentGpk->nama }}</strong>
        <span>NIP. {{ $documentGpk->nip ?: '________________' }}</span>
    </td>
    </tr>
</table>
