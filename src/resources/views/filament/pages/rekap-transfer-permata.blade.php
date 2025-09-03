<x-filament::page>
    <x-filament::section class="mb-6">
        {{ $this->form }}
    </x-filament::section>

    @php
        $s = $this->start_date ? \Carbon\Carbon::parse($this->start_date) : null;
        $e = $this->end_date   ? \Carbon\Carbon::parse($this->end_date)   : null;

        $labelPeriode = 'Semua periode';
        $labelGaji15  = 'Semua bulan';
        $labelGaji16  = 'Semua bulan';

        if ($s && $e) {
            if ($s->isSameMonth($e)) {
                $lastDay = $s->copy()->endOfMonth()->day;

                if ($s->day === 1 && $e->day === 15) {
                    // 01–15 bulan ini → 16–31 bulan sebelumnya
                    $labelPeriode = '01–15 ' . $s->format('F Y');
                    $labelGaji15  = $s->format('F Y');
                    $labelGaji16  = $s->copy()->subMonth()->format('F Y');
                } elseif ($s->day >= 16 && $e->day === $lastDay) {
                    // 16–akhir bulan ini
                    $labelPeriode = '16–' . $lastDay . ' ' . $s->format('F Y');
                    $labelGaji15  = $s->format('F Y');
                    $labelGaji16  = $s->format('F Y');
                } else {
                    // range lain di bulan yang sama
                    $labelPeriode = $s->format('d M Y') . ' – ' . $e->format('d M Y');
                    $labelGaji15  = $s->format('F Y');
                    $labelGaji16  = $s->format('F Y');
                }
            } else {
                // lintas bulan
                $labelPeriode = $s->format('d M Y') . ' – ' . $e->format('d M Y');
                $labelGaji15  = $s->format('M Y') . ' – ' . $e->format('M Y');
                $labelGaji16  = $labelGaji15;
            }
        }

        // totals untuk footer
        $totals = [
            'pembulatan'  => collect($rows)->sum(fn($r) => $r['pembulatan']  ?? 0),
            'kasbon'      => collect($rows)->sum(fn($r) => $r['kasbon']      ?? 0),
            'sisa_kasbon' => collect($rows)->sum(fn($r) => $r['sisa_kasbon'] ?? 0),
            'gaji_16_31'  => collect($rows)->sum(fn($r) => $r['gaji_16_31']  ?? 0),
            'gaji_15_31'  => collect($rows)->sum(fn($r) => $r['gaji_15_31']  ?? 0),
        ];
    @endphp

    <div class="px-4 py-2 mb-3 text-sm text-gray-700 bg-gray-50 border rounded-lg flex flex-wrap items-center justify-between gap-2">
        <div><span class="font-medium">Periode:</span> {{ $labelPeriode }}</div>
            <div class="space-x-4">
                <span><span class="font-medium">Gaji 01–15:</span> {{ $labelGaji15 }}</span>
                <span><span class="font-medium">Gaji 16–31:</span> {{ $labelGaji16 }}</span>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="mb-3 flex justify-end">
        <x-filament::button type="button" icon="heroicon-o-document-arrow-down" wire:click="exportPdf">
            Download PDF
        </x-filament::button>
    </div>
        <table class="min-w-[1100px] w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-700">
                    <th class="px-4 py-3 font-semibold">No</th>
                    <th class="px-4 py-3 font-semibold">No ID / Kode</th>
                    <th class="px-4 py-3 font-semibold">Bagian</th>
                    <th class="px-4 py-3 font-semibold">Lokasi</th>
                    <th class="px-4 py-3 font-semibold">Proyek</th>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold text-right">Pembulatan</th>
                    <th class="px-4 py-3 font-semibold text-right">Kasbon</th>
                    <th class="px-4 py-3 font-semibold text-right">Sisa Kasbon</th>
                    <th class="px-4 py-3 font-semibold text-right">
                        Gaji 16-31
                        <span class="block md:inline text-[10px] text-gray-500 font-normal">({{ $labelGaji16 }})</span>
                    </th>
                    <th class="px-4 py-3 font-semibold text-right">
                        Gaji 01-15
                        <span class="block md:inline text-[10px] text-gray-500 font-normal">({{ $labelGaji15 }})</span>
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $i => $r)
                    <tr class="odd:bg-white even:bg-gray-50/50">
                        <td class="px-4 py-2.5">{{ $i + 1 }}</td>
                        <td class="px-4 py-2.5">{{ $r['id_karyawan'] ?? ($r['no_id'] ?? '') }}</td>
                        <td class="px-4 py-2.5">{{ $r['bagian'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['lokasi'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['project'] ?? ($r['proyek'] ?? '') }}</td>
                        <td class="px-4 py-2.5">{{ $r['nama'] ?? '' }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['pembulatan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kasbon'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['sisa_kasbon'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['gaji_16_31'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['gaji_15_31'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-600">
                            Tidak ada data pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if (count($rows) > 0)
                <tfoot class="bg-gray-50 border-t">
                    <tr class="text-gray-900 font-semibold">
                        <td colspan="6" class="px-4 py-3 text-right">TOTAL</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['pembulatan'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['kasbon'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['sisa_kasbon'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['gaji_16_31'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($totals['gaji_15_31'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-filament::page>
