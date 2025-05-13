<x-filament::page>
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('filament.admin.resources.absensis.create') }}" class="text-primary-600 hover:underline">
            ← Kembali ke Form Import
        </a>

        <!-- Button to clear data -->
        <x-filament::button wire:click="clearData" color="danger">
            Clear Data
        </x-filament::button>

        <x-filament::button wire:click="saveAllToDatabase" color="success">
            Simpan Semua ke Database
        </x-filament::button>
    </div>

    <x-filament::card>
        <h2 class="text-lg font-bold mb-4">Preview Data Hasil Import</h2>

        @if (count($data) > 0)
            <div class="overflow-x-auto rounded-xl border">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-100 text-xs font-semibold">
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
                    <tbody>
                        @foreach ($data as $row)
                            <tr>
                                <td class="px-4 py-2 border">{{ $row['name'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['tanggal'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['masuk_pagi'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['keluar_siang'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['masuk_siang'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['pulang_kerja'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['masuk_lembur'] }}</td>
                                <td class="px-4 py-2 border">{{ $row['pulang_lembur'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">Belum ada data yang diimpor.</p>
        @endif
    </x-filament::card>
</x-filament::page>
