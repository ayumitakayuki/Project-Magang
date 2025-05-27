<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    // ✅ Tambahkan properti fillable
    protected $fillable = [
        'id_karyawan',
        'nama',
        'status',
        'lokasi',
        'jenis_proyek',
        'gaji_perbulan',
        'gaji_lembur_reguler',
        'gaji_lembur_sabtu',
        'gaji_lembur_minggu_haribesar',
        'gaji_harian',
    ];

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class, 'name', 'nama');
    }

    protected static function booted()
    {
        static::creating(function ($karyawan) {
            if (empty($karyawan->id_karyawan)) {
                $karyawan->id_karyawan = 'KR-' . strtoupper(uniqid());
            }
        });
    }
}
