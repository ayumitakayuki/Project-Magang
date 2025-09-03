<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekapGajiNonPayroll extends Model
{
    protected $fillable = [
        'period_start','period_end','period_label','range_type',
        'rows_count','total_pembulatan','total_kasbon','total_sisa_kasbon','total_total_setelah_bon',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'rows_count'   => 'integer',
        'total_pembulatan'        => 'integer',
        'total_kasbon'            => 'integer',
        'total_sisa_kasbon'       => 'integer',
        'total_total_setelah_bon' => 'integer',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(RekapGajiNonPayrollRow::class, 'rekap_gaji_non_payroll_id');
    }

    public function refreshTotals(): void
    {
        $this->loadMissing('rows');
        $this->rows_count                 = $this->rows->count();
        $this->total_pembulatan           = (int) $this->rows->sum('pembulatan');
        $this->total_kasbon               = (int) $this->rows->sum('kasbon');
        $this->total_sisa_kasbon          = (int) $this->rows->sum('sisa_kasbon');
        $this->total_total_setelah_bon    = (int) $this->rows->sum('total_setelah_bon');
        $this->save();
    }
}
