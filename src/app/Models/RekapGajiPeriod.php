<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapGajiPeriod extends Model
{
    protected $fillable = [
        'start_date','end_date','selected_pairs',
        'total_payroll','total_non_payroll','total_grand',
        'count_payroll','count_non_payroll','count_grand','created_by',
    ];

    protected $casts = [
        'selected_pairs' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function rows() {
        return $this->hasMany(RekapGajiPeriodRow::class, 'rekap_id');
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

}

