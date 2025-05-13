<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    //
    protected $fillable = [
        'name',
        'tanggal',
        'masuk_pagi',
        'keluar_siang',
        'masuk_siang',
        'pulang_kerja',
        'masuk_lembur',
        'pulang_lembur',
    ];    

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'name', 'nama');
    }
}
