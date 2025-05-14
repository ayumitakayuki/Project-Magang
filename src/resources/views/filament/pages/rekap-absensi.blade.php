<x-filament::page>
    <x-filament::card class="bg-blue-100 rounded-xl p-6">
        <h2 class="text-xl font-bold mb-4">Rekapitulasi Absensi</h2>

        {{-- FORM FILTER --}}
        <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
            {{-- Input Cari Karyawan (ID atau Nama) --}}
            <input
                type="text"
                name="karyawan_keyword"
                value="{{ request('karyawan_keyword') }}"
                placeholder="🔍 Search ID/Name"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm w-64"
            />

            {{-- Filter Tanggal --}}
            <input
                type="date"
                name="start_date"
                value="{{ request('start_date') ?? now()->subMonth()->toDateString() }}"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm"
            />
            <span>-</span>
            <input
                type="date"
                name="end_date"
                value="{{ request('end_date') ?? now()->toDateString() }}"
                class="rounded-lg px-3 py-1 bg-blue-200 text-sm"
            />

            {{-- Tombol Filter --}}
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

        {{-- TABEL REKAP --}}
        @if (!empty($rekap))
            <table class="w-full table-auto text-sm bg-blue-200 rounded-lg overflow-hidden border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">Kategori</th>
                        <th class="p-2 border">Jumlah Hari</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekap as $kategori => $jumlah)
                        <tr>
                            <td class="p-2 border">{{ $kategori }}</td>
                            <td class="p-2 border">{{ $jumlah }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold bg-blue-300">
                        <td class="p-2 border">Total</td>
                        <td class="p-2 border">{{ array_sum($rekap) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p class="text-gray-500 mt-4 text-sm">Tidak ada data untuk filter yang diberikan.</p>
        @endif
    </x-filament::card>
</x-filament::page>
