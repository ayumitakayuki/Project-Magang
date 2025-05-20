<x-filament::page>
    <x-filament::card class="bg-blue-100 rounded-xl p-6">
        <h2 class="text-xl font-bold mb-4">Rekapitulasi Absensi</h2>

        {{-- FORM FILTER --}}
        <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
            <input
                type="text"
                name="karyawan_keyword"
                value="{{ request('karyawan_keyword') }}"
                placeholder="🔍 Search ID/Name"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm w-64"
            />

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

        {{-- INFO PENCARIAN --}}
        <div class="bg-white border border-blue-300 rounded-lg p-4 mb-6 shadow-sm text-sm">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-y-2 gap-x-6">
                <div>
                    <span class="text-gray-500">ID Karyawan</span><br>
                    <span class="font-semibold text-lg text-blue-700">{{ $selected_id ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Nama Karyawan</span><br>
                    <span class="font-semibold text-lg text-blue-700">{{ $selected_name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Tanggal Pencarian</span><br>
                    <span class="font-semibold text-lg text-blue-700">
                        {{ \Carbon\Carbon::parse($start_date)->format('d-m-Y') }}
                        s/d
                        {{ \Carbon\Carbon::parse($end_date)->format('d-m-Y') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- TABEL REKAP PER TANGGAL (SESUAI GAMBAR) --}}
        @if (!empty($rekap['per_tanggal']))
            <h3 class="text-md font-bold mb-2">Rekap Per Tanggal</h3>
            <table class="w-full text-sm border border-black text-center bg-white shadow-md">
                <thead class="bg-gray-100 font-bold text-black">
                    <tr>
                        <th class="border px-3 py-2" rowspan="2">Tanggal</th>
                        <th class="border px-3 py-2" colspan="5">HARI</th>
                    </tr>
                    <tr>
                        <th class="border px-3 py-2">S-J</th>
                        <th class="border px-3 py-2">SABTU</th>
                        <th class="border px-3 py-2">MINGGU</th>
                        <th class="border px-3 py-2">HARI BESAR</th>
                        <th class="border px-3 py-2">TIDAK MASUK</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekap['per_tanggal'] as $tanggal => $data)
                        <tr>
                            <td class="border px-3 py-1">{{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}</td>
                            <td class="border px-3 py-1">{{ $data['sj'] ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $data['sabtu'] ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $data['minggu'] ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $data['hari_besar'] ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $data['tidak_masuk'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-bold bg-gray-50">
                        <td class="border px-3 py-2">TOTAL</td>
                        <td class="border px-3 py-2">{{ $rekap['sj'] ?? '0 jam' }}</td>
                        <td class="border px-3 py-2">{{ $rekap['sabtu'] ?? '0 jam' }}</td>
                        <td class="border px-3 py-2">{{ $rekap['minggu'] ?? '0 jam' }}</td>
                        <td class="border px-3 py-2">{{ $rekap['hari_besar'] ?? '0 jam' }}</td>
                        <td class="border px-3 py-2">{{ $rekap['tidak_masuk'] ?? 0 }} hari</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- TABEL DETAIL ABSENSI HARIAN --}}
        @if (!empty($data_harian))
            <h3 class="text-md font-bold mt-8 mb-2">Detail Absensi Harian</h3>
            <table class="w-full text-sm border border-black text-center bg-white shadow-md">
                <thead class="bg-gray-200 font-bold text-black">
                    <tr>
                        <th class="border px-3 py-2">Tanggal</th>
                        <th class="border px-3 py-2">Masuk Pagi</th>
                        <th class="border px-3 py-2">Keluar Siang</th>
                        <th class="border px-3 py-2">Masuk Siang</th>
                        <th class="border px-3 py-2">Pulang Kerja</th>
                        <th class="border px-3 py-2">Masuk Lembur</th>
                        <th class="border px-3 py-2">Pulang Lembur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data_harian as $absen)
                        <tr>
                            <td class="border px-3 py-1">{{ \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y') }}</td>
                            <td class="border px-3 py-1">{{ $absen->masuk_pagi ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $absen->keluar_siang ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $absen->masuk_siang ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $absen->pulang_kerja ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $absen->masuk_lembur ?? '-' }}</td>
                            <td class="border px-3 py-1">{{ $absen->pulang_lembur ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
