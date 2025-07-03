<x-filament::page>
    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="{{ route('filament.admin.resources.absensis.create') }}"
           class="text-sm text-primary-600 hover:underline inline-flex items-center">
            ← Kembali ke Form Import
        </a>

        <div class="flex gap-2 flex-wrap">
            <x-filament::button wire:click="clearData" color="danger">
                🗑️ Clear Data
            </x-filament::button>

            <x-filament::button wire:click="saveAllToDatabase" color="success">
                💾 Simpan Semua ke Database
            </x-filament::button>
        </div>
    </div>

    {{-- Card Preview --}}
    <x-filament::card>
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Preview Data Hasil Import</h2>

        @if (count($data) > 0)
            <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                <table class="min-w-full text-sm text-gray-700">
                    <thead class="bg-gray-100 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-2 border">Nama</th>
                            <th class="px-4 py-2 border">Tanggal</th>
                            <th class="px-4 py-2 border">Masuk Pagi</th>
                            <th class="px-4 py-2 border">Keluar Siang</th>
                            <th class="px-4 py-2 border">Masuk Siang</th>
                            <th class="px-4 py-2 border">Pulang Kerja</th>
                            <th class="px-4 py-2 border">Masuk Lembur</th>
                            <th class="px-4 py-2 border">Pulang Lembur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($data as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border">{{ $row['name'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['tanggal'] }}</td>
                                <td class="px-4 py-2 border text-center">{{ $row['masuk_pagi'] }}</td>
                                <td class="px-4 py-2 border text-center">{{ $row['keluar_siang'] }}</td>
                                <td class="px-4 py-2 border text-center">{{ $row['masuk_siang'] }}</td>
                                <td class="px-4 py-2 border text-center">{{ $row['pulang_kerja'] }}</td>
                                <td class="px-4 py-2 border text-center">{{ $row['masuk_lembur'] }}</td>
                                <td class="px-4 py-2 border text-center">{{ $row['pulang_lembur'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 italic">Belum ada data yang diimpor.</p>
        @endif
    </x-filament::card>
</x-filament::page>
