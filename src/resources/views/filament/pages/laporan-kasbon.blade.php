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

        <div class="flex items-end">
            <x-filament::button type="submit">Terapkan</x-filament::button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">Nama</th>
                    <th class="px-3 py-2 text-right">Kasbon (Pokok)</th>
                    <th class="px-3 py-2 text-right">Sisa Bulan Lalu</th>
                    <th class="px-3 py-2 text-right">Potong 01–15</th>
                    <th class="px-3 py-2 text-right">Potong 16–Akhir</th>
                    <th class="px-3 py-2 text-right">Sisa Bulan Ini</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($this->rows as $r)
                    <tr>
                        <td class="px-3 py-2">{{ $r['nama'] }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($r['kasbon'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($r['sisa_prev'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($r['pot15'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($r['pot_end'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right font-medium">Rp {{ number_format($r['sisa_now'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-6 text-gray-500" colspan="6">Tidak ada data untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50">
                <tr class="font-semibold">
                    <td class="px-3 py-2 text-right">Total</td>
                    <td class="px-3 py-2 text-right">Rp {{ number_format($this->totals['kasbon'] ?? 0, 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-right">Rp {{ number_format($this->totals['sisa_prev'] ?? 0, 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-right">Rp {{ number_format($this->totals['pot15'] ?? 0, 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-right">Rp {{ number_format($this->totals['pot_end'] ?? 0, 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-right">Rp {{ number_format($this->totals['sisa_now'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-filament::page>
