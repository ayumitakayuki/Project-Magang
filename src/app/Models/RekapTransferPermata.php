<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RekapTransferPermata extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank','period_start','period_end','lokasi','proyek',
        'rows_count','total_pembulatan','total_kasbon','total_sisa_kasbon',
        'total_gaji_16_31','total_gaji_15_31','total_transfer',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function rows()
    {
        return $this->hasMany(RekapTransferPermataRow::class);
    }
}
