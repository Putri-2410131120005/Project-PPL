<?php

namespace App\Jobs;

use App\Models\Pengguna;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendParentNotePush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $studentId, public readonly string $type, public readonly int $noteId) {}

    public function handle(WebPushService $push): void
    {
        $parentIds = Pengguna::query()->whereHas('orangTua.relasiSiswa', fn ($query) => $query->where('id_siswa', $this->studentId))->pluck('id_pengguna');
        $label = $this->type === 'perkembangan' ? 'perkembangan' : 'pendampingan';
        $push->sendForUsers($parentIds, [
            'title' => 'Catatan baru tersedia',
            'body' => "Ada catatan {$label} anak yang sudah diverifikasi.",
            'url' => route('dev.parent.notes.show', ['type' => $this->type, 'id' => $this->noteId]),
            'tag' => "catatan-{$this->type}-{$this->noteId}",
        ]);
    }
}
