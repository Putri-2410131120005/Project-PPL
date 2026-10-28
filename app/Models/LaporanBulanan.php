<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanBulanan extends Model
{
    protected $table = 'laporan_bulanan';

    protected $primaryKey = 'id_laporan_bulanan';

    public $timestamps = false;

    protected $fillable = [
        'periode_bulan',
        'jumlah_siswa',
        'jumlah_catatan',
        'data_laporan',
        'status_laporan',
        'tanggal_dibuat',
        'id_publikator',
        'tanggal_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'date',
            'data_laporan' => 'array',
            'tanggal_dibuat' => 'datetime',
            'tanggal_publikasi' => 'datetime',
        ];
    }

    public function publikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_publikator', 'id_pengguna');
    }
}
