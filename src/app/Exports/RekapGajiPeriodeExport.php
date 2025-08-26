<?php

namespace App\Exports;

use App\Models\RekapGajiPeriod;
use App\Services\HoRekapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapGajiPeriodeExport
{
    /**
     * @param array<int> $rekapIds  daftar ID header rekap
     * @param bool $combine         true = gabung jadi 1 PDF multi-halaman
     */
    public function __construct(
        private array $rekapIds,
        private bool $combine = true
    ) {}

    /** Stream ke browser (download) */
    public function download(?string $filename = null): StreamedResponse
    {
        if (count($this->rekapIds) === 1 || $this->combine === true) {
            // satu file pdf (1 atau multi halaman)
            $pdf = $this->buildCombinedPdf();
            $name = $filename ?: $this->defaultFilename();
            return response()->streamDownload(fn () => print($pdf->output()), $name);
        }

        // jika mau 1 file per rekap (jarang dipakai)
        // NOTE: DomPDF tidak support zip out-of-the-box — biasanya pakai ZipArchive.
        // Di sini kita tetap kembalikan single combined untuk kesederhanaan.
        $pdf = $this->buildCombinedPdf();
        $name = $filename ?: $this->defaultFilename();
        return response()->streamDownload(fn () => print($pdf->output()), $name);
    }

    /** Simpan ke storage path yang diberikan, return full path */
    public function saveTo(string $absolutePath): string
    {
        $pdf = $this->buildCombinedPdf();
        $pdf->save($absolutePath);
        return $absolutePath;
    }

    /** Bangun 1 PDF berisi 1..N halaman. */
    private function buildCombinedPdf(): \Barryvdh\DomPDF\PDF
    {
        $html = '';

        $rekaps = RekapGajiPeriod::with('user')
            ->whereIn('id', $this->rekapIds)
            ->orderBy('start_date')
            ->get();

        foreach ($rekaps as $idx => $h) {
            // ambil rows agregasi agar sama persis dengan mode edit
            $rows = app(HoRekapService::class)->rekapPeriodeLaporan(
                $h->start_date->format('Y-m-d'),
                $h->end_date->format('Y-m-d'),
                $h->selected_pairs
            );

            // render blade ke string
            $html .= View::make('exports.rekap-gaji-periode-pdf', [
                'rekap' => $h,
                'rows'  => $rows,
            ])->render();

            // page break antar periode
            if ($idx < $rekaps->count() - 1) {
                $html .= '<div style="page-break-after: always;"></div>';
            }
        }

        // DomPDF dari HTML gabungan
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    /** Nama file default kalau 1 atau banyak periode */
    private function defaultFilename(): string
    {
        if (count($this->rekapIds) === 1) {
            $h = RekapGajiPeriod::find($this->rekapIds[0]);
            if ($h) {
                return 'Rekap-Gaji-Periode-' .
                    $h->start_date->format('Ymd') . '-' .
                    $h->end_date->format('Ymd') . '.pdf';
            }
        }
        return 'Rekap-Gaji-Periode-Multi.pdf';
    }
}
