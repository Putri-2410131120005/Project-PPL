<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelasiPendampingSiswa extends Model
{
    protected $table = 'relasi_pendamping_siswa';
    protected $primaryKey = 'id_relasi_pendamping';
    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'id_siswa',
    ];

    public function pendamping(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }
}
