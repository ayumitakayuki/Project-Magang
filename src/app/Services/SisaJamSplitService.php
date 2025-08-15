<?php

namespace App\Services;

class SisaJamSplitService
{
    public function splitFromPerTanggal(array $perTanggal): array
    {
        $sisaSj = $sisaSabtu = $sisaMinggu = $sisaHB = 0.0;

        foreach ($perTanggal as $tanggal => $row) {
            $sisa = (float)($row['sisa_jam'] ?? 0);
            if ($sisa <= 0) continue;

            // Tentukan kategori dari kolom yang tidak '-'
            if (!empty($row['sj']) && $row['sj'] !== '-') {
                $sisaSj += $sisa;
            } elseif (!empty($row['sabtu']) && $row['sabtu'] !== '-') {
                $sisaSabtu += $sisa;
            } elseif (!empty($row['minggu']) && $row['minggu'] !== '-') {
                $sisaMinggu += $sisa;
            } elseif (!empty($row['hari_besar']) && $row['hari_besar'] !== '-') {
                $sisaHB += $sisa;
            }
            // jika semua '-' (harusnya tidak), abaikan
        }

        $total = $sisaSj + $sisaSabtu + $sisaMinggu + $sisaHB;

        return [
            'sisa_sj'         => round($sisaSj, 2),
            'sisa_sabtu'      => round($sisaSabtu, 2),
            'sisa_minggu'     => round($sisaMinggu, 2),
            'sisa_hari_besar' => round($sisaHB, 2),
            'sisa_jam'        => round($total, 2), // untuk blade tetap baca ini
        ];
    }
}
