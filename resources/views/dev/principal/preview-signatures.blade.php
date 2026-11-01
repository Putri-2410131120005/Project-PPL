{{-- Area tanda tangan yang disiapkan pada pratinjau dokumen. --}}
<section class="abk-pdf-approval">
    <p class="abk-pdf-date">Banjarmasin, {{ $document['printed_date'] }}</p>
    <table class="abk-pdf-signatures">
        <tr>
            <td class="abk-pdf-signature"><div>Mengetahui,<br>Kepala Sekolah</div><div class="abk-pdf-signature-space"></div><strong>{{ $document['headmaster'] ?: '(Nama Kepala Sekolah)' }}</strong><span>NIP. {{ $document['headmaster_nip'] ?: '________________' }}</span></td>
            <td class="abk-pdf-signature"><div>Guru Pembimbing Khusus (GPK)</div><div class="abk-pdf-signature-space"></div><strong>{{ $document['author'] }}</strong><span>NIP. {{ $document['author_nip'] ?: '________________' }}</span></td>
        </tr>
    </table>
</section>
<footer class="abk-pdf-footer">SMART-ABK | SMAN 4 BANJARMASIN <span class="abk-pdf-page">Halaman </span></footer>
