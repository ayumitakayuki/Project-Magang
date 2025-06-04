<?php

namespace App\Exports;

use App\Models\Absensi;
use App\Models\Karyawan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\BeforeSheet;
use Carbon\Carbon;

class AbsensiExport implements FromArray, WithHeadings, WithEvents, WithMapping
{
    protected $start_date;
    protected $end_date;
    protected $karyawan;
    protected $absensi;

    public function __construct($start_date, $end_date, $id_karyawan)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->karyawan = Karyawan::where('id_karyawan', $id_karyawan)->first();
        $this->absensi = Absensi::where('name', $this->karyawan->nama)
            ->whereBetween('tanggal', [$this->start_date, $this->end_date])
            ->orderBy('tanggal')
            ->get();
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

    public function array(): array
    {
        return $this->absensi->toArray();
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
            'Jam Normal',  // ⬅️ Tambahan
            'Jam Lembur',  // ⬅️ Tambahan
            'Total Jam'    // ⬅️ Tambahan
        ];
    }

    public function map($absen): array
    {
        $jamNormal = 0;
        $jamLembur = 0;

        // Hitung Jam Normal
        if ($absen['masuk_pagi'] && $absen['keluar_siang']) {
            $start = Carbon::createFromFormat('H:i:s', $absen['masuk_pagi']);
            $end = Carbon::createFromFormat('H:i:s', $absen['keluar_siang']);
            $jamNormal += $start->diffInMinutes($end);
        }

        if ($absen['masuk_siang'] && $absen['pulang_kerja']) {
            $start = Carbon::createFromFormat('H:i:s', $absen['masuk_siang']);
            $end = Carbon::createFromFormat('H:i:s', $absen['pulang_kerja']);
            $jamNormal += $start->diffInMinutes($end);
        }

        // Hitung Jam Lembur
        if ($absen['masuk_lembur'] && $absen['pulang_lembur']) {
            $start = Carbon::createFromFormat('H:i:s', $absen['masuk_lembur']);
            $end = Carbon::createFromFormat('H:i:s', $absen['pulang_lembur']);
            $jamLembur += $start->diffInMinutes($end);
        }

        $jamNormal = intdiv($jamNormal, 60);
        $jamLembur = intdiv($jamLembur, 60);
        $totalJam = $jamNormal + $jamLembur;

        // Format sesuai web
        $jamNormalText = $jamNormal > 0 ? $jamNormal . ' jam' : '-';
        $jamLemburText = $jamLembur > 0 ? $jamLembur . ' jam' : '-';
        $totalJamText = $totalJam > 0 ? $totalJam . ' jam' : '-';

        return [
            $absen['tanggal'],
            $absen['masuk_pagi'],
            $absen['keluar_siang'],
            $absen['masuk_siang'],
            $absen['pulang_kerja'],
            $absen['masuk_lembur'],
            $absen['pulang_lembur'],
            $jamNormalText,
            $jamLemburText,
            $totalJamText,
        ];
    }
}
