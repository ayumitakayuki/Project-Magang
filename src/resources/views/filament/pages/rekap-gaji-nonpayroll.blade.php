<x-filament::page>
    <x-filament::section class="mb-6">
        {{ $this->form }}
    </x-filament::section>

    @php
        $s = $this->start_date ? \Carbon\Carbon::parse($this->start_date) : null;
        $e = $this->end_date   ? \Carbon\Carbon::parse($this->end_date)   : null;

        $labelPeriode = $s && $e
            ? $s->format('d M Y') . ' – ' . $e->format('d M Y')
            : 'Semua periode';

        // totals footer
        $totals = [
            'pembulatan'        => collect($rows)->sum(fn($r) => $r['pembulatan']        ?? 0),
            'kasbon'            => collect($rows)->sum(fn($r) => $r['kasbon']            ?? 0),
            'sisa_kasbon'       => collect($rows)->sum(fn($r) => $r['sisa_kasbon']       ?? 0),
            'total_setelah_bon' => collect($rows)->sum(fn($r) => $r['total_setelah_bon'] ?? ($r['total_slip'] ?? 0)),
        ];
    @endphp

    <div class="px-4 py-2 mb-3 text-sm text-gray-700 bg-gray-50 border rounded-lg">
        <span class="font-medium">Periode:</span> {{ $labelPeriode }}
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-[1200px] w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-700">
                    <th class="px-4 py-3 font-semibold text-center">±</th>
                    <th class="px-4 py-3 font-semibold text-right">Pembulatan</th>
                    <th class="px-4 py-3 font-semibold text-right">Kasbon</th>
                    <th class="px-4 py-3 font-semibold text-right">Sisa Kasbon</th>
                    <th class="px-4 py-3 font-semibold text-right">Total Setelah Bon</th>
                    <th class="px-4 py-3 font-semibold">CD</th>
                    <th class="px-4 py-3 font-semibold text-center">No</th>
                    <th class="px-4 py-3 font-semibold">No ID</th>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Bagian</th>
                    <th class="px-4 py-3 font-semibold">Project</th>
                    <th class="px-4 py-3 font-semibold">Lokasi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $i => $r)
                    <tr class="odd:bg-white even:bg-gray-50/50">
                        <td class="px-4 py-2.5 text-center">{{ $r['plus'] ?? '' }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['pembulatan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kasbon'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['sisa_kasbon'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold">
                            Rp {{ number_format(($r['total_setelah_bon'] ?? ($r['total_slip'] ?? 0)), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5">{{ $r['cd'] ?? '' }}</td>
                        <td class="px-4 py-2.5 text-center">{{ $i + 1 }}</td>
                        <td class="px-4 py-2.5">{{ $r['no_id'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['nama'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['bagian'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['project'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['lokasi'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-gray-600">
                            {{ ($this->start_date && $this->end_date) ? 'Tidak ada data pada periode ini.' : 'Belum ada data.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if (count($rows) > 0)
                <tfoot class="bg-gray-50 border-t">
                    <tr class="text-gray-900 font-semibold">
                        <td colspan="1" class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['pembulatan'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['kasbon'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['sisa_kasbon'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['total_setelah_bon'], 0, ',', '.') }}</td>
                        <td colspan="7" class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
        <div class="mb-3 flex justify-end">
            <x-filament::button type="button" icon="heroicon-o-document-arrow-down" wire:click="exportPdf">
                Download PDF
            </x-filament::button>
        </div>
    </div>
</x-filament::page>
