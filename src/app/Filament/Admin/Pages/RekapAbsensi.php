<?php

namespace App\Filament\Admin\Pages;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Services\AbsensiRekapService;
use Filament\Pages\Page;
use Illuminate\Http\Request;

class RekapAbsensi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $title = 'Rekapitulasi Absensi';
    protected static string $view = 'filament.pages.rekap-absensi';

    public array $rekap = [];
    public $data_harian = [];

    public ?string $start_date = null;
    public ?string $end_date = null;

    public ?string $selected_id = null;
    public ?string $selected_name = null;

    public $all_karyawan = null;

    public function mount(Request $request): void
    {
        $this->all_karyawan = Karyawan::get(['id_karyawan', 'nama']);

        $keyword = $request->query('karyawan_keyword');

        if ($keyword) {
            $matched = Karyawan::where('id_karyawan', $keyword)
                ->orWhere('nama', 'like', '%' . $keyword . '%')
                ->first();

            $this->selected_id = $matched?->id_karyawan;
            $this->selected_name = $matched?->nama;
        }

        $this->start_date = $request->query('start_date') ?? now()->subMonth()->toDateString();
        $this->end_date = $request->query('end_date') ?? now()->toDateString();

        $this->loadRekap();
    }

    public function loadRekap(): void
    {
        if ($this->selected_name) {
            $this->rekap = (new AbsensiRekapService())->rekapUntukUser(
                $this->selected_name,
                $this->start_date,
                $this->end_date
            );

            $this->data_harian = Absensi::where('name', $this->selected_name)
                ->whereBetween('tanggal', [$this->start_date, $this->end_date])
                ->orderBy('tanggal')
                ->get();
        }
    }
}
