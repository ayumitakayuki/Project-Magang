<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Gaji;
use App\Models\Karyawan;
use Illuminate\Http\Request;

class HistoriSlipGaji extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.histori-slip-gaji';

    public $selected_karyawan = null;
    public $histori = [];

    public function mount(Request $request)
    {
        $this->selected_karyawan = $request->get('karyawan');
        if ($this->selected_karyawan) {
            $this->histori = Gaji::where('id_karyawan', $this->selected_karyawan)
                ->with('details')
                ->orderByDesc('periode_awal')
                ->get();
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }

    public function getKaryawanList()
    {
        return Karyawan::all();
    }
}


