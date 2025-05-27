<?php

namespace App\Services;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AbsensiRekapService
{
    public function rekapUntukUser(string $nama, string $start_date, string $end_date): array
    {
        $absensis = Absensi::where('name', $nama)
            ->whereBetween('tanggal', [$start_date, $end_date])
            ->get()
            ->groupBy('tanggal');

        // Ambil daftar tanggal merah (hari besar)
        $liburResponse = Http::get('https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json');
        $libur = $liburResponse->successful() ? $liburResponse->json() : [];

        $rekap = [
            'sj' => 0,
            'sabtu' => 0,
            'minggu' => 0,
            'hari_besar' => 0,
            'tidak_masuk' => 0,
            'per_tanggal' => [],
        ];

        $period = new \DatePeriod(
            new \DateTime($start_date),
            new \DateInterval('P1D'),
            (new \DateTime($end_date))->modify('+1 day')
        );

        foreach ($period as $date) {
            $tanggalStr = $date->format('Y-m-d');
            $dayName = $date->format('l');
            $isLibur = array_key_exists($tanggalStr, $libur);

            $record = $absensis->get($tanggalStr)?->first(); // ambil satu record jika ada

            $hasData = $record && (
                $record->masuk_pagi ||
                $record->keluar_siang ||
                $record->masuk_siang ||
                $record->pulang_kerja ||
                $record->masuk_lembur ||
                $record->pulang_lembur
            );

            $kategori = null;

            if (!$hasData) {
                $rekap['tidak_masuk']++;
                $kategori = 'tidak_masuk';
                $jumlahJam = '-';
            } elseif ($isLibur) {
                $jumlahJam = $this->hitungJamKerja($record); // full
                $rekap['hari_besar'] += $jumlahJam;
                $kategori = 'hari_besar';
            } elseif ($dayName == 'Saturday') {
                $jumlahJam = $this->hitungJamKerja($record); // full
                $rekap['sabtu'] += $jumlahJam;
                $kategori = 'sabtu';
            } elseif ($dayName == 'Sunday') {
                $jumlahJam = $this->hitungJamKerja($record); // full
                $rekap['minggu'] += $jumlahJam;
                $kategori = 'minggu';
            } else {
                // S-J → hanya lembur
                $jumlahJam = $this->hitungJamLemburSaja($record); // hanya lembur
                $rekap['sj'] += $jumlahJam;
                $kategori = 'sj';
            }

            $rekap['per_tanggal'][$tanggalStr] = [
                'sj' => $kategori === 'sj' ? $jumlahJam . ' jam' : '-',
                'sabtu' => $kategori === 'sabtu' ? $jumlahJam . ' jam' : '-',
                'minggu' => $kategori === 'minggu' ? $jumlahJam . ' jam' : '-',
                'hari_besar' => $kategori === 'hari_besar' ? $jumlahJam . ' jam' : '-',
                'tidak_masuk' => $kategori === 'tidak_masuk' ? '1 hari' : '-',
            ];
        }

        // Format total akhir
        $rekap['sj'] .= ' jam';
        $rekap['sabtu'] .= ' jam';
        $rekap['minggu'] .= ' jam';
        $rekap['hari_besar'] .= ' jam';

        return $rekap;
    }

    public function rekapSemuaUser($start, $end)
    {
        $data = Absensi::whereBetween('tanggal', [$start, $end])->get();

        $rekap = [
            'per_tanggal' => [],
            'sj' => 0,
            'sabtu' => 0,
            'minggu' => 0,
            'hari_besar' => 0,
            'tidak_masuk' => 0,
        ];

        foreach ($data as $absen) {
            $tanggal = $absen->tanggal;
            $rekap['per_tanggal'][$tanggal]['sj'] ??= 0;
            $rekap['per_tanggal'][$tanggal]['sj'] += $absen->jam_sj ?? 0;
            // dan seterusnya sesuai format jam kerja kamu
        }

        return $rekap;
    }

    private function hitungJamKerja(?Absensi $absensi): int
    {
        if (!$absensi) return 0;

        $totalMinutes = 0;

        $jamPairs = [
            ['masuk_pagi', 'keluar_siang'],
            ['masuk_siang', 'pulang_kerja'],
            ['masuk_lembur', 'pulang_lembur'],
        ];

        foreach ($jamPairs as [$masuk, $keluar]) {
            if ($absensi->$masuk && $absensi->$keluar) {
                $start = Carbon::createFromFormat('H:i:s', $absensi->$masuk);
                $end = Carbon::createFromFormat('H:i:s', $absensi->$keluar);
                $diff = $start->diffInMinutes($end);
                $totalMinutes += $diff;
            }
        }

        return intdiv($totalMinutes, 60); // konversi ke jam
    }

    private function hitungJamLemburSaja(?Absensi $absensi): int
    {
        if (!$absensi || !$absensi->masuk_lembur || !$absensi->pulang_lembur) return 0;

        $start = Carbon::createFromFormat('H:i:s', $absensi->masuk_lembur);
        $end = Carbon::createFromFormat('H:i:s', $absensi->pulang_lembur);
        return intdiv($start->diffInMinutes($end), 60); // hanya lembur
    }
}
