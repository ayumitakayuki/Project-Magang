<x-filament::page>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-bold mb-2">Detail Slip Gaji - {{ $gaji->nama }}</h2>

        <div class="mb-4 text-sm text-gray-700">
            <p><strong>ID Karyawan:</strong> {{ $gaji->id_karyawan }}</p>
            <p><strong>Nama:</strong> {{ $gaji->nama }}</p>
            <p><strong>Status:</strong> {{ $gaji->status }}</p>
            <p><strong>Lokasi:</strong> {{ $gaji->lokasi }}</p>
            <p><strong>Jenis Proyek:</strong> {{ $gaji->jenis_proyek }}</p>
            <p><strong>Periode:</strong> {{ $gaji->periode_awal }} s/d {{ $gaji->periode_akhir }}</p>
        </div>

        <a href="{{ route('slip-gaji.export', ['id' => $gaji->id]) }}" class="inline-block bg-emerald-600 text-white px-4 py-2 mb-4 rounded hover:bg-emerald-700">
            ⬇️ Ekspor Excel
        </a>

        <table class="w-full text-sm border border-gray-300 rounded overflow-hidden">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border">Kode</th>
                    <th class="p-2 border">Keterangan</th>
                    <th class="p-2 border">Masuk</th>
                    <th class="p-2 border">Faktor</th>
                    <th class="p-2 border">Nominal</th>
                    <th class="p-2 border">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($gaji->details as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 border">{{ $item->kode }}</td>
                        <td class="p-2 border">{{ $item->keterangan }}</td>
                        <td class="p-2 border text-center">{{ $item->masuk ?? '-' }}</td>
                        <td class="p-2 border text-center">{{ $item->faktor ?? '-' }}</td>
                        <td class="p-2 border text-right">Rp {{ number_format($item->nominal ?? 0, 0, ',', '.') }}</td>
                        <td class="p-2 border text-right font-medium">Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::page>