<?php

namespace App\Exports;

use App\Models\Gaji;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SlipGajiPdfExport
{
    public function __invoke(Request $request, $id)
    {
        $gaji = Gaji::with('details')->findOrFail($id);

        $pdf = Pdf::loadView('exports.slip-gaji-pdf', [
            'gaji' => $gaji
        ])->setPaper('a5', 'portrait');

        return $pdf->download('Slip-Gaji-' . $gaji->nama . '.pdf');
    }
}
