<?php

namespace App\Exports;

use App\Models\RekapGajiNonPayroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class RekapGajiNonPayrollExport
{
    /**
     * @param array<int> $headerIds  daftar ID header rekap_gaji_non_payrolls
     * @param bool       $combine    true = gabung jadi 1 PDF multi-halaman
     */
    public function __construct(
        private array $headerIds,
        private bool $combine = true
    ) {}

    /** Stream ke browser (download) */
    public function download(?string $filename = null): StreamedResponse
    {
        $pdf  = $this->buildCombinedPdf();
        $name = $filename ?: $this->defaultFilename();

        return response()->streamDownload(fn () => print($pdf->output()), $name);
    }

    /** Simpan ke path absolut, return path-nya */
    public function saveTo(string $absolutePath): string
    {
        $pdf = $this->buildCombinedPdf();
        $pdf->save($absolutePath);
        return $absolutePath;
    }

    /** Bangun 1 PDF berisi beberapa header (tiap header = 1 section halaman) */
    private function buildCombinedPdf(): \Barryvdh\DomPDF\PDF
    {
        $html = '';

        $headers = RekapGajiNonPayroll::with(['rows' => fn ($q) => $q->orderBy('no_urut')])
            ->whereIn('id', $this->headerIds)
            ->orderBy('period_start')
            ->get();

        foreach ($headers as $idx => $h) {
            // Range label (01–15 / 16–Akhir / custom) + label periode
            $s = $h->period_start instanceof Carbon ? $h->period_start : ($h->period_start ? Carbon::parse($h->period_start) : null);
            $e = $h->period_end   instanceof Carbon ? $h->period_end   : ($h->period_end   ? Carbon::parse($h->period_end)   : null);

            $labelPeriode = 'Semua periode';
            if ($s && $e) {
                if ($s->isSameMonth($e)) {
                    $lastDay = $s->copy()->endOfMonth()->day;
                    if ($s->day === 1 && $e->day === 15) {
                        $labelPeriode = '01–15 ' . $s->format('F Y');
                    } elseif ($s->day >= 16 && $e->day === $lastDay) {
                        $labelPeriode = '16–' . $lastDay . ' ' . $s->format('F Y');
                    } else {
                        $labelPeriode = $s->format('d M Y') . ' – ' . $e->format('d M Y');
                    }
                } else {
                    $labelPeriode = $s->format('d M Y') . ' – ' . $e->format('d M Y');
                }
            }

            // Rows → array siap render (ambil dari tabel rows yang sudah disimpan)
            $rows = $h->rows->map(function ($r) {
                return [
                    'no_urut'          => (int) $r->no_urut,
                    'no_id'            => $r->no_id,
                    'bagian'           => $r->bagian,
                    'lokasi'           => $r->lokasi,
                    'project'          => $r->project,
                    'nama'             => $r->nama,
                    'pembulatan'       => (float) $r->pembulatan,        // dari GRAND total slip
                    'kasbon'           => (float) $r->kasbon,
                    'sisa_kasbon'      => (float) $r->sisa_kasbon,
                    'total_setelah_bon'=> (float) $r->total_setelah_bon, // biasanya = GRAND juga
                ];
            })->values()->all();

            $totals = [
                'pembulatan'        => array_sum(array_column($rows, 'pembulatan')),
                'kasbon'            => array_sum(array_column($rows, 'kasbon')),
                'sisa_kasbon'       => array_sum(array_column($rows, 'sisa_kasbon')),
                'total_setelah_bon' => array_sum(array_column($rows, 'total_setelah_bon')),
            ];

            // Render blade → gabungkan
            $html .= View::make('exports.rekap-gaji-nonpayroll', [
                'header'       => $h,
                'rows'         => $rows,
                'totals'       => $totals,
                'labelPeriode' => $labelPeriode,
            ])->render();

            if ($idx < $headers->count() - 1) {
                $html .= '<div style="page-break-after: always;"></div>';
            }
        }

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait') // compact portrait seperti contohmu
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',      // font UTF-8 friendly
                'isHtml5ParserEnabled' => true,
            ]);
    }

    /** Nama file default */
    private function defaultFilename(): string
    {
        if (count($this->headerIds) === 1) {
            $h = RekapGajiNonPayroll::find($this->headerIds[0]);
            if ($h) {
                $s = $h->period_start ? Carbon::parse($h->period_start)->format('Ymd') : 'NA';
                $e = $h->period_end   ? Carbon::parse($h->period_end)->format('Ymd')   : 'NA';
                return "Rekap-NonPayroll-{$s}-{$e}.pdf";
            }
        }
        return 'Rekap-NonPayroll-Multi.pdf';
    }
}
