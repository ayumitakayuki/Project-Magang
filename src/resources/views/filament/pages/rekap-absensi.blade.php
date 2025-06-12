<x-filament::page>
    <x-filament::card class="bg-blue-100 rounded-xl p-6">
        {{-- FORM FILTER TERPADU --}}
        <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">

            {{-- Hidden input untuk show_all --}}
            <input type="hidden" name="show_all" value="1">

            {{-- Search ID/Name --}}
            <input
                type="text"
                name="karyawan_keyword"
                value="{{ request('karyawan_keyword') }}"
                placeholder="🔍 Search ID/Name"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm w-64"
            />

            {{-- Status Karyawan --}}
            <select name="status_karyawan"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm border border-blue-500">
                <option value="all" {{ request('status_karyawan') == 'all' ? 'selected' : '' }}>
                    Show All
                </option>
                <option value="staff" {{ request('status_karyawan') == 'staff' ? 'selected' : '' }}>
                    Staff
                </option>
                <option value="harian tetap" {{ request('status_karyawan') == 'harian tetap' ? 'selected' : '' }}>
                    Harian Tetap
                </option>
                <option value="harian lepas" {{ request('status_karyawan') == 'harian lepas' ? 'selected' : '' }}>
                    Harian Lepas
                </option>
            </select>

            {{-- Lokasi --}}
            <select name="lokasi"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm">
                <option value="">Lokasi</option>
                @foreach ($lokasi_options as $lokasi)
                    <option value="{{ $lokasi }}" {{ request('lokasi') == $lokasi ? 'selected' : '' }}>
                        {{ ucfirst($lokasi) }}
                    </option>
                @endforeach
            </select>

            {{-- Proyek --}}
            <select name="proyek"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm">
                <option value="">Proyek</option>
                @foreach ($proyek_options as $proyek)
                    <option value="{{ $proyek }}" {{ request('proyek') == $proyek ? 'selected' : '' }}>
                        {{ $proyek }}
                    </option>
                @endforeach
            </select>

            {{-- Tanggal --}}
            <input
                type="text"
                id="start_date"
                name="start_date"
                value="{{ request('start_date') ?? now()->subMonth()->toDateString() }}"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm"
                placeholder="Start Date"
            />
            <span>-</span>
            <input
                type="text"
                id="end_date"
                name="end_date"
                value="{{ request('end_date') ?? now()->toDateString() }}"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm"
                placeholder="End Date"
            />

            {{-- Tombol Filter --}}
            <button type="submit"
                class="px-4 py-1 bg-green-300 hover:bg-green-400 text-sm rounded-lg transition">
                Filter
            </button>

            {{-- Tombol Reset Filter --}}
            <a href="{{ route('filament.admin.pages.rekap-absensi') }}"
                class="px-4 py-1 bg-gray-300 hover:bg-gray-400 text-sm rounded-lg transition">
                Reset
            </a>

        </form>
    </form>

        @if (!empty($data_harian))
        @php
            $nama_karyawan_unik = $data_harian->pluck('name')->unique();
        @endphp

        <div>
            @foreach ($nama_karyawan_unik as $nama_karyawan)
                @php
                    $data_karyawan = \App\Models\Karyawan::where('nama', $nama_karyawan)->first();
                    $data_absensi_karyawan = $data_harian->where('name', $nama_karyawan);
                @endphp

                <div class="flex flex-col lg:flex-row gap-4 items-start mb-6">
                {{-- BAGIAN KIRI: IDENTITAS --}}
                <div class="bg-white border border-gray-300 rounded-lg px-2 py-1 shadow-sm text-sm w-48 leading-tight">
                    <div class="space-y-1">
                        <div>
                            <span class="text-gray-500">ID Karyawan</span><br>
                            <span class="text-gray-800">{{ $data_karyawan->id_karyawan ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Nama Karyawan</span><br>
                            <span class="text-gray-800">{{ $nama_karyawan }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Periode</span><br>
                            <span class="text-gray-900 font-semibold">
                                {{ \Carbon\Carbon::parse($start_date)->format('d-m-Y') }}
                                s/d
                                {{ \Carbon\Carbon::parse($end_date)->format('d-m-Y') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Status</span><br>
                            <span class="text-gray-800">{{ $data_karyawan->status ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Lokasi</span><br>
                            <span class="text-gray-800">{{ $data_karyawan->lokasi ?? '-' }}</span>
                        </div>

                        @if ($data_karyawan?->lokasi === 'proyek')
                            <div>
                                <span class="text-gray-500">Jenis Proyek</span><br>
                                <span class="text-gray-800">{{ $data_karyawan->jenis_proyek ?? '-' }}</span>
                            </div>
                        @endif
                    </div>
                    {{-- TOMBOL EXPORT EXCEL --}}
                    <div class="mt-3">
                        <a href="{{ url('/export-absensi?start_date=' . request('start_date') . '&end_date=' . request('end_date') . '&id_karyawan=' . $data_karyawan->id_karyawan) }}" 
                        target="_blank"
                        class="text-gray-500">
                            Download Excel
                            </a>
                        </div>
                    </div>
        {{-- BAGIAN KANAN: 2 TABEL (HORIZONTAL) --}}
        <div class="w-full lg:w-2/3 flex flex-row gap-2">
            {{-- TABEL DETAIL ABSENSI --}}
            <div class="flex-1 overflow-x-auto">
                    <table class="custom-table">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="tanggal">Tanggal</th>
                        <th class="border border-black px-2 py-1">Masuk Pagi</th>
                        <th class="border border-black px-2 py-1">Keluar Siang</th>
                        <th class="border border-black px-2 py-1">Masuk Siang</th>
                        <th class="border border-black px-2 py-1">Pulang Kerja</th>
                        <th class="border border-black px-2 py-1">Masuk Lembur</th>
                        <th class="border border-black px-2 py-1">Pulang Lembur</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalTidakMasuk = 0;
                        foreach ($data_absensi_karyawan as $absen) {
                            $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
                            $rekap_tanggal = $rekap['per_tanggal'][$nama_karyawan][$tanggal] ?? [];

                            if (($rekap_tanggal['tidak_masuk'] ?? '-') !== '-') {
                                // Ambil angka dari string '8 jam'
                                $jam = intval($rekap_tanggal['tidak_masuk']);
                                $totalTidakMasuk += $jam;
                            }
                        }
                    @endphp

                    @foreach ($data_absensi_karyawan as $absen)
                        <tr>
                            <td class="tanggal">{{ \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y') }}</td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->masuk_pagi ? \Carbon\Carbon::parse($absen->masuk_pagi)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->keluar_siang ? \Carbon\Carbon::parse($absen->keluar_siang)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->masuk_siang ? \Carbon\Carbon::parse($absen->masuk_siang)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->pulang_kerja ? \Carbon\Carbon::parse($absen->pulang_kerja)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->masuk_lembur ? \Carbon\Carbon::parse($absen->masuk_lembur)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->pulang_lembur ? \Carbon\Carbon::parse($absen->pulang_lembur)->format('H:i') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TABEL PERHITUNGAN JAM --}}
        <div class="overflow-x-auto">
            <table class="custom-table">
                <thead>
                    @php
                        $isHarianLepas = strtolower($data_karyawan->status ?? '') === 'harian lepas';
                        $jumlahHari = 0;

                        if ($isHarianLepas) {
                            $jumlahHari = app(\App\Services\AbsensiRekapService::class)
                                ->hitungJumlahHariHarianLepas($data_absensi_karyawan);
                            $jumlahHariPerTanggal = app(\App\Services\AbsensiRekapService::class)
                                ->hitungJumlahHariPerTanggal($data_absensi_karyawan);
                        }
                    @endphp
            
                    @if ($isHarianLepas)
                        {{-- HEADER 2 BARIS: Khusus Harian Lepas --}}
                        <tr class="bg-gray-100 text-xs text-center">
                            <th rowspan="2" class="border border-black px-2 py-1">Tanggal</th>
                            <th rowspan="2" class="border border-black px-2 py-1">SJ</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Sabtu</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Minggu</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Hari<br>Besar</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Tidak<br>Masuk</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Sisa<br>Jam</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Jumlah<br>Hari</th>
                        </tr>
                        <tr class="bg-gray-100 text-xs text-center">
                            {{-- Kosong karena semua kolom pakai rowspan --}}
                        </tr>
                    @else
                        {{-- HEADER 1 BARIS: Untuk Staff dan Harian Tetap --}}
                        <tr class="bg-gray-100 text-xs text-center">
                            <th class="border border-black px-2 py-1">Tanggal</th>
                            <th class="border border-black px-2 py-1">SJ</th>
                            <th class="border border-black px-2 py-1">Sabtu</th>
                            <th class="border border-black px-2 py-1">Minggu</th>
                            <th class="border border-black px-2 py-1">Hari Besar</th>
                            <th class="border border-black px-2 py-1">Tidak Masuk</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @foreach ($data_absensi_karyawan as $absen)
                        @php
                            $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
                            $rekap_tanggal = $rekap['per_tanggal'][$nama_karyawan][$tanggal] ?? [
                                'sj' => '-',
                                'sabtu' => '-',
                                'minggu' => '-',
                                'hari_besar' => '-',
                                'tidak_masuk' => '-',
                            ];
                        @endphp
                        <tr>
                            <td class="tanggal">{{ \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y') }}</td>
                            <td class="border border-black px-2 py-1">
                                @if ($isHarianLepas)
                                    -
                                @else
                                    {{ ($rekap_tanggal['sj'] ?? '-') }}
                                @endif
                            </td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['sabtu'] }}</td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['minggu'] }}</td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['hari_besar'] }}</td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['tidak_masuk'] }}</td>
                            @if ($isHarianLepas)
                                @php
                                    $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
                                    $rekapHari = $jumlahHariPerTanggal[$tanggal] ?? ['jumlah_hari' => null, 'sisa_jam' => null];
                                @endphp
                                <td class="border border-black px-2 py-1">
                                    {{ ($rekapHari['sisa_jam'] ?? 0) > 0 && ($rekapHari['jumlah_hari'] ?? 0) > 0
                                        ? $rekapHari['sisa_jam'] . ' jam'
                                        : '-' }}
                                </td>
                                <td class="border border-black px-2 py-1">
                                    {{ ($rekapHari['jumlah_hari'] ?? 0) > 0
                                        ? $rekapHari['jumlah_hari'] . ' hari'
                                        : '-' }}
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    {{-- TOTAL per kolom --}}
                    <tr class="bg-gray-200 font-semibold">
                        <td class="border border-black px-2 py-1 text-right">Total</td>
                        <td class="border border-black px-2 py-1">
                            @if ($isHarianLepas)
                                -
                            @else
                                {{ ($rekap['per_user'][$nama_karyawan]['sj'] ?? 0) . ' jam' }}
                            @endif
                        </td>
                        <td class="border border-black px-2 py-1">{{ ($rekap['per_user'][$nama_karyawan]['sabtu'] ?? 0) . ' jam' }}</td>
                        <td class="border border-black px-2 py-1">{{ ($rekap['per_user'][$nama_karyawan]['minggu'] ?? 0) . ' jam' }}</td>
                        <td class="border border-black px-2 py-1">{{ ($rekap['per_user'][$nama_karyawan]['hari_besar'] ?? 0) . ' jam' }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">{{ $totalTidakMasuk }} jam</td>
                        @if ($isHarianLepas)
                            @php
                                $totalSisaJam = collect($jumlahHariPerTanggal)
                                    ->filter(fn($item) => ($item['jumlah_hari'] ?? 0) > 0)
                                    ->sum('sisa_jam');
                            @endphp

                            <td class="border border-black px-2 py-1 font-semibold">
                                {{ $totalSisaJam > 0 ? $totalSisaJam . ' jam' : '-' }}
                            </td>


                            <td class="border border-black px-2 py-1 font-semibold">
                                {{ $jumlahHari }} hari
                            </td>
                        @endif
                    </tr>

                    {{-- GRAND TOTAL --}}
                    @php
                        $grandTotalSabtu = $rekap['per_user'][$nama_karyawan]['sabtu'] ?? 0;
                        $grandTotalMinggu = $rekap['per_user'][$nama_karyawan]['minggu'] ?? 0;
                        $grandTotalHariBesar = $rekap['per_user'][$nama_karyawan]['hari_besar'] ?? 0;
                        $grandTotalSJ = $rekap['per_user'][$nama_karyawan]['sj'] ?? 0;
                        $grandTotalTidakMasuk = $totalTidakMasuk;

                        if ($isHarianLepas) {
                            // Hitung sisa jam valid (hanya dari hari yang punya jumlah_hari > 0)
                            $totalSisaJam = collect($jumlahHariPerTanggal)
                                ->filter(fn($item) => ($item['jumlah_hari'] ?? 0) > 0)
                                ->sum('sisa_jam');

                            $grandTotalJam = ($grandTotalSabtu + $grandTotalMinggu + $grandTotalHariBesar)
                                - $grandTotalTidakMasuk
                                - $totalSisaJam;
                        } else {
                            // Untuk Harian Tetap
                            $grandTotalJam = ($grandTotalSJ + $grandTotalSabtu + $grandTotalMinggu + $grandTotalHariBesar) - $grandTotalTidakMasuk;
                        }

                        if ($grandTotalJam < 0) {
                            $grandTotalJam = 0;
                        }
                    @endphp

                    @php
                        // Sisa Jam total hanya jika jumlah_hari > 0
                        $totalSisaJam = collect($jumlahHariPerTanggal)
                            ->filter(fn($item) => ($item['jumlah_hari'] ?? 0) > 0)
                            ->sum('sisa_jam');
                    @endphp
                    <tr class="bg-green-200 font-semibold">
                        <td class="border border-black px-2 py-1 text-right">Grand Total</td>
                        <td colspan="{{ $isHarianLepas ? 5 : 5 }}" class="border border-black px-2 py-1 text-center">
                            {{ $grandTotalJam }} jam
                        </td>
                        @if ($isHarianLepas)
                            <td class="border border-black px-2 py-1 text-center text-gray-400" style="visibility: hidden">-</td>
                            <td class="border border-black px-2 py-1 text-center">
                                {{ $jumlahHari > 0 ? $jumlahHari . ' hari' : '-' }}
                            </td>

                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
            @endforeach
        </div>
    @endif
    </x-filament::card>
</x-filament::page>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/airbnb.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            fetch("https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json")
                .then(response => response.json())
                .then(libur => {
                    const tanggalMerah = Object.keys(libur);
                    const commonOptions = {
                        dateFormat: "Y-m-d",
                        onDayCreate: function (dObj, dStr, fp, dayElem) {
                            const dateStr = dayElem.dateObj.toLocaleDateString('sv-SE');
                            if (tanggalMerah.includes(dateStr)) {
                                dayElem.style.backgroundColor = "#f87171";
                                dayElem.style.color = "white";
                                dayElem.title = libur[dateStr];
                            }
                        }
                    };
                    flatpickr("#start_date", commonOptions);
                    flatpickr("#end_date", commonOptions);
                });
        });
    </script>
@endpush

@push('styles')
<style>
.custom-table {
    border-collapse: collapse;
    width: auto;
    margin: 0 auto;
    background-color: #ffffff;
    font-size: 0.75rem;
}

.custom-table th,
.custom-table td {
    border: 1px solid black;
    padding: 6px 10px;
    text-align: center;
    vertical-align: middle;
    white-space: normal;
    word-break: break-word;
    font-size: 0.75rem;
}

.custom-table th.tanggal,
.custom-table td.tanggal {
    width: 110px; /* Ukuran kolom Tanggal */
}

.custom-table th {
    background-color: #f3f4f6;
    font-weight: 600;
}

.custom-table tr:nth-child(even) {
    background-color: #f9fafb;
}

.custom-table tr:hover {
    background-color: #f1f5f9;
}

/* Untuk cetak */
@media print {
    .custom-table {
        font-size: 0.8rem;
        background: #ffffff;
    }
    .custom-table tr:nth-child(even),
    .custom-table tr:hover {
        background: #ffffff;
    }
}
</style>
@endpush
