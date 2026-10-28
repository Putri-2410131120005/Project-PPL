<?php

namespace App\Services;

use App\Models\CatatanPerkembangan;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GuruMapelNoteService
{
    public const LABELS = ['Draft' => 'Draft', 'Dikirim' => 'Menunggu Verifikasi', 'Terverifikasi' => 'Terverifikasi', 'Perlu Revisi' => 'Dikembalikan'];

    public function save(Pengguna $guru, int $siswaId, array $input, ?int $noteId = null): CatatanPerkembangan
    {
        return DB::transaction(function () use ($guru, $siswaId, $input, $noteId) {
            $access = app(GuruMapelAccess::class);
            $siswa = $access->student($guru, $siswaId);
            $note = $noteId ? $access->notes($guru)->lockForUpdate()->findOrFail($noteId) : new CatatanPerkembangan;
            abort_if($noteId && (int) $note->id_siswa !== $siswaId, 404);
            abort_if($noteId && ! in_array($note->status_pengajuan, ['Draft', 'Perlu Revisi'], true), 409, 'Catatan sudah dikirim dan tidak dapat diubah.');
            $send = ($input['aksi'] ?? '') === 'kirim';
            $rules = [
                'aksi' => 'required|in:draft,kirim', 'tanggal_catatan' => 'required|date_format:Y-m-d|before_or_equal:today',
                'materi_kegiatan' => 'required|string|max:10000', 'catatan_tambahan' => 'nullable|string|max:10000',
            ];
            foreach (['kendala_peristiwa', 'tindakan', 'hasil_tindakan'] as $field) {
                $rules[$field] = ($send ? 'required' : 'nullable').'|string|max:10000';
            }
            $data = Validator::make($input, $rules)->validate();
            // Subject identity is server-owned and comes from the Admin assignment.
            $mapel = $noteId ? (int) $note->id_mapel : $access->assignedSubjectId($guru, $siswa);
            $access->writable($guru, $siswa, $mapel);
            // Explicit optimistic token prevents silent overwrites and duplicate submission.
            if ($noteId) {
                $expected = hash('sha256', json_encode($note->getRawOriginal()));
                abort_unless(is_string($input['revision_token'] ?? null) && hash_equals($expected, $input['revision_token']), 409, 'Catatan berubah. Muat ulang sebelum menyimpan.');
            }
            $revisi = $noteId && $note->status_pengajuan === 'Perlu Revisi';
            $note->fill(collect($data)->except('aksi')->all());
            $note->forceFill([
                'id_siswa' => $siswaId, 'id_pembuat' => $guru->getKey(), 'id_mapel' => $mapel,
                'status_pengajuan' => $send ? 'Dikirim' : 'Draft',
                'versi_pengajuan' => $noteId ? $note->versi_pengajuan + ($revisi ? 1 : 0) : 1,
                'tanggal_kirim' => $send ? now() : null,
            ])->save();

            return $note;
        });
    }
}
