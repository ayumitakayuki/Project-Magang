<x-filament::page>
    <div class="bg-white rounded-xl shadow p-6 space-y-6 border border-gray-200">
        <form method="GET" action="{{ route('filament.admin.pages.histori-slip-gaji') }}" class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-700">🔍 Cari Histori Slip Gaji</h2>

            <div>
                <label for="karyawan" class="block text-sm font-medium text-gray-600 mb-1">Pilih Karyawan</label>
                <select name="karyawan" id="karyawan" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach ((new \App\Filament\Admin\Pages\HistoriSlipGaji)->getKaryawanList() as $k)
                        <option value="{{ $k->id_karyawan }}" {{ request('karyawan') == $k->id_karyawan ? 'selected' : '' }}>
                            {{ $k->id_karyawan }} - {{ $k->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2 rounded-lg transition">
                    🔎 Tampilkan Histori
                </button>
            </div>
        </form>

        @if ($histori)
            <div class="overflow-x-auto mt-6">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-300 shadow-sm rounded-lg">
                    <thead class="bg-gray-100 text-sm text-gray-600 font-semibold">
                        <tr>
                            <th class="p-2 border">ID</th>
                            <th class="p-2 border">Nama</th>
                            <th class="p-2 border">Status</th>
                            <th class="p-2 border">Lokasi</th>
                            <th class="p-2 border">Proyek</th>
                            <th class="p-2 border">Periode</th>
                            <th class="p-2 border">Total Gaji</th>
                            <th class="p-2 border">Kasbon</th>
                            <th class="p-2 border">Grand Total</th>
                            <th class="p-2 border">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-sm">
                        @forelse ($histori as $item)
                            @php
                                $grand = $item->details->where('kode', 'grand')->first();
                                $kasbon = $item->details->where('kode', 'h')->first();
                                $subtotal = $item->details->where('kode', 'jml')->first();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="p-2 border">{{ $item->id_karyawan }}</td>
                                <td class="p-2 border">{{ $item->nama }}</td>
                                <td class="p-2 border">{{ $item->status }}</td>
                                <td class="p-2 border">{{ $item->lokasi }}</td>
                                <td class="p-2 border">{{ $item->jenis_proyek }}</td>
                                <td class="p-2 border">
                                    {{ \Carbon\Carbon::parse($item->periode_awal)->format('d M') }} -
                                    {{ \Carbon\Carbon::parse($item->periode_akhir)->format('d M Y') }}
                                </td>
                                <td class="p-2 border text-right">Rp {{ number_format($subtotal->total ?? 0, 0, ',', '.') }}</td>
                                <td class="p-2 border text-right">Rp {{ number_format($kasbon->total ?? 0, 0, ',', '.') }}</td>
                                <td class="p-2 border text-right font-semibold text-emerald-700">Rp {{ number_format($grand->total ?? 0, 0, ',', '.') }}</td>
                                <td class="p-2 border text-center">
                                    <a href="{{ route('filament.admin.pages.detail-slip-gaji', ['id' => $item->id]) }}" class="text-blue-600 hover:underline">Lihat</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-center text-gray-500 italic">Belum ada data slip gaji untuk karyawan ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament::page>
