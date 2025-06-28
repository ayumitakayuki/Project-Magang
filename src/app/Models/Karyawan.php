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
        'gaji_lembur',
        'gaji_harian',
        'uang_makan_lembur_malam',
        'uang_makan_lembur_jalan',
        'potongan_bpjs_kesehatan',
        'potongan_tenaga_kerja',
        'potongan_bpjs_kesehatan_tk',
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
