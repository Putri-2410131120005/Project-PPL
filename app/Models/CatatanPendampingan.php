<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatatanPendampingan extends Model
{
    protected $table = 'catatan_pendampingan';
    protected $primaryKey = 'id_catatan_pendampingan';
    public $timestamps = false;

    protected $fillable = [
        'id_siswa',
        'tanggal_catatan',
        'kegiatan_pendampingan',
        'kendala',
        'tindak_lanjut',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_catatan' => 'date',
            'tanggal_kirim' => 'datetime',
            'versi_pengajuan' => 'integer',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pembuat', 'id_pengguna');
    }

    public function verifikasi(): HasMany
    {
        return $this->hasMany(VerifikasiCatatan::class, 'id_catatan_pendampingan', 'id_catatan_pendampingan');
    }

    public function sumberRekap(): HasMany
    {
        return $this->hasMany(SumberRekapCatatan::class, 'id_catatan_pendampingan', 'id_catatan_pendampingan');
    }

    /** Hanya catatan dengan verifikasi versi terkini yang telah dipublikasikan. */
    public function scopeDipublikasikan($query)
    {
        return $query->where('status_pengajuan', 'Terverifikasi')
            ->whereHas('verifikasi', function ($q) {
                $q->where('status_verifikasi', 'Terverifikasi')
                  ->whereNotNull('id_publikator')
                  ->whereNotNull('tanggal_publikasi')
                  ->whereColumn('verifikasi_catatan.versi_pengajuan', 'catatan_pendampingan.versi_pengajuan');
            });
    }
}
