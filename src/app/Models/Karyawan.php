<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    // IKUT MIGRATION: PK = 'id' (default), BUKAN 'id_karyawan'
    // Hapus: protected $primaryKey = 'id_karyawan';

    protected $fillable = [
        'id_karyawan', // tetap boleh simpan kode "KR-..." sebagai identifier bisnis
        'nama',
        'status',
        'lokasi',
        'bagian',
        'jenis_proyek',
        'gaji_setengah_bulan',
        'gaji_lembur',
        'gaji_harian',
        'uang_makan_lembur_malam',
        'uang_makan_lembur_jalan',
        'potongan_bpjs_kesehatan',
        'potongan_tenaga_kerja',
        'potongan_bpjs_kesehatan_tk',
        'faktor_sj',
        'faktor_sabtu',
        'faktor_minggu',
        'faktor_hari_besar',
    ];

    public function absensis(): HasMany
    {
        // kalau tabel absensis punya fk karyawan_id numerik, pakai ini:
        return $this->hasMany(Absensi::class, 'karyawan_id', 'id');
        // kalau memang belum ada kolom itu dan masih lewat 'nama', biarkan sesuai skema tabelmu
    }

    protected static function booted()
    {
        static::creating(function ($karyawan) {
            if (empty($karyawan->id_karyawan)) {
                $karyawan->id_karyawan = 'KR-' . strtoupper(uniqid());
            }
        });
    }

    public function setJenisProyekAttribute($value)
    {
        $this->attributes['jenis_proyek'] = is_string($value) ? trim($value) : $value;
    }

    public function setLokasiAttribute($value)
    {
        $v = is_string($value) ? strtolower(trim($value)) : $value;
        $this->attributes['lokasi'] = in_array($v, ['workshop','proyek'], true) ? $v : null;
    }

    public function rekaps()
    {
        // IKUT MIGRATION: fk = absensi_rekaps.karyawan_id (bigint) -> karyawans.id (bigint)
        return $this->hasMany(AbsensiRekap::class, 'karyawan_id', 'id');
    }
}
