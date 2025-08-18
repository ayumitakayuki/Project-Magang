<?php
declare(strict_types=1);

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LaporanKasbonExport
{
    /**
     * Render PDF dari blade exports.laporan-kasbon dan kembalikan output biner.
     *
     * @param  string $bulan   Format Y-m (mis. "2025-08")
     * @param  string $q       Query nama karyawan (opsional, bisa kosong)
     * @param  array  $rows    Array baris laporan (lihat LaporanKasbon::$rows)
     * @param  array  $totals  Array totals (lihat LaporanKasbon::$totals)
     * @param  array  $options Opsi DomPDF: ['paper' => 'a4', 'orientation' => 'landscape', 'isRemoteEnabled' => true]
     * @return string          Output PDF (binary string)
     */
    public static function render(string $bulan, string $q, array $rows, array $totals, array $options = []): string
    {
        $paper       = $options['paper']        ?? 'a4';
        $orientation = $options['orientation']  ?? 'landscape';
        $domOptions  = [
            // aktifkan kalau butuh img/logo dari URL
            'isRemoteEnabled' => $options['isRemoteEnabled'] ?? false,
        ];

        $pdf = Pdf::setOptions($domOptions)
            ->loadView('exports.laporan-kasbon', [
                'bulan'  => $bulan,
                'q'      => $q,
                'rows'   => $rows,
                'totals' => $totals,
            ])
            ->setPaper($paper, $orientation);

        return $pdf->output();
    }

    /**
     * Stream PDF ke browser (inline).
     */
    public static function stream(string $bulan, string $q, array $rows, array $totals, array $options = []): StreamedResponse
    {
        $filename = self::filename($bulan);

        return response()->streamDownload(
            fn () => print(self::render($bulan, $q, $rows, $totals, $options)),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Pakai data dari Page LaporanKasbon (Livewire) lalu stream.
     * Contoh pemakaian di Page: return LaporanKasbonExport::fromPage($this)->stream();
     */
    public static function fromPage(object $page): self
    {
        // disediakan untuk chaining ->download() / ->stream()
        // Simpan instance sederhana berisi referensi page
        $inst = new self();
        $inst->page = $page;
        return $inst;
    }

    /**
     * Stream dari page instance yang sudah di-pass via fromPage().
     */
    public function streamFromPage(array $options = []): StreamedResponse
    {
        // pastikan data terbaru
        $this->page->loadData();

        return self::stream(
            (string) $this->page->bulan,
            (string) $this->page->q,
            (array)  $this->page->rows,
            (array)  $this->page->totals,
            $options
        );
    }

    /**
     * Download PDF (force download).
     */
    public static function download(string $bulan, string $q, array $rows, array $totals, array $options = []): Response
    {
        $filename = self::filename($bulan);
        $binary   = self::render($bulan, $q, $rows, $totals, $options);

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Download dari page instance (via fromPage()).
     */
    public function downloadFromPage(array $options = []): Response
    {
        // pastikan data terbaru
        $this->page->loadData();

        return self::download(
            (string) $this->page->bulan,
            (string) $this->page->q,
            (array)  $this->page->rows,
            (array)  $this->page->totals,
            $options
        );
    }

    private static function filename(string $bulan): string
    {
        $safe = preg_replace('/[^0-9\-]/', '', $bulan) ?: now()->format('Y-m');
        return "laporan-kasbon-{$safe}.pdf";
    }

    // ====== Internal storage untuk fromPage() ======
    private object $page;
}
