<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapTransferPermata extends Model
{
    protected $table = 'rekap_transfer_permatas';
    public $timestamps = true;

    // HAPUS: protected $connection = 'magang';
    protected $guarded = [];

    public function rows()
    {
        return $this->hasMany(RekapTransferPermataRow::class, 'rekap_transfer_permata_id');
    }
}
