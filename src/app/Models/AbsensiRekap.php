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
        'sisa_sj',
        'sisa_sabtu',
        'sisa_minggu',
        'sisa_hari_besar',
        'total_jam',
        'jumlah_hari',
    ];

    public function karyawan()
    {
        // IKUT MIGRATION: fk ke kolom 'id' di karyawans
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'id');
    }
    public function scopeForPeriod($q, int $karyawanId, string $start, string $end) {
        return $q->where('karyawan_id', $karyawanId)
                ->whereDate('periode_awal', $start)
                ->whereDate('periode_akhir', $end)
                ->latest('updated_at');
    }
}
