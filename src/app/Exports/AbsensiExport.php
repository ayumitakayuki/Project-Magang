<?php

namespace App\Exports;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Services\AbsensiRekapService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsensiExport implements FromView, WithEvents, WithStyles, WithColumnWidths
{
    protected $start_date;
    protected $end_date;
    protected $karyawan;
    protected $absensi;
    protected $rekap;
    protected $jumlahHariPerTanggal = [];

    protected $dataExport = [];
    protected $totals = [
        'sj' => 0,
        'sabtu' => 0,
        'minggu' => 0,
        'hari_besar' => 0,
        'tidak_masuk' => 0,
        'jumlah_hari' => 0,
    ];

    public function __construct($start_date, $end_date, $id_karyawan)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->karyawan = Karyawan::where('id_karyawan', $id_karyawan)->first();
        $this->absensi = Absensi::where('name', $this->karyawan->nama)
            ->whereBetween('tanggal', [$this->start_date, $this->end_date])
            ->orderBy('tanggal')
            ->get();

        $this->rekap = (new AbsensiRekapService())->rekapUntukUser(
            $this->karyawan->nama,
            $start_date,
            $end_date
        );
    }

    public function view(): View
    {
        $rows = $this->generateDataExport();

        return view('exports.absensi-excel', [
            'karyawan' => $this->karyawan,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'rows' => $rows,
            'headings' => $this->generateHeadings(),
        ]);
    }

    private function generateHeadings()
    {
        $base = [
            'Tanggal', 'Masuk Pagi', 'Keluar Siang', 'Masuk Siang', 'Pulang Kerja',
            'Masuk Lembur', 'Pulang Lembur', 'SJ',
            'Sabtu', 'Minggu', 'Hari Besar', 'Tidak Masuk'
        ];

        if (strtolower($this->karyawan->status) === 'harian lepas') {
            $base[] = 'Sisa Jam';
            $base[] = 'Jumlah Hari';
        }

        return $base;
    }

    private function generateDataExport()
    {
        $status = strtolower($this->karyawan->status);
        $totalSisaJam = 0;

        foreach ($this->absensi as $absen) {
            $tanggal = $absen['tanggal'];
            $rekapPerTanggal = $this->rekap['per_tanggal'][$this->karyawan->nama][$tanggal] ?? [
                'sj' => '-', 'sabtu' => '-', 'minggu' => '-', 'hari_besar' => '-', 'tidak_masuk' => '-',
            ];

            $jumlahHari = '-';
            if (!empty($rekapPerTanggal['sj']) && $rekapPerTanggal['sj'] !== '-') {
                $jumlahHari = '1 hari';
                $this->totals['jumlah_hari']++;
            }

            if ($status === 'harian lepas' && !empty($this->jumlahHariPerTanggal)) {
                $totalSisaJam = collect($this->jumlahHariPerTanggal)
                    ->filter(fn($item) => ($item['jumlah_hari'] ?? 0) > 0)
                    ->sum('sisa_jam');
            }

            $sjValue = $rekapPerTanggal['sj'] ?? '-';

            $sisaJam = $rekapPerTanggal['sisa_jam'] ?? null;

            if (is_numeric($sisaJam)) {
                $totalSisaJam += (int) $sisaJam;
                $sisaJam = ((int) $sisaJam === 0) ? '-' : $sisaJam . ' jam';
            } else {
                $sisaJam = '-';
            }


            $row = [
                $tanggal,
                $absen['masuk_pagi'] ?? '-',
                $absen['keluar_siang'] ?? '-',
                $absen['masuk_siang'] ?? '-',
                $absen['pulang_kerja'] ?? '-',
                $absen['masuk_lembur'] ?? '-',
                $absen['pulang_lembur'] ?? '-',
                $sjValue,
                $rekapPerTanggal['sabtu'] ?? '-',
                $rekapPerTanggal['minggu'] ?? '-',
                $rekapPerTanggal['hari_besar'] ?? '-',
                $rekapPerTanggal['tidak_masuk'] ?? '-',
            ];

            if ($status === 'harian lepas') {
                $row[] = $sisaJam;
                $row[] = $jumlahHari;
            }

            $this->dataExport[] = $row;
            $this->sumJam($rekapPerTanggal);
        }

        $totalSisaJamFormatted = $totalSisaJam > 0 ? $totalSisaJam . ' jam' : '-';

        $totalRow = [
            'Total', '', '', '', '', '', '',
            $this->formatTotal($this->totals['sj']),
            $this->formatTotal($this->totals['sabtu']),
            $this->formatTotal($this->totals['minggu']),
            $this->formatTotal($this->totals['hari_besar']),
            $this->formatTotal($this->totals['tidak_masuk']),
        ];

        if ($status === 'harian lepas') {
            $totalRow[] = $totalSisaJamFormatted;
            $totalRow[] = $this->totals['jumlah_hari'] . ' hari';

            $grandRow[13] = $this->totals['jumlah_hari'] . ' hari';
        }

        $this->dataExport[] = $totalRow;

        if ($status === 'harian lepas') {
            $grandTotalJam = (
                $this->totals['sj'] +
                $this->totals['sabtu'] +
                $this->totals['minggu'] +
                $this->totals['hari_besar']
            ) - $this->totals['tidak_masuk'] - $totalSisaJam;
        } else {
            $grandTotalJam = (
                $this->totals['sj'] +
                $this->totals['sabtu'] +
                $this->totals['minggu'] +
                $this->totals['hari_besar']
            ) - $this->totals['tidak_masuk'];
        }

        if ($grandTotalJam < 0) $grandTotalJam = 0;

        $grandRow = array_fill(0, 13, '');
        $grandRow[0] = 'Grand Total';
        $grandRow[12] = $grandTotalJam . ' jam';

        if ($status === 'harian lepas') {
            $grandRow[13] = $this->totals['jumlah_hari'] . ' hari';
        }

        $this->dataExport[] = $grandRow;

        return $this->dataExport;
    }

    private function sumJam(array $rekap)
    {
        foreach (['sj', 'sabtu', 'minggu', 'hari_besar', 'tidak_masuk'] as $key) {
            if (isset($rekap[$key]) && $rekap[$key] !== '-') {
                $jam = (int) str_replace(' jam', '', $rekap[$key]);
                $this->totals[$key] += $jam;
            }
        }
    }

    private function formatTotal($jumlahJam)
    {
        return $jumlahJam > 0 ? $jumlahJam . ' jam' : '-';
    }

    public function styles($sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // 1. Tambahkan border ke seluruh data
        $sheet->getStyle("A8:N$highestRow")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ]);

        // 2. Styling untuk header (baris ke-8)
        $sheet->getStyle("A8:N8")->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => 'D9D9D9'], // Abu terang
            ],
            'font' => [
                'bold' => true,
            ],
        ]);

        // 3. Styling Total dan Grand Total
        for ($row = 9; $row <= $highestRow; $row++) {
            $cellValue = strtolower(trim((string) $sheet->getCell("A{$row}")->getValue()));

            if ($cellValue === 'total') {
                $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'color' => ['rgb' => 'E1CC43'], // Kuning emas
                    ],
                    'font' => [
                        'bold' => true,
                    ],
                ]);
            }

            if ($cellValue === 'grand total') {
                $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'color' => ['rgb' => 'B6E7A0'], // Hijau pastel
                    ],
                    'font' => [
                        'bold' => true,
                    ],
                ]);
            }
        }

        return [];
    }



    public function columnWidths(): array
    {
        $columns = [
            'A' => 15, 'B' => 12, 'C' => 12, 'D' => 12,
            'E' => 12, 'F' => 12, 'G' => 12, 'H' => 10,
            'I' => 10, 'J' => 10, 'K' => 10, 'L' => 12
        ];

        if (strtolower($this->karyawan->status) === 'harian lepas') {
            $columns['M'] = 12;
            $columns['N'] = 12;
        }

        return $columns;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->getSheet();
                $sheet->insertNewRowBefore(1, 7);
                $sheet->setCellValue('A1', 'ID Karyawan:');
                $sheet->setCellValue('B1', $this->karyawan->id_karyawan ?? '-');
                $sheet->setCellValue('A2', 'Nama Karyawan:');
                $sheet->setCellValue('B2', $this->karyawan->nama ?? '-');
                $sheet->setCellValue('A3', 'Status:');
                $sheet->setCellValue('B3', $this->karyawan->status ?? '-');
                $sheet->setCellValue('A4', 'Lokasi:');
                $sheet->setCellValue('B4', $this->karyawan->lokasi ?? '-');
                if ($this->karyawan->lokasi === 'proyek') {
                    $sheet->setCellValue('A5', 'Jenis Proyek:');
                    $sheet->setCellValue('B5', $this->karyawan->jenis_proyek ?? '-');
                }
                $sheet->setCellValue('A6', 'Periode:');
                $sheet->setCellValue('B6', \Carbon\Carbon::parse($this->start_date)->format('d-m-Y') . ' s/d ' . \Carbon\Carbon::parse($this->end_date)->format('d-m-Y'));
            },
        ];
    }
}
