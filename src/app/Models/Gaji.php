<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gaji extends Model
{
    protected $table = 'gaji';
    
    protected $fillable = [
        'id_karyawan',
        'nama',
        'status',
        'lokasi',
        'jenis_proyek',
        'periode_awal',
        'periode_akhir',
        'total_hari_kerja',
        'total_hari_lembur',
        'upah_per_hari',
        'upah_lembur_per_hari',
        'total_upah',
        'total_lembur',
        'total_gaji'
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }
}