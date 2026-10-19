<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kelas extends Model
{
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $timestamps = false;

    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'tahun_ajaran',
        'status_kelas',
    ];

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'id_kelas', 'id_kelas');
    }

    public function relasiGuruMapelKelas(): HasMany
    {
        return $this->hasMany(RelasiGuruMapelKelas::class, 'id_kelas', 'id_kelas');
    }

    public function mataPelajaran(): BelongsToMany
    {
        return $this->belongsToMany(
            MataPelajaran::class,
            'relasi_kelas_mapel',
            'id_kelas',
            'id_mapel',
            'id_kelas',
            'id_mapel'
        );
    }
}
