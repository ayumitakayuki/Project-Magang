<x-filament::page>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4">Rekapitulasi Absensi</h2>

        <form method="GET" class="mb-4 flex flex-wrap gap-2 items-center">
            <select name="name" class="rounded-lg px-2 py-1 bg-blue-100">
                @foreach ($all_names as $name)
                    <option value="{{ $name }}" {{ request('name') === $name ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="start_date" value="{{ request('start_date') ?? now()->subMonth()->toDateString() }}"
                   class="rounded-lg px-2 py-1 bg-blue-100" />
            <span>-</span>
            <input type="date" name="end_date" value="{{ request('end_date') ?? now()->toDateString() }}"
                   class="rounded-lg px-2 py-1 bg-blue-100" />
            <button type="submit" class="px-4 py-1 bg-green-300 rounded-lg hover:bg-green-400 transition">
                Filter
            </button>
        </form>

        @if (!empty($rekap))
            <table class="table-auto w-full border text-left">
                <thead>
                    <tr class="bg-gray-100">
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
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500 mt-4">Tidak ada data absensi yang ditemukan untuk filter ini.</p>
        @endif
    </x-filament::card>
</x-filament::page>
