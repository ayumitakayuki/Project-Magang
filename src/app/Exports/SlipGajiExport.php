<?php

namespace App\Exports;

use App\Models\Gaji;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SlipGajiExport implements FromView
{
    protected $gaji_id;

    public function __construct($gaji_id)
    {
        $this->gaji_id = $gaji_id;
    }

    public function view(): View
    {
        $gaji = Gaji::with('details')->findOrFail($this->gaji_id);

        return view('exports.slip-gaji', [
            'gaji' => $gaji,
        ]);
    }
}

