<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiCatatan extends Model
{
    protected $table = 'verifikasi_catatan';
    protected $primaryKey = 'id_verifikasi';
    public $timestamps = false;

    protected $guarded = ['*'];

    /**
     * Server-side publication actor supplied by the workflow service.
     * This value is intentionally transient and is never persisted.
     */
    private ?int $publicationActorId = null;

    protected static function booted(): void
    {
        static::saving(function (self $verification): void {
            $verification->guardPublicationIntegrity();
        });

        static::saved(function (self $verification): void {
            $verification->publicationActorId = null;
        });
    }

    public function authorizePublicationMutationBy(Pengguna|int $actor): static
    {
        $this->publicationActorId = $actor instanceof Pengguna
            ? (int) $actor->getKey()
            : $actor;

        return $this;
    }

    protected function casts(): array
    {
        return [
            'tanggal_verifikasi' => 'datetime',
            'tanggal_publikasi' => 'datetime',
            'versi_pengajuan' => 'integer',
        ];
    }

    public function catatanPerkembangan(): BelongsTo
    {
        return $this->belongsTo(CatatanPerkembangan::class, 'id_catatan_perkembangan', 'id_catatan_perkembangan');
    }

    public function catatanPendampingan(): BelongsTo
    {
        return $this->belongsTo(CatatanPendampingan::class, 'id_catatan_pendampingan', 'id_catatan_pendampingan');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_verifikator', 'id_pengguna');
    }

    public function publikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_publikator', 'id_pengguna');
    }

    private function guardPublicationIntegrity(): void
    {
        $publisherId = $this->getAttribute('id_publikator');
        $publishedAt = $this->getAttribute('tanggal_publikasi');
        $isPublisherPresent = $publisherId !== null;
        $isPublishedAtPresent = $publishedAt !== null;

        if ($isPublisherPresent !== $isPublishedAtPresent
            || ($isPublisherPresent && $this->getAttribute('status_verifikasi') !== 'Terverifikasi')) {
            throw new DomainException('Publikasi memerlukan GPK, tanggal, dan status Terverifikasi.');
        }

        if (! $this->isDirty(['id_publikator', 'tanggal_publikasi'])) {
            return;
        }

        if ($this->publicationActorId === null) {
            throw new DomainException('Perubahan data publikasi harus melalui alur GPK yang terotorisasi.');
        }

        $actor = Pengguna::query()->with('role')->find($this->publicationActorId);
        if (! $actor || $actor->status_akun !== 'Aktif' || $actor->role?->nama_role !== 'GPK') {
            throw new DomainException('Hanya GPK dengan akun aktif yang dapat mengubah data publikasi.');
        }

        if ($isPublisherPresent && (int) $publisherId !== (int) $actor->getKey()) {
            throw new DomainException('GPK publikator harus sama dengan aktor publikasi yang terotorisasi.');
        }

        if (! $isPublisherPresent && $this->exists) {
            $originalPublisherId = $this->getOriginal('id_publikator');
            $verifierId = $this->getAttribute('id_verifikator');
            if ($originalPublisherId !== null
                && (int) $actor->getKey() !== (int) $originalPublisherId
                && (int) $actor->getKey() !== (int) $verifierId) {
                throw new DomainException('Publikasi hanya dapat diubah oleh GPK yang terkait dengan verifikasi.');
            }
        }
    }
}
