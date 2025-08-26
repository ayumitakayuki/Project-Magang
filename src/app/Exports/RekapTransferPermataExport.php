<?php

namespace App\Exports;

use App\Models\RekapTransferPermata;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;

class RekapTransferPermataExport
{
    /**
     * @param array<int> $batchIds  daftar ID header rekap_transfer_permatas
     * @param bool       $combine   true = gabung jadi 1 PDF multi-halaman
     */
    public function __construct(
        private array $batchIds,
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

    private function buildCombinedPdf(): \Barryvdh\DomPDF\PDF
    {
        $html = '';

        $headers = RekapTransferPermata::with(['rows'])
            ->whereIn('id', $this->batchIds)
            ->orderBy('period_start')
            ->get();

        foreach ($headers as $idx => $h) {
            // periode & label kolom gaji
            $s = $h->period_start instanceof Carbon ? $h->period_start : Carbon::parse($h->period_start);
            $e = $h->period_end   instanceof Carbon ? $h->period_end   : Carbon::parse($h->period_end);

            $labelPeriode = 'Semua periode';
            $labelGaji15  = 'Semua bulan';
            $labelGaji16  = 'Semua bulan';

            if ($s && $e) {
                if ($s->isSameMonth($e)) {
                    $lastDay = $s->copy()->endOfMonth()->day;
                    if ($s->day === 1 && $e->day === 15) {
                        $labelPeriode = '01–15 ' . $s->format('F Y');
                        $labelGaji15  = $s->format('F Y');
                        $labelGaji16  = $s->copy()->subMonth()->format('F Y');
                    } elseif ($s->day >= 16 && $e->day === $lastDay) {
                        $labelPeriode = '16–' . $lastDay . ' ' . $s->format('F Y');
                        $labelGaji15  = $s->format('F Y');
                        $labelGaji16  = $s->format('F Y');
                    } else {
                        $labelPeriode = $s->format('d M Y') . ' – ' . $e->format('d M Y');
                        $labelGaji15  = $s->format('F Y');
                        $labelGaji16  = $s->format('F Y');
                    }
                } else {
                    $labelPeriode = $s->format('d M Y') . ' – ' . $e->format('d M Y');
                    $labelGaji15  = $s->format('M Y') . ' – ' . $e->format('M Y');
                    $labelGaji16  = $labelGaji15;
                }
            }

            // rows + totals (pakai data yang sudah disimpan di tabel rows)
            $rows = $h->rows->map(function ($r) {
                return [
                    'no_id'        => $r->no_id,
                    'bagian'       => $r->bagian,
                    'lokasi'       => $r->lokasi,
                    'project'      => $r->proyek,
                    'nama'         => $r->nama,
                    'pembulatan'   => (float) $r->pembulatan,
                    'kasbon'       => (float) $r->kasbon,
                    'sisa_kasbon'  => (float) $r->sisa_kasbon,
                    'gaji_16_31'   => (float) $r->gaji_16_31,
                    'gaji_15_31'   => (float) $r->gaji_15_31,
                    'transfer'     => (float) $r->transfer,
                ];
            })->values()->all();

            $totals = [
                'pembulatan'  => array_sum(array_column($rows, 'pembulatan')),
                'kasbon'      => array_sum(array_column($rows, 'kasbon')),
                'sisa_kasbon' => array_sum(array_column($rows, 'sisa_kasbon')),
                'gaji_16_31'  => array_sum(array_column($rows, 'gaji_16_31')),
                'gaji_15_31'  => array_sum(array_column($rows, 'gaji_15_31')),
            ];

            // render blade per header → gabungkan
            $html .= View::make('exports.rekap-transfer-permata', [
                'header'       => $h,
                'rows'         => $rows,
                'totals'       => $totals,
                'labelPeriode' => $labelPeriode,
                'labelGaji15'  => $labelGaji15,
                'labelGaji16'  => $labelGaji16,
            ])->render();

            if ($idx < $headers->count() - 1) {
                $html .= '<div style="page-break-after: always;"></div>';
            }
        }
        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')   // <- ganti ke portrait
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
            ]);
    }

    /** Nama file default */
    private function defaultFilename(): string
    {
        if (count($this->batchIds) === 1) {
            $h = RekapTransferPermata::find($this->batchIds[0]);
            if ($h) {
                $s = Carbon::parse($h->period_start)->format('Ymd');
                $e = Carbon::parse($h->period_end)->format('Ymd');
                return "Rekap-Transfer-Permata-{$s}-{$e}.pdf";
            }
        }
        return 'Rekap-Transfer-Permata-Multi.pdf';
    }
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('open')
                ->label('Buka Rekap')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn ($record) => route('filament.admin.pages.rekap-transfer-permata', [
                    'start_date' => \Carbon\Carbon::parse($record->period_start)->format('Y-m-d'),
                    'end_date'   => \Carbon\Carbon::parse($record->period_end)->format('Y-m-d'),
                ]))
                ->openUrlInNewTab(),

            Tables\Actions\Action::make('pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function ($record) {
                    return (new \App\Exports\RekapTransferPermataExport([$record->id]))->download();
                }),

            Tables\Actions\DeleteAction::make(),
        ];
    }
}
