<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'id_tahun_ajaran',
        'tahun_ajaran',
        'status_kelas',
    ];

    protected static function booted(): void
    {
        static::saving(function (Kelas $kelas): void {
            if ($kelas->id_tahun_ajaran) {
                $kelas->tahun_ajaran = TahunAjaran::query()->findOrFail($kelas->id_tahun_ajaran)->nama;
            } elseif ($kelas->tahun_ajaran) {
                $kelas->id_tahun_ajaran = TahunAjaran::resolveLabel($kelas->tahun_ajaran)->getKey();
            }
        });
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'id_kelas', 'id_kelas');
    }

    public function relasiGuruMapelKelas(): HasMany
    {
        return $this->hasMany(RelasiGuruMapelKelas::class, 'id_kelas', 'id_kelas');
    }

    public function riwayatSiswa(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'id_kelas', 'id_kelas');
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
