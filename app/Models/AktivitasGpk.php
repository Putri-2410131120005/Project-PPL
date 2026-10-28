<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AktivitasGpk extends Model
{
    protected $table = 'aktivitas_gpk';
    protected $primaryKey = 'id_aktivitas_gpk';
    public $timestamps = false;
    protected $guarded = ['id_aktivitas_gpk'];

    protected function casts(): array
    {
        return ['tanggal_aktivitas' => 'datetime', 'versi_pengajuan' => 'integer'];
    }

    public function gpk(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_gpk', 'id_pengguna');
    }
}
