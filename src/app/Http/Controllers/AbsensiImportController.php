<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

use App\Services\AbsensiRekapService;

class AbsensiImportController extends Controller
{
    public function downloadTemplate()
    {
        $filePath = public_path('templates/template-absensi');
        
        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {
            abort(404, 'Template file not found.');
        }
    }

    public function showRekap()
    {
        $rekapService = new AbsensiRekapService();
        $rekap = $rekapService->rekapUntukUser('Badru Salam');

        return view('rekap-absensi', compact('rekap'));
    }
}



