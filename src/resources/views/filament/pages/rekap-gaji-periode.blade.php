<x-filament::page>
    {{-- Banner Mode Edit (tanpa tombol keluar) --}}
    @if ($this->isEditing ?? false)
        <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 text-yellow-900 p-3">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                <span class="font-semibold">Mode Edit:</span>
                <span>Anda sedang mengedit rekap gaji yang sudah pernah disimpan.</span>
            </div>
            <div class="mt-1 text-sm">
                Periode:
                {{ \Carbon\Carbon::parse($this->filters['start_date'])->format('d M Y') }}
                —
                {{ \Carbon\Carbon::parse($this->filters['end_date'])->format('d M Y') }}
                (ID: {{ $this->editingId }})
            </div>
        </div>
    @endif

    <x-filament::section class="mb-6">
        {{ $this->form }}
    </x-filament::section>

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
                @forelse ($rows as $r)
                    @php
                        $isTotal = in_array($r['keterangan'] ?? '', ['TOTAL PAYROLL', 'TOTAL CASH', 'Grand Total'], true);
                    @endphp
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
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            Tidak ada data untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::page>
