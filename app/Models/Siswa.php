<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $primaryKey = 'id_siswa';

    public $timestamps = false;

    protected $fillable = [
        'id_kelas',
        'nis',
        'nama_siswa',
        'foto',
        'kebutuhan_khusus',
        'status_siswa',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function catatanPerkembangan(): HasMany
    {
        return $this->hasMany(CatatanPerkembangan::class, 'id_siswa', 'id_siswa');
    }

    public function catatanPendampingan(): HasMany
    {
        return $this->hasMany(CatatanPendampingan::class, 'id_siswa', 'id_siswa');
    }

    public function rekapPerkembangan(): HasMany
    {
        return $this->hasMany(RekapPerkembangan::class, 'id_siswa', 'id_siswa');
    }

    public function relasiOrangTuaSiswa(): HasMany
    {
        return $this->hasMany(RelasiOrangTuaSiswa::class, 'id_siswa', 'id_siswa');
    }

    public function relasiPendampingSiswa(): HasMany
    {
        return $this->hasMany(RelasiPendampingSiswa::class, 'id_siswa', 'id_siswa');
    }
}
