<table>
    <tr><td><strong>ID Karyawan</strong></td><td>{{ $gaji->id_karyawan }}</td></tr>
    <tr><td><strong>Nama</strong></td><td>{{ $gaji->nama }}</td></tr>
    <tr><td><strong>Status</strong></td><td>{{ $gaji->status }}</td></tr>
    <tr><td><strong>Lokasi</strong></td><td>{{ $gaji->lokasi }}</td></tr>
    <tr><td><strong>Jenis Proyek</strong></td><td>{{ $gaji->jenis_proyek }}</td></tr>
    <tr>
        <td><strong>Periode</strong></td>
        <td>
            {{ \Carbon\Carbon::parse($gaji->periode_awal)->format('Y-m-d') }}
            s/d
            {{ \Carbon\Carbon::parse($gaji->periode_akhir)->format('Y-m-d') }}
        </td>
    </tr>
</table>

<br>

<table border="1">
    <thead>
        <tr>
            <th>Kode</th>
            <th>Keterangan</th>
            <th>Masuk</th>
            <th>Faktor</th>
            <th>Nominal</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($gaji->details as $detail)
            <tr>
                <td>{{ $detail->kode }}</td>
                <td>{{ $detail->keterangan }}</td>
                <td>{{ $detail->masuk }}</td>
                <td>{{ $detail->faktor }}</td>
                <td>{{ $detail->nominal }}</td>
                <td>{{ $detail->total }}</td>
            </tr>
        @endforeach
    </tbody>
</table>