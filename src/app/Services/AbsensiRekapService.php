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

        $absensis = Absensi::with('karyawan') // ⬅️ tambahkan ini
            ->where('name', $nama)
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
                $jumlahJam = 8; // diasumsikan 8 jam default
                $rekap['tidak_masuk'] += $jumlahJam;
                $kategori = 'tidak_masuk';
            } elseif ($isLibur) {
                $jumlahJam = $this->hitungJamKerja($record);
                $rekap['hari_besar'] += $jumlahJam;
                $kategori = 'hari_besar';
            } elseif ($dayName == 'Saturday') {
                $jumlahJam = $this->hitungJamKerja($record);
                $rekap['sabtu'] += $jumlahJam;
                $kategori = 'sabtu';
            } elseif ($dayName == 'Sunday') {
                $jumlahJam = $this->hitungJamKerja($record);
                $rekap['minggu'] += $jumlahJam;
                $kategori = 'minggu';
            } else {
                $jumlahJam = $this->hitungJamLemburSaja($record);
                $rekap['sj'] += $jumlahJam;
                $kategori = 'sj';

                $isHarianLepas = strtolower($record->karyawan->status ?? '') === 'harian lepas';

                if ($isHarianLepas && $record->masuk_pagi && $record->pulang_kerja) {
                    try {
                        $masuk = Carbon::parse($record->masuk_pagi);
                        $pulang = Carbon::parse($record->pulang_kerja);

                        if ($masuk->format('H:i') <= '08:30' &&
                            $pulang->format('H:i') >= '15:30' &&
                            $pulang->format('H:i') < '17:00') {

                            // Tambah 2 jam ke tidak masuk
                            $rekap['tidak_masuk'] += 2;
                            $rekap['per_tanggal'][$nama][$tanggalStr]['tidak_masuk'] = '2 jam';
                        } else {
                            $rekap['per_tanggal'][$nama][$tanggalStr]['tidak_masuk'] = '-';
                        }
                    } catch (\Exception $e) {
                        $rekap['per_tanggal'][$nama][$tanggalStr]['tidak_masuk'] = '-';
                    }
                } else {
                    $rekap['per_tanggal'][$nama][$tanggalStr]['tidak_masuk'] = '-';
                }
            }

            // Format untuk ditampilkan
            $rekap['per_tanggal'][$nama][$tanggalStr] = [
                'sj' => $kategori === 'sj' ? $jumlahJam . ' jam' : '-',
                'sabtu' => $kategori === 'sabtu' ? $jumlahJam . ' jam' : '-',
                'minggu' => $kategori === 'minggu' ? $jumlahJam . ' jam' : '-',
                'hari_besar' => $kategori === 'hari_besar' ? $jumlahJam . ' jam' : '-',
                'tidak_masuk' => $kategori === 'tidak_masuk' ? 8 : '-', 
            ];
        }

        // Format total akhir
        $rekap['sj'] .= ' jam';
        $rekap['sabtu'] .= ' jam';
        $rekap['minggu'] .= ' jam';
        $rekap['hari_besar'] .= ' jam';

        return $rekap;
    }

    public function rekapSemuaUser($start, $end, $nama_karyawan = null, $status_karyawan = null, $lokasi = null, $jenis_proyek = null)
    {
        $query = Absensi::whereBetween('tanggal', [$start, $end])
            ->with('karyawan');

        if ($nama_karyawan) {
            $query->whereIn('name', $nama_karyawan);
        }

        if ($status_karyawan && $status_karyawan != 'all') {
            $query->whereHas('karyawan', fn($q) => $q->where('status', $status_karyawan));
        }

        if ($lokasi) {
            $query->whereHas('karyawan', fn($q) => $q->where('lokasi', $lokasi));
        }

        if ($jenis_proyek) {
            $query->whereHas('karyawan', fn($q) => $q->where('jenis_proyek', $jenis_proyek));
        }

        // Group by nama → tanggal
        $data = $query->get()->groupBy(['name', 'tanggal']);

        // Ambil daftar tanggal merah (hari besar)
        $liburResponse = Http::get('https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json');
        $libur = $liburResponse->successful() ? $liburResponse->json() : [];

        $rekap = [
            'per_user' => [],
            'grand_total' => [
                'sj' => 0,
                'sabtu' => 0,
                'minggu' => 0,
                'hari_besar' => 0,
                'tidak_masuk' => 0,
            ],
            'per_tanggal' => [],
        ];

        $period = new \DatePeriod(
            new \DateTime($start),
            new \DateInterval('P1D'),
            (new \DateTime($end))->modify('+1 day')
        );

        foreach ($period as $date) {
            $tanggalStr = $date->format('Y-m-d');
            $dayName = $date->format('l');
            $isLibur = array_key_exists($tanggalStr, $libur);

            // Loop semua karyawan yang pernah absen
            foreach ($data as $nama => $absensiHarian) {
                $record = $absensiHarian->get($tanggalStr)?->first();

                $hasData = $record && (
                    $record->masuk_pagi ||
                    $record->keluar_siang ||
                    $record->masuk_siang ||
                    $record->pulang_kerja ||
                    $record->masuk_lembur ||
                    $record->pulang_lembur
                );

                $kategori = null;
                $jumlahJam = 0;

                if (!$record || !$hasData) {
                    // Tidak ada record atau record kosong
                    $rekap['grand_total']['tidak_masuk'] += 8;
                    $rekap['per_user'][$nama]['tidak_masuk'] = ($rekap['per_user'][$nama]['tidak_masuk'] ?? 0) + 8;
                    $kategori = 'tidak_masuk';
                    $jumlahJam = 8;
                } elseif ($isLibur) {
                    $jumlahJam = $this->hitungJamKerja($record);
                    $rekap['grand_total']['hari_besar'] += $jumlahJam;
                    $rekap['per_user'][$nama]['hari_besar'] = ($rekap['per_user'][$nama]['hari_besar'] ?? 0) + $jumlahJam;
                    $kategori = 'hari_besar';
                } elseif ($dayName == 'Saturday') {
                    $jumlahJam = $this->hitungJamKerja($record);
                    $rekap['grand_total']['sabtu'] += $jumlahJam;
                    $rekap['per_user'][$nama]['sabtu'] = ($rekap['per_user'][$nama]['sabtu'] ?? 0) + $jumlahJam;
                    $kategori = 'sabtu';
                } elseif ($dayName == 'Sunday') {
                    $jumlahJam = $this->hitungJamKerja($record);
                    $rekap['grand_total']['minggu'] += $jumlahJam;
                    $rekap['per_user'][$nama]['minggu'] = ($rekap['per_user'][$nama]['minggu'] ?? 0) + $jumlahJam;
                    $kategori = 'minggu';
                } else {
                    $jumlahJam = $this->hitungJamLemburSaja($record);
                    $rekap['grand_total']['sj'] += $jumlahJam;
                    $rekap['per_user'][$nama]['sj'] = ($rekap['per_user'][$nama]['sj'] ?? 0) + $jumlahJam;
                    $kategori = 'sj';

                    // Tambahan logika: jika status Harian Lepas dan pulang sebelum 17:00 tapi ≥ 15:30 → tambahkan 2 jam tidak masuk
                    $isHarianLepas = $record->karyawan?->status === 'harian lepas';

                    if ($isHarianLepas && $record->masuk_pagi && $record->pulang_kerja) {
                        $masuk = Carbon::parse($record->masuk_pagi);
                        $pulang = Carbon::parse($record->pulang_kerja);

                        if ($masuk->format('H:i') <= '08:30' && $pulang->format('H:i') >= '15:30' && $pulang->format('H:i') < '17:00') {
                            // Tambahkan 2 jam ke tidak masuk
                            $rekap['grand_total']['tidak_masuk'] += 2;
                            $rekap['per_user'][$nama]['tidak_masuk'] = ($rekap['per_user'][$nama]['tidak_masuk'] ?? 0) + 2;
                            $rekap['per_tanggal'][$nama][$tanggalStr]['tidak_masuk'] = '2 jam';
                        } elseif ($rekap['per_tanggal'][$nama][$tanggalStr]['tidak_masuk'] ?? null === null) {
                            // Pastikan tidak_masuk tetap ditandai "-"
                            $rekap['per_tanggal'][$nama][$tanggalStr]['tidak_masuk'] = '-';
                        }
                    }
                }
                $rekap['per_tanggal'][$nama][$tanggalStr] = [
                    'sj' => $kategori === 'sj' ? $jumlahJam . ' jam' : '-',
                    'sabtu' => $kategori === 'sabtu' ? $jumlahJam . ' jam' : '-',
                    'minggu' => $kategori === 'minggu' ? $jumlahJam . ' jam' : '-',
                    'hari_besar' => $kategori === 'hari_besar' ? $jumlahJam . ' jam' : '-',
                    'tidak_masuk' => $kategori === 'tidak_masuk' ? '8 jam' : '-',
                ];
            }
        }

        return $rekap;
    }

    private function hitungJamLemburSaja(?Absensi $absensi): int
    {
        if (!$absensi || !$absensi->masuk_lembur || !$absensi->pulang_lembur) return 0;

        $start = Carbon::createFromFormat('H:i:s', $absensi->masuk_lembur);
        $end = Carbon::createFromFormat('H:i:s', $absensi->pulang_lembur);
        return intdiv($start->diffInMinutes($end), 60); // hanya lembur
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
        if (!empty($absensi->$masuk) && !empty($absensi->$keluar)) {
            try {
                $start = Carbon::parse($absensi->$masuk);
                $end = Carbon::parse($absensi->$keluar);

                if ($start->lt($end)) {
                    $diff = $start->diffInMinutes($end);
                    $totalMinutes += $diff;
                }
            } catch (\Exception $e) {
                // Skip pasangan waktu ini jika tidak valid
                continue;
            }
        }
    }

    return intdiv($totalMinutes, 60);
}


    public function hitungJumlahHariPerTanggal($data_absensi_karyawan): array
    {
        $hasil = [];

        foreach ($data_absensi_karyawan as $absen) {
            $tanggal = Carbon::parse($absen->tanggal)->format('Y-m-d');
            $masuk = $absen->masuk_pagi ? Carbon::parse($absen->masuk_pagi) : null;
            $keluarSiang = $absen->keluar_siang;
            $masukSiang = $absen->masuk_siang;
            $pulang = $absen->pulang_kerja ? Carbon::parse($absen->pulang_kerja) : null;

            $jumlah = 0;

            if ($masuk && $masuk->format('H:i') <= '08:30') {
                if ($pulang && $pulang->format('H:i') >= '15:30') {
                    $jumlah = 1;
                } elseif (!$masukSiang && !$pulang && $keluarSiang) {
                    $jumlah = 0.5;
                }
            }

            $hasil[$tanggal] = $jumlah;
        }

        return $hasil;
    }






    public function hitungJumlahHariHarianLepas($data_absensi_karyawan): float
    {
        $jumlahHari = 0;

        foreach ($data_absensi_karyawan as $absen) {
            $masukPagi = $absen->masuk_pagi ? Carbon::parse($absen->masuk_pagi) : null;
            $keluarSiang = $absen->keluar_siang;
            $masukSiang = $absen->masuk_siang;
            $pulangKerja = $absen->pulang_kerja ? Carbon::parse($absen->pulang_kerja) : null;

            if ($masukPagi && $masukPagi->format('H:i') <= '08:30') {
                if ($pulangKerja) {
                    if ($pulangKerja->format('H:i') >= '17:00') {
                        $jumlahHari += 1;
                    } elseif ($pulangKerja->format('H:i') >= '15:00' && $pulangKerja->format('H:i') < '17:00') {
                        $jumlahHari += 1;
                        // Jam tidak masuk akan dihitung di fungsi terpisah
                    }
                } elseif ($keluarSiang && !$masukSiang && !$pulangKerja) {
                    $jumlahHari += 0.5;
                }
            }
        }

        return $jumlahHari;
    }




}