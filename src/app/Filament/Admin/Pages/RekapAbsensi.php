<?php

namespace App\Filament\Admin\Pages;

use App\Models\Absensi;
use App\Services\AbsensiRekapService;
use Filament\Pages\Page;
use Illuminate\Http\Request;

class RekapAbsensi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $title = 'Rekapitulasi Absensi';
    protected static string $view = 'filament.pages.rekap-absensi';

    public array $rekap = [];
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $selected_name = null;
    public array $all_names = [];

    public function mount(Request $request): void
    {
        // Ambil semua nama unik
        $this->all_names = Absensi::select('name')->distinct()->pluck('name')->toArray();

        $this->selected_name = $request->query('name') ?? $this->all_names[0] ?? null;
        $this->start_date = $request->query('start_date') ?? now()->subMonth()->toDateString();
        $this->end_date = $request->query('end_date') ?? now()->toDateString();

        if ($this->selected_name) {
            $this->rekap = (new AbsensiRekapService())->rekapUntukUser(
                $this->selected_name,
                $this->start_date,
                $this->end_date
            );
        }
    }
}
