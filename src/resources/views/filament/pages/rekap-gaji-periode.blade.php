<x-filament::page>
    <x-filament::section class="mb-6">
        {{ $this->form }}
    </x-filament::section>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-700">
                    <th class="px-4 py-3 font-semibold">No ID</th>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Bagian</th>
                    <th class="px-4 py-3 font-semibold">Project</th>
                    <th class="px-4 py-3 font-semibold text-right">Subtotal</th>
                    <th class="px-4 py-3 font-semibold text-right">Kasbon</th>
                    <th class="px-4 py-3 font-semibold text-right">Grand Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $r)
                    <tr class="odd:bg-white even:bg-gray-50/50">
                        <td class="px-4 py-2.5">{{ $r['no_id'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['nama'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['bagian'] ?? '' }}</td>
                        <td class="px-4 py-2.5">{{ $r['project'] ?? '' }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['subtotal'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">Rp {{ number_format($r['kasbon'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold">Rp {{ number_format($r['grand_total'] ?? 0, 0, ',', '.') }}</td>
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
