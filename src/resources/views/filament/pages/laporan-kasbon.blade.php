<x-filament::page>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
            <input
                type="month"
                name="bulan"
                value="{{ request('bulan', $this->bulan ?? now()->format('Y-m')) }}"
                class="block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan (opsional)</label>
            <input
                type="text"
                name="q"
                value="{{ request('q', $this->q ?? '') }}"
                placeholder="Cari nama karyawan"
                class="block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
        <div class="flex items-end gap-2">
             <x-filament::button type="submit">Terapkan</x-filament::button>
             <x-filament::button color="gray" type="button" wire:click="exportPdf">
                 Export PDF
             </x-filament::button>
         </div>
    </form>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-[800px] w-full table-fixed text-sm">
            {{-- Atur lebar kolom di sini --}}
            <colgroup>
                <col style="width:30%">
                <col style="width:14%">
                <col style="width:14%">
                <col style="width:14%">
                <col style="width:14%">
                <col style="width:14%">
            </colgroup>
            <colgroup>
                <col style="width:26%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:12%">
            </colgroup>

            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr class="text-left text-gray-700">
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold text-right">Pokok</th>
                    <th class="px-4 py-3 font-semibold text-right">X</th>
                    <th class="px-4 py-3 font-semibold text-right">Sisa Bulan Lalu</th>
                    <th class="px-4 py-3 font-semibold text-right">Potong 01–15</th>
                    <th class="px-4 py-3 font-semibold text-right">Potong 16–Akhir</th>
                    <th class="px-4 py-3 font-semibold text-right">Sisa X</th>
                    <th class="px-4 py-3 font-semibold text-right">Sisa Bulan Ini</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse ($this->rows as $r)
                    <tr class="odd:bg-white even:bg-gray-50/50">
                        <td class="px-4 py-2.5">{{ $r['nama'] }}</td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            Rp {{ number_format($r['pokok'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            {{ $r['x'] }}
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            Rp {{ number_format($r['sisa_prev'], 0, ',', '.') }}
                        </td>

                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            Rp {{ number_format($r['pot15'], 0, ',', '.') }}
                        </td>

                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            Rp {{ number_format($r['pot_end'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            {{ $r['sisa_x'] }}
                        </td>

                        <td class="px-4 py-2.5 text-right font-medium whitespace-nowrap">
                            Rp {{ number_format($r['sisa_now'], 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center text-gray-500" colspan="6">
                            Tidak ada data untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr class="font-semibold">
                    <td class="px-4 py-3 text-right">Total</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        Rp {{ number_format($this->totals['pokok'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ $this->totals['x'] ?? 0 }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        Rp {{ number_format($this->totals['sisa_prev'] ?? 0, 0, ',', '.') }}
                    </td>

                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        Rp {{ number_format($this->totals['pot15'] ?? 0, 0, ',', '.') }}
                    </td>

                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        Rp {{ number_format($this->totals['pot_end'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{ $this->totals['sisa_x'] ?? 0 }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        Rp {{ number_format($this->totals['sisa_now'] ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-filament::page>

