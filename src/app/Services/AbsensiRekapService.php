<?php

namespace App\Services;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AbsensiRekap;

class AbsensiRekapService
{
    public function rekapUntukUser(string $nama, string $start_date, string $end_date, bool $persist = false)
    {
        $absensis = Absensi::where('name', $nama)
            ->whereBetween('tanggal', [$start_date, $end_date])
            ->get()
            ->groupBy('tanggal');

        $absensis = Absensi::with('karyawan')
            ->where('name', $nama)
            ->whereBetween('tanggal', [$start_date, $end_date])
            ->get()
            ->groupBy('tanggal');
        
        $tanggalAkhirAbsensi = $absensis->keys()->sortDesc()->first() ?? $end_date;

        $liburResponse = Http::get('https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json');
        $libur = $liburResponse->successful() ? $liburResponse->json() : [];

        $rekap = [
            'sj' => 0,
            'sabtu' => 0,
            'minggu' => 0,
            'hari_besar' => 0,
            'tidak_masuk' => 0,
            'per_tanggal' => [],
            'per_user' => [],
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

            $hasData = false;
            if ($record) {
                $fields = [
                    $record->masuk_pagi,
                    $record->keluar_siang,
                    $record->masuk_siang,
                    $record->pulang_kerja,
                    $record->masuk_lembur,
                    $record->pulang_lembur
                ];
                foreach ($fields as $f) {
                    if (!empty($f)) {
                        $hasData = true;
                        break;
                    }
                }
            }

            $jumlahJam = 0;
            $kategori = null;
            $sisaJam   = 0;

            // ==== HITUNG SISA JAM BERDASARKAN KATEGORI ====
            if ($kategori === 'sj' && !$isLibur) {
                // (a) Telat
                if ($record && $record->masuk_pagi) {
                    $jamMasukStr = Carbon::parse($record->masuk_pagi)->format('H:i');

                    if ($jamMasukStr >= '12:00') {
                        // Datang setelah 12:00 → anggap pagi penuh hangus = 4 jam
                        $sisaJam += 4;
                    } elseif ($jamMasukStr > '08:15') {
                        // Datang antara 08:16–11:59 → kelipatan 30 menit dari 08:15
                        $menitTerlambat = Carbon::createFromTimeString('08:15')
                            ->diffInMinutes(Carbon::parse($record->masuk_pagi));
                        // 30 menit = 0.5 jam
                        $sisaJam += ($menitTerlambat <= 30) ? 0.5 : ceil($menitTerlambat / 30) * 0.5;
                    }
                }


                // (b) Harian lepas: pulang sebelum 17:00
                $isHarianLepas = strtolower($record->karyawan->status ?? '') === 'harian lepas';
                if ($isHarianLepas && $record && $record->pulang_kerja) {
                    $jamPulang = Carbon::parse($record->pulang_kerja)->format('H:i');
                    if     ($jamPulang >= '14:00' && $jamPulang < '15:00') $sisaJam += 3;
                    elseif ($jamPulang >= '15:00' && $jamPulang < '16:00') $sisaJam += 2;
                    elseif ($jamPulang >= '16:00' && $jamPulang < '17:00') $sisaJam += 1;
                }

                // (c) Non harian lepas: keluar siang tidak kembali
                if (
                    !$isHarianLepas &&
                    $record && $record->keluar_siang && !$record->masuk_siang
                ) {
                    $jamKeluarSiang = Carbon::parse($record->keluar_siang)->format('H:i');
                    if     ($jamKeluarSiang >= '09:00' && $jamKeluarSiang <= '09:59') $sisaJam += 6;
                    elseif ($jamKeluarSiang >= '10:00' && $jamKeluarSiang <= '10:59') $sisaJam += 5;
                    elseif ($jamKeluarSiang >= '11:00' && $jamKeluarSiang <= '11:59') $sisaJam += 4;
                }

            } elseif (in_array($kategori, ['sabtu','minggu'], true)) {
                // Sabtu/Minggu: kalau mau, tetap boleh telat (kalau aturannya begitu)
                if ($record && $record->masuk_pagi) {
                    $jamMasuk  = Carbon::parse($record->masuk_pagi);
                    $jamNormal = Carbon::createFromTimeString('08:15');
                    if ($jamMasuk->gt($jamNormal)) {
                        $menitTerlambat = $jamNormal->diffInMinutes($jamMasuk);
                        $sisaJam += ($menitTerlambat <= 30) ? 0.5 : ceil($menitTerlambat / 30);
                    }
                }

            } elseif ($kategori === 'hari_besar') {
                // Hari Besar (meskipun jatuh di weekday): TIDAK ADA sisa_jam sama sekali
                $sisaJam = 0;
            }

            // ==== END HITUNG SISA JAM ====

            // === OVERRIDE: HARIAN LEPAS 0.5 HARI → sisa_jam = 0 ===
            $isHarianLepas = strtolower($record->karyawan->status ?? '') === 'harian lepas';
            if ($isHarianLepas) {
                $masukPagi    = $record->masuk_pagi   ? Carbon::parse($record->masuk_pagi)   : null;
                $pulangKerja  = $record->pulang_kerja ? Carbon::parse($record->pulang_kerja) : null;
                $keluarSiang  = $record->keluar_siang ? Carbon::parse($record->keluar_siang) : null;
                $isHalfDayHL  = false;

                // Aturan 0.5 hari untuk harian lepas:
                // - Masuk tapi tidak ada pulang_kerja
                // - Masuk & pulang sebelum 15:00
                // - Keluar siang & tidak kembali & tidak ada pulang_kerja
                if ($masukPagi && !$pulangKerja) {
                    $isHalfDayHL = true;
                } elseif ($masukPagi && $pulangKerja) {
                    $jp = $pulangKerja->format('H:i');
                    if ($jp < '15:00') {
                        $isHalfDayHL = true;
                    }
                } elseif ($keluarSiang && !$record->masuk_siang && !$record->pulang_kerja) {
                    $isHalfDayHL = true;
                }

                if ($isHalfDayHL) {
                    $sisaJam = 0; // nolkan seluruh sisa_jam di hari itu
                }
            }

            if (
                (!$record || !$hasData)
                && !$isLibur && $dayName != 'Saturday' && $dayName != 'Sunday'
            ) {
                $rekap['per_tanggal'][$nama][$tanggalStr] = [
                    'sj' => '-',
                    'sabtu' => '-',
                    'minggu' => '-',
                    'hari_besar' => '-',
                    'tidak_masuk' => '8 jam',
                    'sisa_jam' => 0,
                ];
                $rekap['tidak_masuk'] += 8;
                $rekap['per_user'][$nama]['tidak_masuk'] = ($rekap['per_user'][$nama]['tidak_masuk'] ?? 0) + 8;
                continue;
            } elseif ($isLibur) {
                // Libur/holiday → jam 08–12 & 13–17 + tier lembur setelah 17:00
                $jumlahJam = $this->hitungJamKerjaLibur($record);
                $rekap['hari_besar'] += $jumlahJam;
                $kategori = 'hari_besar';
            } elseif ($dayName == 'Saturday') {
                // Sabtu → pakai aturan libur juga
                $jumlahJam = $this->hitungJamKerjaLibur($record);
                $rekap['sabtu'] += $jumlahJam;
                $kategori = 'sabtu';
            } elseif ($dayName == 'Sunday') {
                // Minggu → pakai aturan libur juga
                $jumlahJam = $this->hitungJamKerjaLibur($record);
                $rekap['minggu'] += $jumlahJam;
                $kategori = 'minggu';
            } else {
                $jumlahJam = $this->hitungJamLemburSaja($record);
                $rekap['sj'] += $jumlahJam;
                $kategori = 'sj';

                // $sisaJam = 0;

                if ($record && $record->masuk_pagi) {
                    $jamMasuk = Carbon::parse($record->masuk_pagi);
                    $jamNormal = Carbon::createFromTimeString('08:15');

                    if ($jamMasuk->gt($jamNormal)) {
                        $menitTerlambat = $jamNormal->diffInMinutes($jamMasuk);

                        if ($menitTerlambat <= 30) {
                            $sisaJam += 0.5;
                        } else {
                            $sisaJam += ceil($menitTerlambat / 30); // kelipatan 30 menit
                        }
                    }
                }

                $isHarianLepas = strtolower($record->karyawan->status ?? '') === 'harian lepas';

                if ($isHarianLepas && $record->pulang_kerja) {
                    $jamPulang = Carbon::parse($record->pulang_kerja)->format('H:i');

                    if ($jamPulang >= '14:00' && $jamPulang < '15:00') {
                        $sisaJam += 3;
                    } elseif ($jamPulang >= '15:00' && $jamPulang < '16:00') {
                        $sisaJam += 2;
                    } elseif ($jamPulang >= '16:00' && $jamPulang < '17:00') {
                        $sisaJam += 1;
                    }
                }

                if (
                    !$isHarianLepas &&
                    $record &&
                    $record->keluar_siang &&
                    !$record->masuk_siang
                ) {
                    $jamKeluarSiang = Carbon::parse($record->keluar_siang)->format('H:i');

                    if ($jamKeluarSiang >= '09:00' && $jamKeluarSiang <= '09:59') {
                        $sisaJam += 6;
                    } elseif ($jamKeluarSiang >= '10:00' && $jamKeluarSiang <= '10:59') {
                        $sisaJam += 5;
                    } elseif ($jamKeluarSiang >= '11:00' && $jamKeluarSiang <= '11:59') {
                        $sisaJam += 4;
                    }
                }
            }
            // // ==== SISA JAM: JALAN HANYA DI WEEKDAY & BUKAN LIBUR ====
            // if ($kategori === 'sj' && !$isLibur) {
            //     // --- TELAT (biarkan logika yang sudah ada) ---
            //     if ($record && $record->masuk_pagi) {
            //         $jamMasuk  = Carbon::parse($record->masuk_pagi);
            //         $jamNormal = Carbon::createFromTimeString('08:15');
            //         if ($jamMasuk->gt($jamNormal)) {
            //             $menitTerlambat = $jamNormal->diffInMinutes($jamMasuk);
            //             $sisaJam += ($menitTerlambat <= 30) ? 0.5 : ceil($menitTerlambat / 30);
            //         }
            //     }

            //     $isHarianLepas = strtolower($record->karyawan->status ?? '') === 'harian lepas';

            //     // === KELUAR SIANG & TIDAK KEMBALI (tanpa masuk_siang & tanpa pulang_kerja) ===
            //     $noReturn = ($record && $record->keluar_siang && !$record->masuk_siang && !$record->pulang_kerja);

            //     if ($noReturn) {
            //         if ($isHarianLepas) {
            //             // HL: hitung 0.5 hari di "jumlah hari", sisa_jam jangan dihitung.
            //             $sisaJam = 0;                 // <- override (hapus penalti telat kalau ada)
            //         } else {
            //             // Harian tetap: tepat 4 jam (jangan dobel)
            //             $sisaJam = 4;                 // <- set, BUKAN +=
            //         }
            //     } else {
            //         // === PULANG CEPAT (hanya jika BUKAN kasus noReturn) ===
            //         if ($record && $record->pulang_kerja) {
            //             $jp = Carbon::parse($record->pulang_kerja)->format('H:i');

            //             // Normal 8 jam (sisa 0)
            //             if     ($jp === '17:00') { /* no-op */ }
            //             elseif ($jp >= '17:16' && $jp <= '18:59') { /* no-op */ }

            //             // (opsional) kalau 16:50–17:15 mau dianggap 7 jam, aktifkan baris ini
            //             // elseif ($jp >= '16:50' && $jp <= '17:15') { $sisaJam = max($sisaJam, 1); }

            //             // Window lain
            //             elseif ($jp >= '15:50' && $jp <= '16:15') { $sisaJam = max($sisaJam, 1); } // 7 jam
            //             elseif ($jp >= '14:50' && $jp <= '15:14') { $sisaJam = max($sisaJam, 2); } // 6 jam
            //             elseif ($jp >= '13:50' && $jp <= '14:15') { $sisaJam = max($sisaJam, 3); } // 5 jam
            //             elseif ($jp >= '12:50' && $jp <= '13:15') { $sisaJam = max($sisaJam, 4); } // 4 jam (istirahat)
            //             elseif ($jp >= '11:50' && $jp <= '12:15') { $sisaJam = max($sisaJam, 4); } // 4 jam
            //             elseif ($jp >= '10:50' && $jp <= '11:15') { $sisaJam = max($sisaJam, 5); } // 3 jam
            //             elseif ($jp >= '09:50' && $jp <= '10:15') { $sisaJam = max($sisaJam, 6); } // 2 jam
            //             elseif ($jp >= '08:50' && $jp <= '09:15') { $sisaJam = max($sisaJam, 7); } // 1 jam
            //             elseif ($jp <  '08:50')                   { $sisaJam = max($sisaJam, 8); } // 0 jam
            //         }
            //     }

            //     // HL half-day override tambahan (kalau ada skenario lain yang bikin 0.5 hari)
            //     if ($isHarianLepas) {
            //         $masuk = $record->masuk_pagi ? Carbon::parse($record->masuk_pagi) : null;
            //         $pulang= $record->pulang_kerja ? Carbon::parse($record->pulang_kerja) : null;
            //         if (($masuk && !$pulang) || ($masuk && $pulang && $pulang->format('H:i') < '15:00')) {
            //             $sisaJam = 0;
            //         }
            //     }

            //     // batas aman
            //     $sisaJam = min($sisaJam, 8);
            // }
            // ==== END WEEKDAY ====


            $rekap['per_tanggal'][$nama][$tanggalStr] = [
                'sj' => $kategori === 'sj' ? $jumlahJam . ' jam' : '-',
                'sabtu' => $kategori === 'sabtu' ? $jumlahJam . ' jam' : '-',
                'minggu' => $kategori === 'minggu' ? $jumlahJam . ' jam' : '-',
                'hari_besar' => $kategori === 'hari_besar' ? $jumlahJam . ' jam' : '-',
                'tidak_masuk' => $kategori === 'tidak_masuk' ? 8 : '-', 
                'sisa_jam' => $sisaJam,
            ];
            $totalSisaJam = array_sum(array_map(function ($item) {
                return (float) ($item['sisa_jam'] ?? 0);
            }, $rekap['per_tanggal'][$nama] ?? []));

            $totalJumlahHari = array_sum(array_map(function ($item) {
                return (float) ($item['jumlah_hari'] ?? 0);
            }, $rekap['per_tanggal'][$nama] ?? []));

            $rekap['per_user'][$nama]['sisa_jam'] = round($totalSisaJam, 2);
            $rekap['per_user'][$nama]['jumlah_hari'] = round($totalJumlahHari, 2);

        }

            $absensisFlat = $absensis->flatten();
            $hasilHariPerTanggal = $this->hitungJumlahHariPerTanggal($absensisFlat);
            $jumlahHari = array_sum(array_column($hasilHariPerTanggal, 'jumlah_hari'));
            $totalHari = count($hasilHariPerTanggal);
            $jumlahHariTidakMasuk = $totalHari - $jumlahHari;

            $rekap['per_user'][$nama]['sj'] = $rekap['sj'] ?? 0;
            $rekap['per_user'][$nama]['sabtu'] = $rekap['sabtu'] ?? 0;
            $rekap['per_user'][$nama]['minggu'] = $rekap['minggu'] ?? 0;
            $rekap['per_user'][$nama]['hari_besar'] = $rekap['hari_besar'] ?? 0;
            $rekap['per_user'][$nama]['total_jam'] = $rekap['per_user'][$nama]['total_jam'] ?? 0;
            $rekap['per_user'][$nama]['jumlah_hari'] = $jumlahHari;
            $jumlahHariKerjaTanpaAbsensi = 0;
            foreach ($period as $date) {
                $tanggalStr = $date->format('Y-m-d');
                $dayName = $date->format('l');
                $isLibur = array_key_exists($tanggalStr, $libur);

                // Hari kerja (Senin-Jumat, bukan libur)
                if ($dayName != 'Saturday' && $dayName != 'Sunday' && !$isLibur) {
                    $record = $absensis->get($tanggalStr)?->first();
                    $hasData = $record && (
                        $record->masuk_pagi || $record->keluar_siang ||
                        $record->masuk_siang || $record->pulang_kerja ||
                        $record->masuk_lembur || $record->pulang_lembur
                    );
                    if (!$hasData) {
                        $jumlahHariKerjaTanpaAbsensi++;
                    }
                }
            }
            $rekap['per_user'][$nama]['tidak_masuk'] = $jumlahHariKerjaTanpaAbsensi * 8;
            $rekap['per_user'][$nama]['total_jam'] = max(0,
                ($rekap['per_user'][$nama]['sj'] ?? 0) +
                ($rekap['per_user'][$nama]['sabtu'] ?? 0) +
                ($rekap['per_user'][$nama]['minggu'] ?? 0) +
                ($rekap['per_user'][$nama]['hari_besar'] ?? 0) -
                ($rekap['per_user'][$nama]['tidak_masuk'] ?? 0) -
                ($rekap['per_user'][$nama]['sisa_jam'] ?? 0)
            );

            $rekap['display'] = [
                'sj' => $rekap['per_user'][$nama]['sj'] . ' jam',
                'sabtu' => $rekap['per_user'][$nama]['sabtu'] . ' jam',
                'minggu' => $rekap['per_user'][$nama]['minggu'] . ' jam',
                'hari_besar' => $rekap['per_user'][$nama]['hari_besar'] . ' jam',
                'tidak_masuk' => $rekap['per_user'][$nama]['tidak_masuk'] . ' jam',
                'sisa_jam' => $sisaJam . ' jam',
            ];

            $lastAbsensiDate = collect($absensis)->keys()->sort()->last() ?? $end_date;

            $split = app(\App\Services\SisaJamSplitService::class)
            ->splitFromPerTanggal($rekap['per_tanggal'][$nama] ?? []);

            // gabungkan ke per_user agar ikut tersimpan
            $rekap['per_user'][$nama] = array_merge($rekap['per_user'][$nama], $split);

            if ($persist) {
                $this->simpanRekapKeDatabase(
                    $nama,
                    $start_date,
                    $end_date,
                    $rekap['per_user'][$nama] ?? [],
                    $rekap['per_tanggal'][$nama] ?? []
                );
            }

            return $rekap;
    }

    public function rekapSemuaUser($start, $end, $nama_karyawan = null, $status_karyawan = null, $lokasi = null, $jenis_proyek = null, bool $persist = false)
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

        // Tanggal merah (hari besar)
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
                'sisa_jam' => 0,
                'jam' => 0
            ],
            'per_tanggal' => [],
        ];

        foreach ($data as $nama => $_) {
            $rekap['per_user'][$nama] = [
                'sj' => 0,
                'sabtu' => 0,
                'minggu' => 0,
                'hari_besar' => 0,
                'tidak_masuk' => 0,
                'sisa_jam' => 0,
            ];
        }

        $period = new \DatePeriod(
            new \DateTime($start),
            new \DateInterval('P1D'),
            (new \DateTime($end))->modify('+1 day')
        );

        foreach ($period as $date) {
            $tanggalStr = $date->format('Y-m-d');
            $dayName = $date->format('l');
            $isLibur = array_key_exists($tanggalStr, $libur);

            foreach ($data as $nama => $absensiHarian) {
                $record = $absensiHarian->get($tanggalStr)?->first();

                // cek ada data jam?
                $hasData = false;
                if ($record) {
                    foreach ([
                        $record->masuk_pagi,
                        $record->keluar_siang,
                        $record->masuk_siang,
                        $record->pulang_kerja,
                        $record->masuk_lembur,
                        $record->pulang_lembur
                    ] as $f) {
                        if (!empty($f)) { $hasData = true; break; }
                    }
                }

                // reset sisaJam di awal iterasi tanggal
                $sisaJam = 0;

                // tentukan kategori + jumlahJam
                if (
                    (!$record || !$hasData)
                    && !$isLibur && $dayName != 'Saturday' && $dayName != 'Sunday'
                ) {
                    $rekap['per_tanggal'][$nama][$tanggalStr] = [
                        'sj' => '-',
                        'sabtu' => '-',
                        'minggu' => '-',
                        'hari_besar' => '-',
                        'tidak_masuk' => '8 jam',
                        'sisa_jam' => 0,
                    ];
                    $rekap['grand_total']['tidak_masuk'] += 8;
                    $rekap['per_user'][$nama]['tidak_masuk'] = ($rekap['per_user'][$nama]['tidak_masuk'] ?? 0) + 8;
                    continue;
                } elseif ((!$record || !$hasData) && ($isLibur || $dayName == 'Saturday' || $dayName == 'Sunday')) {
                    // libur/akhir pekan tanpa data: tidak dihitung tidak_masuk
                    $rekap['per_tanggal'][$nama][$tanggalStr] = [
                        'sj' => '-',
                        'sabtu' => '-',
                        'minggu' => '-',
                        'hari_besar' => '-',
                        'tidak_masuk' => '-',
                        'sisa_jam' => 0,
                    ];
                    continue;
                }

                // ada data
            if ($isLibur) {
                // Libur/holiday → 08–12 + 13–17 (dibulatkan) + tier lembur setelah 17:00
                $jumlahJam = $this->hitungJamKerjaLibur($record);   // ← pakai fungsi LIBUR
                $rekap['grand_total']['hari_besar'] += $jumlahJam;
                $rekap['per_user'][$nama]['hari_besar'] = ($rekap['per_user'][$nama]['hari_besar'] ?? 0) + $jumlahJam;
                $kategori = 'hari_besar';

            } elseif ($dayName == 'Saturday') {
                $jumlahJam = $this->hitungJamKerjaLibur($record);   // ← pakai fungsi LIBUR
                $rekap['grand_total']['sabtu'] += $jumlahJam;
                $rekap['per_user'][$nama]['sabtu'] = ($rekap['per_user'][$nama]['sabtu'] ?? 0) + $jumlahJam;
                $kategori = 'sabtu';

            } elseif ($dayName == 'Sunday') {
                $jumlahJam = $this->hitungJamKerjaLibur($record);   // ← pakai fungsi LIBUR
                $rekap['grand_total']['minggu'] += $jumlahJam;
                $rekap['per_user'][$nama]['minggu'] = ($rekap['per_user'][$nama]['minggu'] ?? 0) + $jumlahJam;
                $kategori = 'minggu';

            } else {
                // Weekday → lembur malam via tier saja
                $jumlahJam = $this->hitungJamLemburSaja($record);
                $rekap['grand_total']['sj'] += $jumlahJam;
                $rekap['per_user'][$nama]['sj'] = ($rekap['per_user'][$nama]['sj'] ?? 0) + $jumlahJam;
                $kategori = 'sj';
            }
                
                // ==== SISA JAM: JALAN HANYA DI WEEKDAY & BUKAN LIBUR ====
                if ($kategori === 'sj' && !$isLibur) {
                    // --- TELAT (biarkan logika yang sudah ada) ---
                    if ($record && $record->masuk_pagi) {
                        $jamMasuk  = Carbon::parse($record->masuk_pagi);
                        $jamNormal = Carbon::createFromTimeString('08:15');
                        if ($jamMasuk->gt($jamNormal)) {
                            $menitTerlambat = $jamNormal->diffInMinutes($jamMasuk);
                            $sisaJam += ($menitTerlambat <= 30) ? 0.5 : ceil($menitTerlambat / 30);
                        }
                    }

                    $isHarianLepas = strtolower($record->karyawan->status ?? '') === 'harian lepas';

                    // === KELUAR SIANG & TIDAK KEMBALI (tanpa masuk_siang & tanpa pulang_kerja) ===
                    $noReturn = ($record && $record->keluar_siang && !$record->masuk_siang && !$record->pulang_kerja);

                    if ($noReturn) {
                        if ($isHarianLepas) {
                            // HL: hitung 0.5 hari di "jumlah hari", sisa_jam jangan dihitung.
                            $sisaJam = 0;                 // <- override (hapus penalti telat kalau ada)
                        } else {
                            // Harian tetap: tepat 4 jam (jangan dobel)
                            $sisaJam = 4;                 // <- set, BUKAN +=
                        }
                    } else {
                        // === PULANG CEPAT (hanya jika BUKAN kasus noReturn) ===
                        if ($record && $record->pulang_kerja) {
                            $jp = Carbon::parse($record->pulang_kerja)->format('H:i');

                            // Normal 8 jam (sisa 0)
                            if     ($jp === '17:00') { /* no-op */ }
                            elseif ($jp >= '17:16' && $jp <= '18:59') { /* no-op */ }

                            // (opsional) kalau 16:50–17:15 mau dianggap 7 jam, aktifkan baris ini
                            // elseif ($jp >= '16:50' && $jp <= '17:15') { $sisaJam = max($sisaJam, 1); }

                            // Window lain
                            elseif ($jp >= '15:50' && $jp <= '16:15') { $sisaJam = max($sisaJam, 1); } // 7 jam
                            elseif ($jp >= '14:50' && $jp <= '15:14') { $sisaJam = max($sisaJam, 2); } // 6 jam
                            elseif ($jp >= '13:50' && $jp <= '14:15') { $sisaJam = max($sisaJam, 3); } // 5 jam
                            elseif ($jp >= '12:50' && $jp <= '13:15') { $sisaJam = max($sisaJam, 4); } // 4 jam (istirahat)
                            elseif ($jp >= '11:50' && $jp <= '12:15') { $sisaJam = max($sisaJam, 4); } // 4 jam
                            elseif ($jp >= '10:50' && $jp <= '11:15') { $sisaJam = max($sisaJam, 5); } // 3 jam
                            elseif ($jp >= '09:50' && $jp <= '10:15') { $sisaJam = max($sisaJam, 6); } // 2 jam
                            elseif ($jp >= '08:50' && $jp <= '09:15') { $sisaJam = max($sisaJam, 7); } // 1 jam
                            elseif ($jp <  '08:50')                   { $sisaJam = max($sisaJam, 8); } // 0 jam
                        }
                    }

                    // HL half-day override tambahan (kalau ada skenario lain yang bikin 0.5 hari)
                    if ($isHarianLepas) {
                        $masuk = $record->masuk_pagi ? Carbon::parse($record->masuk_pagi) : null;
                        $pulang= $record->pulang_kerja ? Carbon::parse($record->pulang_kerja) : null;
                        if (($masuk && !$pulang) || ($masuk && $pulang && $pulang->format('H:i') < '15:00')) {
                            $sisaJam = 0;
                        }
                    }

                    // batas aman
                    $sisaJam = min($sisaJam, 8);
                }
                // ==== END WEEKDAY ====


                // === OVERRIDE: HARIAN LEPAS 0.5 HARI → sisa_jam = 0 ===
                $isHarianLepas = strtolower($record->karyawan->status ?? '') === 'harian lepas';
                if ($isHarianLepas) {
                    $masukPagi    = $record->masuk_pagi   ? Carbon::parse($record->masuk_pagi)   : null;
                    $pulangKerja  = $record->pulang_kerja ? Carbon::parse($record->pulang_kerja) : null;
                    $keluarSiang  = $record->keluar_siang ? Carbon::parse($record->keluar_siang) : null;
                    $isHalfDayHL  = false;

                    // Aturan 0.5 hari untuk harian lepas:
                    // - Masuk tapi tidak ada pulang_kerja
                    // - Masuk & pulang sebelum 15:00
                    // - Keluar siang & tidak kembali & tidak ada pulang_kerja
                    if ($masukPagi && !$pulangKerja) {
                        $isHalfDayHL = true;
                    } elseif ($masukPagi && $pulangKerja) {
                        $jp = $pulangKerja->format('H:i');
                        if ($jp < '15:00') {
                            $isHalfDayHL = true;
                        }
                    } elseif ($keluarSiang && !$record->masuk_siang && !$record->pulang_kerja) {
                        $isHalfDayHL = true;
                    }

                    if ($isHalfDayHL) {
                        $sisaJam = 0; // nolkan seluruh sisa_jam di hari itu
                    }
                }


                // Simpan ke array rekap per tanggal
                $rekap['per_tanggal'][$nama][$tanggalStr] = [
                    'sj' => $kategori === 'sj' ? $jumlahJam . ' jam' : '-',
                    'sabtu' => $kategori === 'sabtu' ? $jumlahJam . ' jam' : '-',
                    'minggu' => $kategori === 'minggu' ? $jumlahJam . ' jam' : '-',
                    'hari_besar' => $kategori === 'hari_besar' ? $jumlahJam . ' jam' : '-',
                    'tidak_masuk' => $kategori === 'tidak_masuk' ? '8 jam' : '-',
                    'sisa_jam' => $sisaJam,
                ];

                // Akumulasi ke user dan grand total
                $rekap['per_user'][$nama]['sisa_jam'] = ($rekap['per_user'][$nama]['sisa_jam'] ?? 0) + $sisaJam;
                $rekap['grand_total']['sisa_jam'] = ($rekap['grand_total']['sisa_jam'] ?? 0) + $sisaJam;
            }
        }

        // Grand total jam (net)
        $rekap['grand_total']['jam'] = max(0,
            ($rekap['grand_total']['sj'] +
            $rekap['grand_total']['sabtu'] +
            $rekap['grand_total']['minggu'] +
            $rekap['grand_total']['hari_besar'])
            - $rekap['grand_total']['tidak_masuk']
            - $rekap['grand_total']['sisa_jam']
        );

        // Per user: hitung total_jam, jumlah_hari, split sisa jam, dan persist
        foreach ($rekap['per_user'] as $nama => $rekapUser) {
            $absensiTanggal = $data->get($nama);
            $absensisFlat = $absensiTanggal ? $absensiTanggal->flatten() : collect();

            $hasilHariPerTanggal = $this->hitungJumlahHariPerTanggal($absensisFlat);
            $jumlahHari = array_sum(array_column($hasilHariPerTanggal, 'jumlah_hari'));

            $totalJam = max(0,
                ($rekapUser['sj'] ?? 0) +
                ($rekapUser['sabtu'] ?? 0) +
                ($rekapUser['minggu'] ?? 0) +
                ($rekapUser['hari_besar'] ?? 0) -
                ($rekapUser['tidak_masuk'] ?? 0) -
                ($rekapUser['sisa_jam'] ?? 0)
            );

            $rekap['per_user'][$nama]['total_jam'] = $totalJam;
            $rekap['per_user'][$nama]['jumlah_hari'] = $jumlahHari;

            $split = app(\App\Services\SisaJamSplitService::class)
                ->splitFromPerTanggal($rekap['per_tanggal'][$nama] ?? []);
            $rekap['per_user'][$nama] = array_merge($rekap['per_user'][$nama], $split);

            if ($persist) {
                $this->simpanRekapKeDatabase(
                    $nama,
                    $start,
                    $end,
                    $rekap['per_user'][$nama],
                    $rekap['per_tanggal'][$nama] ?? []
                );
            }
        }

        return $rekap;
    }

    private function hitungJamLemburSaja(?Absensi $absensi): int
    {
        return $this->hitungLemburSetelahJam17($absensi);
    }

    private function hitungJamKerja(?Absensi $absensi): int
    {
        if (!$absensi) return 0;

        $tanggal   = $absensi->tanggal;
        $hari      = Carbon::parse($tanggal)->format('l');
        $isWeekend = in_array($hari, ['Saturday', 'Sunday']);
        $isLibur   = $this->isHariBesar($tanggal);

        // helper overlap menit antar dua interval [startA,endA] ∩ [startB,endB]
        $overlapMinutes = function (string $startA, string $endA, string $startB, string $endB): int {
            $a1 = Carbon::createFromTimeString($startA);
            $a2 = Carbon::createFromTimeString($endA);
            $b1 = Carbon::createFromTimeString($startB);
            $b2 = Carbon::createFromTimeString($endB);
            $s  = $a1->max($b1);
            $e  = $a2->min($b2);
            return $s->lt($e) ? $s->diffInMinutes($e) : 0;
        };

        // pasangan non-lembur (pagi & siang)
        $pairs = [
            ['masuk_pagi',  'keluar_siang'],
            ['masuk_siang', 'pulang_kerja'],
        ];
        if ($isWeekend || $isLibur) {
            $morningMin   = 0;
            $afternoonMin = 0;

            foreach ($pairs as [$in, $out]) {
                if (!empty($absensi->$in) && !empty($absensi->$out)) {
                    $start = Carbon::parse($absensi->$in)->format('H:i');
                    $end   = Carbon::parse($absensi->$out)->format('H:i');
                    $morningMin   += $overlapMinutes($start, $end, '08:00', '12:00');
                    $afternoonMin += $overlapMinutes($start, $end, '13:00', '17:00');
                }
            }

            // pembulatan ke jam terdekat dengan ambang :30 (cap 4 jam per blok)
            $morningHours   = min(4, intdiv($morningMin   + 30, 60));
            $afternoonHours = min(4, intdiv($afternoonMin + 30, 60));

            $lembur = $this->hitungLemburSetelahJam17($absensi);

            return $morningHours + $afternoonHours + $lembur;
        }
        $totalMinutes = 0;
        foreach ([['masuk_pagi','keluar_siang'], ['masuk_siang','pulang_kerja'], ['masuk_lembur','pulang_lembur']] as [$in,$out]) {
            if (!empty($absensi->$in) && !empty($absensi->$out)) {
                try {
                    $s = Carbon::parse($absensi->$in);
                    $e = Carbon::parse($absensi->$out);
                    if ($s->lt($e)) $totalMinutes += $s->diffInMinutes($e);
                } catch (\Throwable $e) {
                    // skip pasangan invalid
                }
            }
        }

        // Jika ada jeda siang (keluar/masuk), kurangi 60 menit istirahat
        if (!empty($absensi->keluar_siang) || !empty($absensi->masuk_siang)) {
            $totalMinutes = max(0, $totalMinutes - 60);
        }

        return intdiv($totalMinutes, 60);
    }


    private function hitungLemburSetelahJam17(?Absensi $absensi): int
    {
        if (!$absensi || !$absensi->pulang_lembur) return 0;

        // Tanggal absensi sebagai basis
        $baseDate = Carbon::parse($absensi->tanggal)->startOfDay();

        // Ambil HH:MM dari pulang_lembur (apapun format sumbernya)
        $endHHmm  = Carbon::parse($absensi->pulang_lembur)->format('H:i');

        // Bangun datetime pulang lembur pada tanggal absensi
        $end = $baseDate->copy()->setTimeFromTimeString($endHHmm);

        // Jika jam pulang < 12:00, anggap itu lewat tengah malam → H+1
        if ($end->lt($baseDate->copy()->setTime(12, 0))) {
            $end->addDay();
        }

        // Pivot 19:00 di H (tanggal absensi)
        $pivot = $baseDate->copy()->setTime(19, 0);

        // Selisih menit sejak 19:00
        $delta = $pivot->diffInMinutes($end, false);
        if ($delta < 0) return 0; // pulang sebelum 19:00 → bukan lembur malam

        // Window 25 menit tiap 60 menit:
        // 19:50–20:15 = 1, 20:50–21:15 = 2, ... , 05:50–06:15 = 11
        for ($k = 1; $k <= 11; $k++) {
            $startMin = 50 + 60 * ($k - 1);
            $endMin   = 75 + 60 * ($k - 1);
            if ($delta >= $startMin && $delta <= $endMin) {
                return $k;
            }
        }

        // > 06:15 H+1 tetap dibatasi 11
        return 11;
    }



    private function hitungJamKerjaLibur(?Absensi $absensi): int
    {
        if (!$absensi) return 0;

        $overlapMinutes = function (string $startA, string $endA, string $startB, string $endB): int {
            $a1 = Carbon::createFromTimeString($startA);
            $a2 = Carbon::createFromTimeString($endA);
            $b1 = Carbon::createFromTimeString($startB);
            $b2 = Carbon::createFromTimeString($endB);
            $s  = $a1->max($b1);
            $e  = $a2->min($b2);
            return $s->lt($e) ? $s->diffInMinutes($e) : 0;
        };

        // hanya jam kerja 08–12 dan 13–17
        $morning = $afternoon = 0;
        foreach ([['masuk_pagi','keluar_siang'], ['masuk_siang','pulang_kerja']] as [$in,$out]) {
            if (!empty($absensi->$in) && !empty($absensi->$out)) {
                $start = Carbon::parse($absensi->$in)->format('H:i');
                $end   = Carbon::parse($absensi->$out)->format('H:i');
                $morning   += $overlapMinutes($start, $end, '08:00', '12:00');
                $afternoon += $overlapMinutes($start, $end, '13:00', '17:00');
            }
        }

        // bulatkan dengan ambang :30 (08:00–11:59 → 4 jam)
        $morningH   = min(4, intdiv($morning   + 30, 60));
        $afternoonH = min(4, intdiv($afternoon + 30, 60));

        // lembur sesudah 17:00 hanya via TIER (tidak dijumlah menit lagi)
        $lemburH = $this->hitungLemburSetelahJam17($absensi);

        return $morningH + $afternoonH + $lemburH;
    }

    private function isHariBesar(string $tanggal): bool
    {
        static $libur = null;
        if ($libur === null) {
            try {
                $resp = \Illuminate\Support\Facades\Http::get(
                    'https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json'
                );
                $libur = $resp->successful() ? $resp->json() : [];
            } catch (\Throwable $e) { $libur = []; }
        }
        return array_key_exists(Carbon::parse($tanggal)->format('Y-m-d'), $libur);
    }

    public function hitungJumlahHariPerTanggal($data_absensi_karyawan): array
    {
        $hasil = [];

        foreach ($data_absensi_karyawan as $absen) {
            $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
            $jumlahHari = 0;
            $sisaJam = 8;

            $masuk = $absen->masuk_pagi ? \Carbon\Carbon::parse($absen->masuk_pagi) : null;
            $pulang = $absen->pulang_kerja ? \Carbon\Carbon::parse($absen->pulang_kerja) : null;

            if ($masuk && $masuk->format('H:i') <= '08:30') {
                if ($pulang) {
                    $jamPulang = $pulang->format('H:i');

                    if ($jamPulang >= '17:00') {
                        $jumlahHari = 1;
                        $sisaJam = 0;
                    } elseif ($jamPulang >= '16:00') {
                        $jumlahHari = 1;
                        $sisaJam = 1;
                    } elseif ($jamPulang >= '15:00') {
                        $jumlahHari = 1;
                        $sisaJam = 2;
                    } elseif ($jamPulang >= '14:00') {
                        $jumlahHari = 1;
                        $sisaJam = 3;
                    } elseif ($jamPulang <= '13:00') {
                        $jumlahHari = 0.5;
                        $sisaJam = 0;
                    } else {
                        $jumlahHari = 0.5;
                        $sisaJam = 0;
                    }
                } else {
                    $jumlahHari = 0.5;
                    $sisaJam = 0;
            }
        } else {
            $jumlahHari = 0;
            $sisaJam = 8;
        }

        $hasil[$tanggal] = [
            'jumlah_hari' => $jumlahHari,
            'sisa_jam' => $sisaJam,
        ];

        if ($masuk && $jumlahHari > 0) {
            $jamMasuk = $masuk->format('H:i');

            if ($jamMasuk > '08:15' && $jamMasuk < '12:00') {
                $menitTerlambat = Carbon::createFromTimeString('08:15')->diffInMinutes($masuk);
                $jamTerlambat = ceil($menitTerlambat / 60); // dibulatkan ke atas per jam
                $sisaJam += $jamTerlambat;
            }
        }
    }
        return $hasil;
    }

    public function hitungJumlahHariHarianLepas($data_absensi_karyawan): float
    {
        $jumlahHari = 0;

        foreach ($data_absensi_karyawan as $absen) {
            $masukPagi = $absen->masuk_pagi ? Carbon::parse($absen->masuk_pagi) : null;
            $pulangKerja = $absen->pulang_kerja ? Carbon::parse($absen->pulang_kerja) : null;

            if ($masukPagi && $pulangKerja) {
                $jamPulang = $pulangKerja->format('H:i');

                if ($jamPulang >= '17:00') {
                    $jumlahHari += 1;
                } elseif ($jamPulang >= '15:00') {
                    $jumlahHari += 1;
                } elseif ($jamPulang <= '13:00') {
                    $jumlahHari += 0.5;
                } else {
                    $jumlahHari += 0.5;
                }
            } elseif ($masukPagi && !$pulangKerja) {
                $jumlahHari += 0.5;
            } elseif ($absen->keluar_siang && !$absen->masuk_siang && !$absen->pulang_kerja) {
                $jumlahHari += 0.5;
            }
        }
        return $jumlahHari;
    }
    

    public function simpanRekapKeDatabase(string $nama, string $start_date, string $end_date, array $rekapUser, array $rekapTanggal)
    {
        $karyawan = \App\Models\Karyawan::where('nama', $nama)->firstOrFail();
        AbsensiRekap::updateOrCreate(
            [
                'karyawan_id'   => $karyawan->id, // sesuaikan dengan PK sebenarnya
                'periode_awal'  => $start_date,
                'periode_akhir' => $end_date,
            ],
            [
                'nama'         => $nama,
                'sj'           => round($rekapUser['sj'] ?? 0, 2),
                'sabtu'        => round($rekapUser['sabtu'] ?? 0, 2),
                'minggu'       => round($rekapUser['minggu'] ?? 0, 2),
                'hari_besar'   => round($rekapUser['hari_besar'] ?? 0, 2),
                'tidak_masuk'  => round($rekapUser['tidak_masuk'] ?? 0, 2),

                // ← tambahan 4 kolom ini
                'sisa_sj'         => round($rekapUser['sisa_sj'] ?? 0, 2),
                'sisa_sabtu'      => round($rekapUser['sisa_sabtu'] ?? 0, 2),
                'sisa_minggu'     => round($rekapUser['sisa_minggu'] ?? 0, 2),
                'sisa_hari_besar' => round($rekapUser['sisa_hari_besar'] ?? 0, 2),

                // tetap isi sisa_jam dari hasil service baru
                'sisa_jam'     => round($rekapUser['sisa_jam'] ?? 0, 2),

                'total_jam'    => round($rekapUser['total_jam'] ?? 0, 2),
                'jumlah_hari'  => round($rekapUser['jumlah_hari'] ?? 0, 2),
            ]
        );
    }

}