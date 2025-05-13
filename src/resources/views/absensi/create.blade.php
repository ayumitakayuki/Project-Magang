{{-- <x-filament::page>
    @if ($previewData)
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">Preview Data Hasil Import</h2>
            <table class="w-full table-auto border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
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
                            <td class="border p-2">{{ $row[0] }}</td>
                            <td class="border p-2">{{ $row[1] }}</td>
                            <td class="border p-2">{{ $row[2] }}</td>
                            <td class="border p-2">{{ $row[3] }}</td>
                            <td class="border p-2">{{ $row[4] }}</td>
                            <td class="border p-2">{{ $row[5] }}</td>
                            <td class="border p-2">{{ $row[6] }}</td>
                            <td class="border p-2">{{ $row[7] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::card>
    @endif

    {{ $this->form }}
    {{ $this->getSubmitFormAction() }}
</x-filament::page> --}}
