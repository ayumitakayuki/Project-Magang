<x-filament::page>
    <div class="space-y-6">
        <div class="rounded-xl border bg-white p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Periode</div>
                    <div class="text-lg font-semibold">
                        {{ optional($rekap->start_date)->format('d M Y') }} — {{ optional($rekap->end_date)->format('d M Y') }}
                    </div>
                    <div class="text-sm text-gray-500">
                        Disimpan: {{ optional($rekap->created_at)->format('d M Y H:i') }}
                        @if($rekap->user) · oleh {{ $rekap->user->name }} @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="fi-btn fi-color-danger">
                        Cetak PDF
                    </a>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-3">
                <div class="rounded-lg bg-gray-50 p-3">
                    <div class="text-sm text-gray-500">Total Payroll</div>
                    <div class="text-lg font-semibold">Rp {{ number_format($rekap->total_payroll, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3">
                    <div class="text-sm text-gray-500">Total Non Payroll</div>
                    <div class="text-lg font-semibold">Rp {{ number_format($rekap->total_non_payroll, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3">
                    <div class="text-sm text-gray-500">Grand Total</div>
                    <div class="text-lg font-semibold text-emerald-600">Rp {{ number_format($rekap->total_grand, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-700">
                    <th class="px-4 py-3 font-semibold">No ID</th>
                    <th class="px-4 py-3 font-semibold">Keterangan</th>
                    <th class="px-4 py-3 font-semibold">Lokasi</th>
                    <th class="px-4 py-3 font-semibold">Proyek</th>
                    <th class="px-4 py-3 font-semibold text-right">Jumlah</th>
                    <th class="px-4 py-3 font-semibold text-right">Jumlah Karyawan</th>
                    <th class="px-4 py-3 font-semibold">TRF</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($this->rowsView as $r)
                    @php $isTotal = in_array($r['keterangan'] ?? '', ['TOTAL PAYROLL','TOTAL CASH','Grand Total'], true); @endphp
                    <tr class="odd:bg-white even:bg-gray-50/50">
                        <td class="px-4 py-2.5 {{ $isTotal ? 'font-semibold' : '' }}">{{ $r['no_id'] ?? '' }}</td>
                        <td class="px-4 py-2.5 {{ $isTotal ? 'font-semibold' : '' }}">{{ $r['keterangan'] ?? '' }}</td>
                        <td class="px-4 py-2.5 {{ $isTotal ? 'font-semibold' : '' }}">{{ $r['lokasi'] ?? '' }}</td>
                        <td class="px-4 py-2.5 {{ $isTotal ? 'font-semibold' : '' }}">{{ $r['proyek'] ?? '' }}</td>
                        <td class="px-4 py-2.5 text-right {{ $isTotal ? 'font-semibold' : '' }}">
                            Rp {{ number_format($r['jumlah'] ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right {{ $isTotal ? 'font-semibold' : '' }}">
                            {{ $r['jumlah_karyawan'] ?? 0 }}
                        </td>
                        <td class="px-4 py-2.5 {{ $isTotal ? 'font-semibold' : '' }}">{{ $r['trf'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada data untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</x-filament::page>
