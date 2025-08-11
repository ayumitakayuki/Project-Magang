<x-filament::page>
    <div class="bg-white p-6 rounded-lg shadow border border-gray-300 space-y-6">

        {{-- Judul Halaman dan Tombol Ekspor --}}
        <div class="flex justify-between items-center border-b pb-4 border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">
                Detail Slip Gaji - {{ $gaji->nama }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('slip-gaji.export', ['id' => $gaji->id]) }}"
                    class="inline-flex items-center px-4 py-2 border-2 border-emerald-700 bg-emerald-600 text-black text-sm font-semibold rounded-md shadow hover:bg-emerald-700 hover:border-emerald-800 hover:shadow-md transition duration-200">
                    <span class="ml-1">Export Excel</span>
                </a>
                <a href="{{ route('slip-gaji.export.pdf', ['id' => $gaji->id]) }}"
                    class="inline-flex items-center px-4 py-2 border-2 border-red-700 bg-red-600 text-black text-sm font-semibold rounded-md shadow hover:bg-red-700 hover:border-red-800 hover:shadow-md transition duration-200">
                    <span class="ml-1">Export PDF</span>
                </a>
            </div>
        </div>

        {{-- Informasi Karyawan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 border rounded-lg p-4 bg-gray-50 border-gray-200">
            <p><strong>ID Karyawan:</strong> {{ $gaji->id_karyawan }}</p>
            <p><strong>Nama:</strong> {{ $gaji->nama }}</p>
            <p><strong>Status:</strong> {{ $gaji->status }}</p>
            <p><strong>Lokasi:</strong> {{ $gaji->lokasi }}</p>
            <p><strong>Jenis Proyek:</strong> {{ $gaji->jenis_proyek }}</p>
            <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($gaji->periode_awal)->format('Y-m-d') }} s/d {{ \Carbon\Carbon::parse($gaji->periode_akhir)->format('Y-m-d') }}</p>
        </div>

        {{-- Tabel Detail Gaji --}}
        <div class="overflow-x-auto border border-gray-300 rounded-lg">
            <table class="w-full text-sm text-left table-fixed">
                <thead class="bg-gray-100 text-gray-700 border-b border-gray-300">
                    <tr>
                        <th class="w-12 p-3 border-r border-gray-300 text-center">Kode</th>
                        <th class="p-3 border-r border-gray-300">Keterangan</th>
                        <th class="w-20 p-3 border-r border-gray-300 text-center">Masuk</th>
                        <th class="w-20 p-3 border-r border-gray-300 text-center">Faktor</th>
                        <th class="w-36 p-3 border-r border-gray-300 text-right">Nominal</th>
                        <th class="w-36 p-3 text-right">Total</th>
                    </tr>
                </thead>

                @php
                    $labels = range('a', 'z');
                    $i = 0;
                @endphp

                <tbody>
                    @foreach ($gaji->details as $item)
                        @php
                            $isJml = strtolower($item->kode) === 'jml';
                            $isGrand = strtolower($item->kode) === 'grand';
                            $rowClass = $isJml ? 'bg-yellow-100 font-semibold' : ($isGrand ? 'bg-green-100 font-bold' : '');
                        @endphp

                        <tr class="border-b border-gray-200 hover:bg-gray-50 {{ $rowClass }}">
                            <td class="p-3 border-r border-gray-200 text-center">
                                @if (!$isJml && !$isGrand)
                                    {{ $labels[$i++] ?? '?' }}
                                @else
                                    {{ $item->kode }}
                                @endif
                            </td>
                            <td class="p-3 border-r border-gray-200">{{ $item->keterangan }}</td>
                            <td class="p-3 border-r border-gray-200 text-center">
                                @if(is_numeric($item->masuk) && $item->masuk > 0)
                                    {{ number_format($item->masuk, (fmod($item->masuk, 1) == 0 ? 0 : 1), ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-3 border-r border-gray-200 text-center">
                                @if(is_numeric($item->faktor) && $item->faktor != 1)
                                    {{ fmod($item->faktor, 1) == 0 ? number_format($item->faktor, 0, ',', '.') : number_format($item->faktor, 1, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-3 border-r border-gray-200 text-right">Rp {{ number_format($item->nominal ?? 0, 0, ',', '.') }}</td>
                            <td class="p-3 text-right">Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>
