<x-filament::page>
    <div class="bg-white p-6 rounded-lg shadow border border-gray-300 space-y-6">

        {{-- Judul Halaman dan Tombol Ekspor --}}
        <div class="flex justify-between items-center border-b pb-4 border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">
                Detail Slip Gaji - {{ $gaji->nama }}
            </h2>

            <a href="{{ route('slip-gaji.export', ['id' => $gaji->id]) }}"
            class="inline-flex items-center px-4 py-2 border-2 border-emerald-700 bg-emerald-600 text-black text-sm font-semibold rounded-md shadow hover:bg-emerald-700 hover:border-emerald-800 hover:shadow-md transition duration-200">
                ⬇️ <span class="ml-1">Ekspor Excel</span>
            </a>
        </div>

        {{-- Informasi Karyawan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 border rounded-lg p-4 bg-gray-50 border-gray-200">
            <p><strong>ID Karyawan:</strong> {{ $gaji->id_karyawan }}</p>
            <p><strong>Nama:</strong> {{ $gaji->nama }}</p>
            <p><strong>Status:</strong> {{ $gaji->status }}</p>
            <p><strong>Lokasi:</strong> {{ $gaji->lokasi }}</p>
            <p><strong>Jenis Proyek:</strong> {{ $gaji->jenis_proyek }}</p>
            <p><strong>Periode:</strong> {{ $gaji->periode_awal }} s/d {{ $gaji->periode_akhir }}</p>
        </div>

        {{-- Tabel Detail Gaji --}}
        <div class="overflow-x-auto border border-gray-300 rounded-lg">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-700 border-b border-gray-300">
                    <tr>
                        <th class="p-3 border-r border-gray-300">Kode</th>
                        <th class="p-3 border-r border-gray-300">Keterangan</th>
                        <th class="p-3 border-r border-gray-300 text-center">Masuk</th>
                        <th class="p-3 border-r border-gray-300 text-center">Faktor</th>
                        <th class="p-3 border-r border-gray-300 text-right">Nominal</th>
                        <th class="p-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gaji->details as $item)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="p-3 border-r border-gray-200">{{ $item->kode }}</td>
                            <td class="p-3 border-r border-gray-200">{{ $item->keterangan }}</td>
                            <td class="p-3 border-r border-gray-200 text-center">{{ $item->masuk ?? '-' }}</td>
                            <td class="p-3 border-r border-gray-200 text-center">{{ $item->faktor ?? '-' }}</td>
                            <td class="p-3 border-r border-gray-200 text-right">Rp {{ number_format($item->nominal ?? 0, 0, ',', '.') }}</td>
                            <td class="p-3 text-right font-semibold">Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500">Tidak ada data detail gaji.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-filament::page>
