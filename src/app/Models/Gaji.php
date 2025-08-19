<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gaji extends Model
{
   protected $table = 'gaji';

    protected $fillable = [
        'id_karyawan', 'nama', 'status', 'lokasi', 'jenis_proyek',
        'periode_awal', 'periode_akhir', 'tipe_pembayaran',
    ];

    public function details()
    {
        return $this->hasMany(GajiDetail::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }
    protected $casts = [
        'periode_awal' => 'datetime',
        'periode_akhir' => 'datetime',
    ];

}