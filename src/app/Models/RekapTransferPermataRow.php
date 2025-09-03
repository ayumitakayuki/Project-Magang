<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapTransferPermataRow extends Model
{
    protected $table = 'rekap_transfer_permata_rows';
    public $timestamps = true;

    // HAPUS: protected $connection = 'magang';
    protected $guarded = [];

    public function batch()
    {
        return $this->belongsTo(RekapTransferPermata::class, 'rekap_transfer_permata_id');
    }
}
