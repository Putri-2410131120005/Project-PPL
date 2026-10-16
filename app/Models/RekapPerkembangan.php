<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekapPerkembangan extends Model
{
    protected $table = 'rekap_perkembangan';
    protected $primaryKey = 'id_rekap';
    public $timestamps = false;

    protected $fillable = [
        'id_siswa',
        'id_pembuat',
        'periode',
        'ringkasan_perkembangan',
        'tanggal_rekap',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_rekap' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pembuat', 'id_pengguna');
    }

    public function sumberCatatan(): HasMany
    {
        return $this->hasMany(SumberRekapCatatan::class, 'id_rekap', 'id_rekap');
    }

    public function laporanPerkembangan(): HasMany
    {
        return $this->hasMany(LaporanPerkembangan::class, 'id_rekap', 'id_rekap');
    }
}
