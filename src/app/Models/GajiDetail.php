<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GajiDetail extends Model
{
    protected $fillable = [
        'gaji_id', 'kode', 'keterangan', 'masuk', 'faktor', 'nominal', 'total'
    ];

    public function gaji()
    {
        return $this->belongsTo(Gaji::class);
    }
}
