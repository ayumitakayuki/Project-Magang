<x-filament::page>
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="{{ route('filament.admin.resources.absensis.create') }}"
           class="text-sm text-primary-600 hover:underline inline-flex items-center font-medium">
            ← Kembali ke Form Import
        </a>

        <div class="flex gap-2 flex-wrap">
            <x-filament::button wire:click="clearData" color="danger" icon="heroicon-o-trash">
                Hapus Data
            </x-filament::button>
            <x-filament::button wire:click="saveAllToDatabase" color="success" icon="heroicon-o-arrow-down-tray">
                Simpan ke Database
            </x-filament::button>
        </div>
    </div>

    <div class="mb-6">
        {{-- <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                📋 Preview Data Hasil Import
            </h2>
            {{-- <span class="text-sm text-gray-500">
                Total baris: {{ count($data) }}
            </span>
        </div> --}}

        @if (count($data) > 0)
            <div class="overflow-auto rounded-xl border border-gray-300 shadow-sm max-h-[70vh] w-full">
                <table class="w-full text-sm text-gray-800 table-auto">
                    <thead class="bg-gray-100 text-xs font-semibold sticky top-0 z-10">
                        <tr class="text-left text-gray-600 border-b border-gray-300">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3 text-center">Masuk Pagi</th>
                            <th class="px-4 py-3 text-center">Keluar Siang</th>
                            <th class="px-4 py-3 text-center">Masuk Siang</th>
                            <th class="px-4 py-3 text-center">Pulang Kerja</th>
                            <th class="px-4 py-3 text-center">Masuk Lembur</th>
                            <th class="px-4 py-3 text-center">Pulang Lembur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($data as $row)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-2">{{ $row['name'] }}</td>
                                <td class="px-4 py-2">{{ $row['tanggal'] }}</td>
                                <td class="px-4 py-2 text-center">{{ $row['masuk_pagi'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-center">{{ $row['keluar_siang'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-center">{{ $row['masuk_siang'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-center">{{ $row['pulang_kerja'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-center">{{ $row['masuk_lembur'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-center">{{ $row['pulang_lembur'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-sm text-gray-500 italic">Belum ada data yang diimpor.</div>
        @endif
    </div>
</x-filament::page>
