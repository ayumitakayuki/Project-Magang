<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasbonLoan extends Model
{
    protected $fillable=['karyawan_id','tanggal','pokok','tenor','cicilan','sisa_kali','sisa_saldo','status','keterangan'];
    public function payments()
    {
        return $this->hasMany(\App\Models\KasbonPayment::class);
    }
    public function karyawan(){ return $this->belongsTo(\App\Models\Karyawan::class); }
    public function markPayment(float $amount, ?int $slipId=null, ?string $label=null): KasbonPayment {
        $amount=min($amount,(float)$this->sisa_saldo);
        $p=$this->payments()->create([
        'tanggal'=>now()->toDateString(),'nominal'=>$amount,'sumber'=>'slip',
        'slip_gaji_id'=>$slipId,'periode_label'=>$label,
        ]);
        $this->sisa_saldo=max(0,(float)$this->sisa_saldo-$amount);
        if($this->sisa_kali>0) $this->sisa_kali--;
        if($this->sisa_saldo<=0){$this->status='lunas';$this->sisa_kali=0;}
        $this->save();
        return $p;
    }

}
