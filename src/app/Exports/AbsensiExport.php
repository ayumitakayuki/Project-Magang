<?php

namespace App\Exports;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Services\AbsensiRekapService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class AbsensiExport implements FromCollection, WithHeadings, WithEvents, WithStyles, WithColumnWidths
{
    protected $start_date;
    protected $end_date;
    protected $karyawan;
    protected $absensi;
    protected $rekap;

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

        // PANGGIL SERVICE Rekap
        $this->rekap = (new \App\Services\AbsensiRekapService())->rekapUntukUser(
            $this->karyawan->nama,
            $start_date,
            $end_date
        );
    }
    public function collection()
    {
        $status = strtolower($this->karyawan->status); // ambil status

        foreach ($this->absensi as $absen) {
            $tanggal = $absen['tanggal'];
            $rekapPerTanggal = $this->rekap['per_tanggal'][$this->karyawan->nama][$tanggal] ?? [
                'sj' => '-',
                'sabtu' => '-',
                'minggu' => '-',
                'hari_besar' => '-',
                'tidak_masuk' => '-',
            ];

            // --- JUMLAH HARI dari SJ ---
            $jumlahHari = '-';
            if (!empty($rekapPerTanggal['sj']) && $rekapPerTanggal['sj'] !== '-') {
                // Kalau SJ tidak kosong
                $jumlahHari = '1 hari';
                $this->totals['jumlah_hari']++;
            }

            // --- LOGIC PENGOSONGAN SJ UNTUK HARIAN LEPAS ---
            $sjValue = $rekapPerTanggal['sj'] ?? '-';
            if ($status === 'harian lepas') {
                $sjValue = '-'; // kosongkan SJ kalau harian lepas
            }

            // --- MASUKKAN DATA KE EXPORT ---
            $this->dataExport[] = [
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
                $jumlahHari,
            ];

            $this->sumJam($rekapPerTanggal);
        }

        // --- TOTAL ROW ---
        $this->dataExport[] = [
            'Total', '', '', '', '', '', '',
            ($status === 'harian lepas' ? '-' : $this->formatTotal($this->totals['sj'])),
            $this->formatTotal($this->totals['sabtu']),
            $this->formatTotal($this->totals['minggu']),
            $this->formatTotal($this->totals['hari_besar']),
            $this->formatTotal($this->totals['tidak_masuk']),
            $this->totals['jumlah_hari'] . ' hari'
        ];

        // --- GRAND TOTAL ROW ---
        if ($status === 'harian lepas') {
            $grandTotalJam = (
                $this->totals['sabtu'] +
                $this->totals['minggu'] +
                $this->totals['hari_besar']
            ) - $this->totals['tidak_masuk'];
        } else {
            $grandTotalJam = (
                $this->totals['sj'] +
                $this->totals['sabtu'] +
                $this->totals['minggu'] +
                $this->totals['hari_besar']
            ) - $this->totals['tidak_masuk'];
        }

        $this->dataExport[] = [
            'Grand Total', '', '', '', '', '', '',
            '',
            '',
            '',
            '',
            $grandTotalJam . ' jam',
            $this->totals['jumlah_hari'] . ' hari'
        ];

        return new \Illuminate\Support\Collection($this->dataExport);
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Masuk Pagi',
            'Keluar Siang',
            'Masuk Siang',
            'Pulang Kerja',
            'Masuk Lembur',
            'Pulang Lembur',
            'SJ',
            'Sabtu',
            'Minggu',
            'Hari Besar',
            'Tidak Masuk',
            'Jumlah Hari',
        ];
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

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A7:M7')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Border untuk semua data sampai baris akhir
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A7:M$highestRow")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Freeze Header
        $sheet->freezePane('A8');

        // Rata tengah kolom 'Jumlah Hari'
        $sheet->getStyle('M8:M' . $highestRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 12,
            'C' => 12,
            'D' => 12,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 10,
            'I' => 10,
            'J' => 10,
            'K' => 10,
            'L' => 12,
            'M' => 12,
        ];
    }


    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->getSheet();
                $sheet->insertNewRowBefore(1, 6);

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
            },
        ];
    }
}
