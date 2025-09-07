<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasbonPayment extends Model
{
    protected $fillable = [
        'kasbon_loan_id','tanggal',
        'periode_awal','periode_akhir','periode_label',
        'nominal','sumber','slip_gaji_id','catatan',
    ];
    protected $casts = [
        'tanggal'       => 'date',
        'periode_awal'  => 'date',
        'periode_akhir' => 'date',
    ];
    protected $guarded = []; 
    // 🔧 TEGASKAN foreign key-nya agar tidak pernah mencoba pakai "loan_id"
    public function loan()
    {
        return $this->belongsTo(KasbonLoan::class, 'kasbon_loan_id');
    }

    public function slip()
    {
        return $this->belongsTo(Gaji::class, 'slip_gaji_id');
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            $loan = $payment->loan()->lockForUpdate()->first();
            if (!$loan) return;

            $amount = min((float) $payment->nominal, (float) $loan->sisa_saldo);
            $loan->sisa_saldo = max(0, (float) $loan->sisa_saldo - $amount);

            if ($loan->sisa_kali > 0) {
                $loan->sisa_kali -= 1;
            }
            if ($loan->sisa_saldo <= 0) {
                $loan->sisa_saldo = 0;
                $loan->sisa_kali  = 0;
                $loan->status     = 'lunas';
            }
            $loan->save();
        });

        static::updating(function ($payment) {
            $originalNominal = (float) $payment->getOriginal('nominal');
            $loan = $payment->loan()->lockForUpdate()->first();
            if (!$loan) return;

            // kembalikan nominal lama
            $loan->sisa_saldo += $originalNominal;
            if ($loan->status === 'lunas') $loan->status = 'aktif';
            $loan->sisa_kali += 1;

            // apply nominal baru
            $amount = min((float) $payment->nominal, (float) $loan->sisa_saldo);
            $loan->sisa_saldo = max(0, (float) $loan->sisa_saldo - $amount);
            $loan->sisa_kali  = max(0, $loan->sisa_kali - 1);
            if ($loan->sisa_saldo <= 0) {
                $loan->sisa_saldo = 0;
                $loan->sisa_kali  = 0;
                $loan->status     = 'lunas';
            }
            $loan->save();
        });

        static::deleted(function ($payment) {
            $loan = $payment->loan()->lockForUpdate()->first();
            if (!$loan) return;

            $loan->sisa_saldo += (float) $payment->nominal;
            $loan->sisa_kali  += 1;
            if ($loan->sisa_saldo > 0 && $loan->status === 'lunas') {
                $loan->status = 'aktif';
            }
            $loan->save();
        });
    }
}
