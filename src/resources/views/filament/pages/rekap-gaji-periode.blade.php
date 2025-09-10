<x-filament::page>
    <x-filament::section class="mb-6">
        {{ $this->form }}
    </x-filament::section>

    @php
        $s = $this->filters['start_date'] ? \Carbon\Carbon::parse($this->filters['start_date']) : null;
        $e = $this->filters['end_date']   ? \Carbon\Carbon::parse($this->filters['end_date'])   : null;
        $labelPeriode = $s && $e ? $s->format('d M Y').' – '.$e->format('d M Y') : 'Semua periode';
    @endphp

    <div class="px-4 py-2 mb-3 text-sm text-gray-700 bg-gray-50 border rounded-lg">
        <span class="font-medium">Periode:</span> {{ $labelPeriode }}
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="mb-3 flex justify-end">
            <x-filament::button type="button" icon="heroicon-o-document-arrow-down" wire:click="exportPdf">
                Download PDF
            </x-filament::button>
        </div>
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
                            {{ ($this->filters['start_date'] && $this->filters['end_date'])
                                ? 'Tidak ada data pada periode ini.'
                                : 'Belum ada data.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::page>
