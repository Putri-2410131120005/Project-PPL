<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengguna extends Authenticatable
{
    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';
    public $timestamps = false;

    protected $fillable = [
        'id_role',
        'nama',
        'username',
        'email',
        'password',
        'status_akun',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class, 'id_pengguna', 'id_pengguna');
    }

    public function catatanPerkembangan(): HasMany
    {
        return $this->hasMany(CatatanPerkembangan::class, 'id_pembuat', 'id_pengguna');
    }

    public function catatanPendampingan(): HasMany
    {
        return $this->hasMany(CatatanPendampingan::class, 'id_pembuat', 'id_pengguna');
    }

    public function verifikasiDilakukan(): HasMany
    {
        return $this->hasMany(VerifikasiCatatan::class, 'id_verifikator', 'id_pengguna');
    }

    public function publikasiDilakukan(): HasMany
    {
        return $this->hasMany(VerifikasiCatatan::class, 'id_publikator', 'id_pengguna');
    }

    public function rekapDibuat(): HasMany
    {
        return $this->hasMany(RekapPerkembangan::class, 'id_pembuat', 'id_pengguna');
    }

    public function laporanDibuat(): HasMany
    {
        return $this->hasMany(LaporanPerkembangan::class, 'id_pembuat', 'id_pengguna');
    }

    public function relasiGuruMapelKelas(): HasMany
    {
        return $this->hasMany(RelasiGuruMapelKelas::class, 'id_pengguna', 'id_pengguna');
    }

    public function relasiPendampingSiswa(): HasMany
    {
        return $this->hasMany(RelasiPendampingSiswa::class, 'id_pengguna', 'id_pengguna');
    }
}
