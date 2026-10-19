<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';
    protected $primaryKey = 'id_mapel';
    public $timestamps = false;

    protected $fillable = [
        'nama_mapel',
        'status_mapel',
    ];

    public function catatanPerkembangan(): HasMany
    {
        return $this->hasMany(CatatanPerkembangan::class, 'id_mapel', 'id_mapel');
    }

    public function relasiGuruMapelKelas(): HasMany
    {
        return $this->hasMany(RelasiGuruMapelKelas::class, 'id_mapel', 'id_mapel');
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(
            Kelas::class,
            'relasi_kelas_mapel',
            'id_mapel',
            'id_kelas',
            'id_mapel',
            'id_kelas'
        );
    }
}
