<x-filament::page>
    @if (!empty($previewData))
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">Preview Data Hasil Import</h2>
            <table class="w-full table-auto border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2">Nama</th>
                        <th class="border p-2">Tanggal</th>
                        <th class="border p-2">Masuk Pagi</th>
                        <th class="border p-2">Keluar Siang</th>
                        <th class="border p-2">Masuk Siang</th>
                        <th class="border p-2">Pulang Kerja</th>
                        <th class="border p-2">Masuk Lembur</th>
                        <th class="border p-2">Pulang Lembur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($previewData as $row)
                        <tr>
                            <td class="border p-2">{{ $row['name'] }}</td>
                            <td class="border p-2">{{ $row['tanggal'] }}</td>
                            <td class="border p-2">{{ $row['masuk_pagi'] }}</td>
                            <td class="border p-2">{{ $row['keluar_siang'] }}</td>
                            <td class="border p-2">{{ $row['masuk_siang'] }}</td>
                            <td class="border p-2">{{ $row['pulang_kerja'] }}</td>
                            <td class="border p-2">{{ $row['masuk_lembur'] }}</td>
                            <td class="border p-2">{{ $row['pulang_lembur'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::card>
    @endif

    {{-- Ini tombol Import dan Create --}}
    {{ $this->form }}
    {{ $this->getSubmitFormAction() }}
</x-filament::page>
