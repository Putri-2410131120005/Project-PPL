<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrangTua extends Model
{
    protected $table = 'orang_tua';
    protected $primaryKey = 'id_orang_tua';
    public $timestamps = false;

    protected $fillable = [
        'id_pengguna',
        'no_telepon',
        'alamat',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function relasiSiswa(): HasMany
    {
        return $this->hasMany(RelasiOrangTuaSiswa::class, 'id_orang_tua', 'id_orang_tua');
    }
}
