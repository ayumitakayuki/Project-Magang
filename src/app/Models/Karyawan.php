<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    protected $fillable = ['nama', 'nip', 'jabatan', 'email'];

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class, 'name', 'nama');
    }
}
