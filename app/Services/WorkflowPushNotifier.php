<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\LaporanBulanan;
use App\Models\Pengguna;

class WorkflowPushNotifier
{
    public function noteSubmitted(string $type, int $noteId): void
    {
        $note = $this->note($type, $noteId);
        $gpkIds = $this->activeRoleIds('GPK');
        if ($gpkIds === []) return;

        SendPushNotification::dispatch($gpkIds, [
            'title' => 'Catatan menunggu verifikasi',
            'body' => ($type === 'perkembangan' ? 'Guru Mata Pelajaran' : 'Tim Inklusi').' mengirim catatan untuk '.$note->siswa?->nama_siswa.'.',
            'url' => route('dev.gpk.notes.show', ['type' => $type, 'id' => $noteId]),
            'tag' => "catatan-masuk-{$type}-{$noteId}-v{$note->versi_pengajuan}",
        ]);
    }

    public function revisionRequested(string $type, int $noteId): void
    {
        $note = $this->note($type, $noteId);
        $url = $type === 'perkembangan'
            ? route('guru-mapel.notes.show', $noteId)
            : route('dev.tim-inklusi.notes.show', $noteId);

        SendPushNotification::dispatch([(int) $note->id_pembuat], [
            'title' => 'Catatan perlu diperbaiki',
            'body' => 'GPK mengembalikan catatan '.$note->siswa?->nama_siswa.' untuk diperbaiki.',
            'url' => $url,
            'tag' => "catatan-revisi-{$type}-{$noteId}-v{$note->versi_pengajuan}",
        ]);
    }

    public function monthlyReportPublished(LaporanBulanan $report): void
    {
        $principalIds = $this->activeRoleIds('Kepala Sekolah');
        if ($principalIds === []) return;

        SendPushNotification::dispatch($principalIds, [
            'title' => 'Laporan bulanan tersedia',
            'body' => 'GPK telah mempublikasikan laporan '.$report->periode_bulan->locale('id')->translatedFormat('F Y').'.',
            'url' => route('dev.principal.period', ['bulan' => $report->periode_bulan->format('Y-m')]),
            'tag' => 'laporan-bulanan-'.$report->getKey(),
        ]);
    }

    private function note(string $type, int $noteId): CatatanPerkembangan|CatatanPendampingan
    {
        $model = $type === 'perkembangan' ? CatatanPerkembangan::class : CatatanPendampingan::class;
        return $model::query()->with('siswa')->findOrFail($noteId);
    }

    private function activeRoleIds(string $role): array
    {
        return Pengguna::query()->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->where('nama_role', $role))
            ->pluck('id_pengguna')->map(fn ($id) => (int) $id)->all();
    }
}
