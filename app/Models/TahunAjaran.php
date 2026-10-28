<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';
    protected $primaryKey = 'id_tahun_ajaran';

    protected $fillable = [
        'nama',
        'mulai_ganjil',
        'selesai_ganjil',
        'mulai_genap',
        'selesai_genap',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'mulai_ganjil' => 'date',
            'selesai_ganjil' => 'date',
            'mulai_genap' => 'date',
            'selesai_genap' => 'date',
        ];
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public static function aktif(): ?self
    {
        return static::query()->where('status', 'Aktif')->first();
    }

    public static function resolveLabel(string $label): self
    {
        if (! preg_match('/^(\d{4})\/(\d{4})$/', $label, $matches) || (int) $matches[2] !== (int) $matches[1] + 1) {
            throw new InvalidArgumentException('Format tahun ajaran harus YYYY/YYYY dan berurutan.');
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        $today = now('Asia/Makassar')->startOfDay();
        $start = now('Asia/Makassar')->setDate($startYear, 7, 1)->startOfDay();
        $end = now('Asia/Makassar')->setDate($endYear, 6, 30)->startOfDay();

        return static::query()->firstOrCreate(['nama' => $label], [
            'mulai_ganjil' => "{$startYear}-07-01",
            'selesai_ganjil' => "{$startYear}-12-31",
            'mulai_genap' => "{$endYear}-01-01",
            'selesai_genap' => "{$endYear}-06-30",
            'status' => $today->lt($start) ? 'Akan Datang' : ($today->gt($end) ? 'Selesai' : 'Aktif'),
        ]);
    }

    public function semesterFor(CarbonInterface $date): ?string
    {
        if ($date->betweenIncluded($this->mulai_ganjil, $this->selesai_ganjil)) {
            return 'ganjil';
        }
        if ($date->betweenIncluded($this->mulai_genap, $this->selesai_genap)) {
            return 'genap';
        }

        return null;
    }
}
