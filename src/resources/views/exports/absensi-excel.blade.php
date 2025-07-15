<table style="margin-bottom: 1rem;">
    <tr>
        <td><strong>ID Karyawan:</strong></td>
        <td>{{ $karyawan->id_karyawan }}</td>
    </tr>
    <tr>
        <td><strong>Nama Karyawan:</strong></td>
        <td>{{ $karyawan->nama }}</td>
    </tr>
    <tr>
        <td><strong>Status:</strong></td>
        <td>{{ $karyawan->status }}</td>
    </tr>
    <tr>
        <td><strong>Lokasi:</strong></td>
        <td>{{ $karyawan->lokasi }}</td>
    </tr>
    @if ($karyawan->lokasi === 'proyek')
    <tr>
        <td><strong>Jenis Proyek:</strong></td>
        <td>{{ $karyawan->jenis_proyek }}</td>
    </tr>
    @endif
    <tr>
        <td><strong>Periode:</strong></td>
        <td>{{ \Carbon\Carbon::parse($start_date)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->format('d-m-Y') }}</td>
    </tr>
</table>

<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; width: 100%; font-size: 12px;">
    <thead style="background-color: #e1cc43;"> {{-- Abu terang --}}
        <tr>
            @foreach ($headings as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            @php
                $label = isset($row[0]) ? strtolower(trim($row[0])) : '';
                $rowStyle = '';

                if ($label === 'total') {
                    $rowStyle = 'background-color: #e1cc43;';
                } elseif ($label === 'grand total') {
                    $rowStyle = 'background-color: #b6e7a0;';
                }
            @endphp
            <tr style="{{ $rowStyle }}">
                @foreach ($row as $col)
                    <td>{{ $col }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
