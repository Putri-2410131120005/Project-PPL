<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPerkembangan extends Model
{
    protected $table = 'laporan_perkembangan';
    protected $primaryKey = 'id_laporan';
    public $timestamps = false;

    protected $fillable = [
        'id_rekap',
        'id_pembuat',
        'periode',
        'isi_laporan',
        'tanggal_laporan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_laporan' => 'datetime',
            'tanggal_publikasi' => 'datetime',
        ];
    }

    public function rekap(): BelongsTo
    {
        return $this->belongsTo(RekapPerkembangan::class, 'id_rekap', 'id_rekap');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pembuat', 'id_pengguna');
    }

    public function publikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_publikator', 'id_pengguna');
    }
}
