<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\RekapGajiPeriod;
use App\Services\HoRekapService;
use Barryvdh\DomPDF\Facade\Pdf;

class DetailRekapGajiPeriode extends Page
{
    protected static string $view = 'filament.pages.detail-rekap-gaji-periode';

    // ⬇️ Sembunyikan dari sidebar/navigation
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // ⬇️ Sembunyikan dari Global Search (search bar Filament)
    public static function isGloballySearchable(): bool
    {
        return false;
    }

    public ?RekapGajiPeriod $rekap = null;
    public array $rowsView = [];

    public function mount(?int $id = null): void
    {
        $id = $id ?? (int) request()->query('id');
        abort_unless($id > 0, 404, 'Parameter id wajib.');

        $this->rekap = RekapGajiPeriod::with(['rows', 'user'])->findOrFail($id);

        $this->rowsView = app(\App\Services\HoRekapService::class)->rekapPeriodeLaporan(
            $this->rekap->start_date->format('Y-m-d'),
            $this->rekap->end_date->format('Y-m-d'),
            $this->rekap->selected_pairs
        );

        if (request()->query('export') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.rekap-gaji-periode-pdf', [
                'rekap' => $this->rekap,
                'rows'  => $this->rowsView,
            ])->setPaper('a4', 'portrait');

            $filename = 'Rekap-Gaji-Periode-' .
                $this->rekap->start_date->format('Ymd') . '-' .
                $this->rekap->end_date->format('Ymd') . '.pdf';

            response()->streamDownload(fn () => print($pdf->output()), $filename)->send();
            exit;
        }
    }
}
