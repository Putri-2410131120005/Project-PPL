<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelasiOrangTuaSiswa extends Model
{
    protected $table = 'relasi_orang_tua_siswa';
    protected $primaryKey = 'id_relasi_ortu';
    public $timestamps = false;

    protected $fillable = [
        'id_orang_tua',
        'id_siswa',
        'hubungan',
    ];

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(OrangTua::class, 'id_orang_tua', 'id_orang_tua');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }
}
