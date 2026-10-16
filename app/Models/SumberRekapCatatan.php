<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SumberRekapCatatan extends Model
{
    protected $table = 'sumber_rekap_catatan';
    protected $primaryKey = 'id_sumber_rekap';
    public $timestamps = false;

    protected $fillable = [
        'id_rekap',
        'id_catatan_perkembangan',
        'id_catatan_pendampingan',
    ];

    public function rekap(): BelongsTo
    {
        return $this->belongsTo(RekapPerkembangan::class, 'id_rekap', 'id_rekap');
    }

    public function catatanPerkembangan(): BelongsTo
    {
        return $this->belongsTo(CatatanPerkembangan::class, 'id_catatan_perkembangan', 'id_catatan_perkembangan');
    }

    public function catatanPendampingan(): BelongsTo
    {
        return $this->belongsTo(CatatanPendampingan::class, 'id_catatan_pendampingan', 'id_catatan_pendampingan');
    }
}
