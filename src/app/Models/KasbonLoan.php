<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasbonLoan extends Model
{
    protected $fillable = [
        'karyawan_id','tanggal','pokok','tenor','cicilan',
        'sisa_kali','sisa_saldo','status','keterangan'
    ];

    public function payments()
    {
        return $this->hasMany(\App\Models\KasbonPayment::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(\App\Models\Karyawan::class);
    }

    // --- ✅ Realtime helpers (tanpa mengandalkan field tersimpan) ---

    public function getPaidCountAttribute(): int
    {
        // gunakan eager-load kalau ada
        if (array_key_exists('payments_count', $this->attributes)) {
            return (int) $this->attributes['payments_count'];
        }
        return (int) $this->payments()->count();
    }

    public function getPaidNominalAttribute(): float
    {
        // gunakan eager-load kalau ada
        if (array_key_exists('payments_sum_nominal', $this->attributes)) {
            return (float) $this->attributes['payments_sum_nominal'];
        }
        return (float) $this->payments()->sum('nominal');
    }

    public function getSisaKaliRealtimeAttribute(): int
    {
        return max(0, (int) $this->tenor - $this->paid_count);
    }

    public function getSisaSaldoRealtimeAttribute(): float
    {
        return max(0.0, (float) $this->pokok - $this->paid_nominal);
    }

    // --- (opsional) method util untuk apply potongan via slip ---
    public function markPayment(float $amount, ?int $slipId = null, ?string $label = null): KasbonPayment
    {
        $amount = min($amount, (float) $this->sisa_saldo);
        $p = $this->payments()->create([
            'tanggal'       => now()->toDateString(),
            'nominal'       => $amount,
            'sumber'        => 'slip',
            'slip_gaji_id'  => $slipId,
            'periode_label' => $label,
        ]);
        // Biarkan hook di KasbonPayment yang mengurus sisa_saldo/sisa_kali
        return $p;
    }
}
