<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiRekap extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_id',
        'nama',
        'periode_awal',
        'periode_akhir',
        'sj',
        'sabtu',
        'minggu',
        'hari_besar',
        'tidak_masuk',
        'sisa_jam',
        'total_jam',
        'jumlah_hari',
    ];
}