<x-filament::page>
    <x-filament::card class="bg-blue-100 rounded-xl p-6">
        {{-- JUDUL + SHOW ALL --}}
         <div class="flex gap-2 items-center">
            <select name="status_karyawan"
                onchange="location = this.value"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm border border-blue-500">
                <option value="?show_all=1&start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&status_karyawan=all"
                    {{ request('status_karyawan') == 'all' ? 'selected' : '' }}>
                    Show All
                </option>
                <option value="?show_all=1&start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&status_karyawan=staff"
                    {{ request('status_karyawan') == 'staff' ? 'selected' : '' }}>
                    Staff
                </option>
                <option value="?show_all=1&start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&status_karyawan=harian tetap"
                    {{ request('status_karyawan') == 'harian tetap' ? 'selected' : '' }}>
                    Harian Tetap
                </option>
                <option value="?show_all=1&start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&status_karyawan=harian lepas"
                    {{ request('status_karyawan') == 'harian lepas' ? 'selected' : '' }}>
                    Harian Lepas
                </option>
            </select>

        </div>
    
        {{-- FORM FILTER --}}
        <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="karyawan_keyword"
                value="{{ request('karyawan_keyword') }}"
                placeholder="🔍 Search ID/Name"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm w-64"
            />

            <select name="lokasi"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm">
                <option value="">Lokasi</option>
                @foreach ($lokasi_options as $lokasi)
                    <option value="{{ $lokasi }}" {{ request('lokasi') == $lokasi ? 'selected' : '' }}>
                        {{ ucfirst($lokasi) }}
                    </option>
                @endforeach
            </select>

            <select name="proyek"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm">
                <option value="">Proyek</option>
                @foreach ($proyek_options as $proyek)
                    <option value="{{ $proyek }}" {{ request('proyek') == $proyek ? 'selected' : '' }}>
                        {{ $proyek }}
                    </option>
                @endforeach
            </select>

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

            <button type="submit"
                class="px-4 py-1 bg-green-300 hover:bg-green-400 text-sm rounded-lg transition">
                Filter
            </button>
        </form>

        @if (!empty($data_harian))
        <div class="flex flex-col lg:flex-row gap-4 items-start mb-6">
        {{-- BAGIAN KIRI: IDENTITAS --}}
        <div class="bg-white border border-gray-300 rounded-lg px-2 py-1 shadow-sm text-sm w-48 leading-tight">
            <div class="space-y-1">
                <div>
                    <span class="text-gray-500">ID Karyawan</span><br>
                    <span class="text-gray-800">{{ $selected_id ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Nama Karyawan</span><br>
                    <span class="text-gray-800">{{ $selected_name ?? '-' }}</span>
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
                    <span class="text-gray-800">{{ $status_karyawan ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Lokasi</span><br>
                    <span class="text-gray-800">{{ $selected_lokasi ?? '-' }}</span>
                </div>

                @if ($selected_lokasi === 'proyek')
                        <div>
                            <span class="text-gray-500">Jenis Proyek</span><br>
                            <span class="text-gray-800">{{ $selected_proyek ?? '-' }}</span>
                        </div>
                @endif
            </div>
        </div>
            {{-- BAGIAN KANAN: TABEL --}}
            <div class="w-full lg:w-2/3 overflow-x-auto">
                <table class="w-full text-sm text-center border border-black table-fixed bg-white shadow-md">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border px-5 py-1">Tanggal</th>
                            <th class="border px-2 py-1">Masuk Pagi</th>
                            <th class="border px-2 py-1">Keluar Siang</th>
                            <th class="border px-2 py-1">Masuk Siang</th>
                            <th class="border px-2 py-1">Pulang Kerja</th>
                            <th class="border px-2 py-1">Masuk Lembur</th>
                            <th class="border px-2 py-1">Pulang Lembur</th>
                            <th class="border px-5 py-1">SJ</th>
                            <th class="border px-2 py-1">Sabtu</th>
                            <th class="border px-2 py-1">Minggu</th>
                            <th class="border px-2 py-1">Hari Besar</th>
                            <th class="border px-2 py-1">Tidak Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data_harian as $absen)
                            @php
                                $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
                                $rekap_tanggal = $rekap['per_tanggal'][$tanggal] ?? [
                                    'sj' => '-',
                                    'sabtu' => '-',
                                    'minggu' => '-',
                                    'hari_besar' => '-',
                                    'tidak_masuk' => '-',
                                ];
                            @endphp
                            <tr>
                                <td class="border px-2 py-1">{{ \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y') }}</td>
                                <td class="border px-2 py-1">
                                    {{ $absen->masuk_pagi ? \Carbon\Carbon::parse($absen->masuk_pagi)->format('H:i') : '-' }}
                                </td>
                                <td class="border px-2 py-1">
                                    {{ $absen->keluar_siang ? \Carbon\Carbon::parse($absen->keluar_siang)->format('H:i') : '-' }}
                                </td>
                                <td class="border px-2 py-1">
                                    {{ $absen->masuk_siang ? \Carbon\Carbon::parse($absen->masuk_siang)->format('H:i') : '-' }}
                                </td>
                                <td class="border px-2 py-1">
                                    {{ $absen->pulang_kerja ? \Carbon\Carbon::parse($absen->pulang_kerja)->format('H:i') : '-' }}
                                </td>
                                <td class="border px-2 py-1">
                                    {{ $absen->masuk_lembur ? \Carbon\Carbon::parse($absen->masuk_lembur)->format('H:i') : '-' }}
                                </td>
                                <td class="border px-2 py-1">
                                    {{ $absen->pulang_lembur ? \Carbon\Carbon::parse($absen->pulang_lembur)->format('H:i') : '-' }}
                                </td>
                                <td class="border px-2 py-1">{{ $rekap_tanggal['sj'] }}</td>
                                <td class="border px-2 py-1">{{ $rekap_tanggal['sabtu'] }}</td>
                                <td class="border px-2 py-1">{{ $rekap_tanggal['minggu'] }}</td>
                                <td class="border px-2 py-1">{{ $rekap_tanggal['hari_besar'] }}</td>
                                <td class="border px-2 py-1">{{ $rekap_tanggal['tidak_masuk'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
