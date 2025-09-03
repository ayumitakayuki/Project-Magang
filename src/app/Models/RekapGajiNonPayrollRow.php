<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapGajiNonPayrollRow extends Model
{
    protected $fillable = [
        'rekap_gaji_non_payroll_id',
        'no_urut','no_id','nama','bagian','project','lokasi','cd','plus',
        'pembulatan','kasbon','sisa_kasbon','total_setelah_bon','total_slip','subtotal',
        'period_start','period_end','period_label','range_type',
    ];

    protected $casts = [
        'no_urut'           => 'integer',
        'pembulatan'        => 'integer',
        'kasbon'            => 'integer',
        'sisa_kasbon'       => 'integer',
        'total_setelah_bon' => 'integer',
        'total_slip'        => 'integer',
        'subtotal'          => 'integer',
        'period_start'      => 'date',
        'period_end'        => 'date',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(RekapGajiNonPayroll::class, 'rekap_gaji_non_payroll_id');
    }
}
