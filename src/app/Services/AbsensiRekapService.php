<?php

namespace App\Services;

use App\Models\Absensi;
use Carbon\Carbon;

class AbsensiRekapService
{
    public function rekapUntukUser(string $userName, string $startDate, string $endDate): array
    {
        $absensis = \App\Models\Absensi::where('name', $userName)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

        $rekap = [
            'S-J' => 0,
            'Sabtu' => 0,
            'Minggu' => 0,
            'Hari Besar' => 0,
            'Tidak Masuk' => 0,
        ];

        foreach ($absensis as $absen) {
            $tanggal = Carbon::parse($absen->tanggal);
            $hari = $tanggal->dayOfWeek;

            $isHariBesar = in_array($tanggal->toDateString(), $this->getHariBesar());
            $tidakMasuk = empty($absen->masuk_pagi) && empty($absen->masuk_siang) && empty($absen->masuk_lembur);

            if ($isHariBesar) {
                $rekap['Hari Besar']++;
            } elseif ($tidakMasuk) {
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

}
