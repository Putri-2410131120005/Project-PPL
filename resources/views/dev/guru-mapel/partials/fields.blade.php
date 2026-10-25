@php($fields = ['materi_kegiatan' => 'Materi / TP yang berkaitan', 'kendala_peristiwa' => 'Peristiwa / Hasil Pengamatan', 'tindakan' => 'Tindakan / Penyesuaian Guru', 'hasil_tindakan' => 'Respons / Hasil Setelah Tindakan', 'catatan_tambahan' => 'Catatan tambahan / kendala (opsional)'])
@php($placeholders = [
    'materi_kegiatan' => 'Tuliskan materi atau TP yang sedang dipelajari...',
    'kendala_peristiwa' => 'Tuliskan apa yang terjadi: capaian, kesulitan, perilaku belajar, atau kendala yang terlihat...',
    'tindakan' => 'Tuliskan tindakan, bantuan, atau penyesuaian yang diberikan oleh guru...',
    'hasil_tindakan' => 'Tuliskan respons siswa atau perubahan yang terlihat setelah tindakan diberikan...',
    'catatan_tambahan' => 'Tambahkan informasi lain, hasil tugas/evaluasi, atau kendala bila diperlukan...',
])
@foreach($fields as $field => $label)
<div class="gm-field">
<label for="{{ $field }}">{{ $label }}</label>
@if($editable ?? false)<textarea id="{{ $field }}" name="{{ $field }}" maxlength="10000" placeholder="{{ $placeholders[$field] }}" @if($field === 'materi_kegiatan') required @endif class="{{ in_array($field, ['materi_kegiatan','catatan_tambahan']) ? 'gm-short' : '' }}">{{ old($field, $note?->$field) }}</textarea>
@else<div class="gm-value">{{ $note->$field ?: 'Belum diisi' }}</div>@endif</div>
@endforeach
