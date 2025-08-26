<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapGajiPeriodRow extends Model
{
    protected $fillable = [
        'rekap_id','lokasi','proyek','keterangan','trf','jumlah','jumlah_karyawan',
    ];

    public function period() {
        return $this->belongsTo(RekapGajiPeriod::class, 'rekap_id');
    }
}
