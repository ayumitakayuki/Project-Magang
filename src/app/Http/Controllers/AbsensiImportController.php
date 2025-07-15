<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

use App\Services\AbsensiRekapService;

class AbsensiImportController extends Controller
{
    public function downloadTemplate()
    {
        $fileName = 'template-absensi.xlsx';
        $filePath = public_path("templates/{$fileName}");

        if (file_exists($filePath)) {
            return response()->download($filePath, $fileName);
        } else {
            abort(404, 'Template file not found.');
        }
    }
}



