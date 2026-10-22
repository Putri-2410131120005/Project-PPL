@props(['status'])
@php($labels = \App\Services\GuruMapelNoteService::LABELS)
<span class="gm-status gm-status-{{ ['Draft'=>0,'Dikirim'=>1,'Perlu Revisi'=>2,'Terverifikasi'=>3][$status] ?? 0 }}">{{ $labels[$status] ?? $status }}</span>
