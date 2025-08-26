<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RekapTransferPermataRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'rekap_transfer_permata_id','no_urut',
        'no_id','bagian','lokasi','proyek','nama',
        'pembulatan','kasbon','sisa_kasbon','gaji_16_31','gaji_15_31','transfer',
    ];

    public function batch()
    {
        return $this->belongsTo(RekapTransferPermata::class, 'rekap_transfer_permata_id');
    }
}
