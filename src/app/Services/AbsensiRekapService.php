<?php

namespace App\Services;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AbsensiRekapService
{
    public function rekapUntukUser(string $userName, string $startDate, string $endDate): array
    {
        $absensis = Absensi::where('name', $userName)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->keyBy('tanggal'); // agar mudah dicek

        $rekap = [
            'S-J' => 0,
            'Sabtu' => 0,
            'Minggu' => 0,
            'Hari Besar' => 0,
            'Tidak Masuk' => 0,
        ];

        $hariBesarList = $this->getHariBesar(); // format: ['2025-01-01', ...]

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        for ($date = $start; $date->lte($end); $date->addDay()) {
            $tgl = $date->toDateString();
            $hari = $date->dayOfWeek;

            $isHariBesar = in_array($tgl, $hariBesarList);
            $absen = $absensis->get($tgl);

            if ($isHariBesar) {
                $rekap['Hari Besar']++;
            } elseif (!$absen) {
                $rekap['Tidak Masuk']++;
            } elseif ($hari === 0) {
                $rekap['Minggu']++;
            } elseif ($hari === 6) {
                $rekap['Sabtu']++;
            } else {
                $rekap['S-J']++;
            }
        }

        return $rekap;
    }

    // Buat contoh daftar hari besar (nanti bisa dinamis dari DB jika mau)
    private function getHariBesar(): array
    {
        return [
            '2025-01-01',
            '2025-04-10',
            '2025-05-01',
            // dst...
        ];
    }
}