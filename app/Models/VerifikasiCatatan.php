<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiCatatan extends Model
{
    protected $table = 'verifikasi_catatan';
    protected $primaryKey = 'id_verifikasi';
    public $timestamps = false;

    protected $guarded = ['*'];

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
}
