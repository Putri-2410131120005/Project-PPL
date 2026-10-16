<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelasiGuruMapelKelas extends Model
{
    protected $table = 'relasi_guru_mapel_kelas';
    protected $primaryKey = 'id_relasi_guru_mapel';
    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'id_mapel',
        'id_kelas',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'id_mapel', 'id_mapel');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }
}
