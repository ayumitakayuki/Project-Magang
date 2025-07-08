@foreach ($gajis as $gaji)
    <table>
        <tr><td><strong>ID Karyawan</strong></td><td>{{ $gaji->id_karyawan }}</td></tr>
        <tr><td><strong>Nama</strong></td><td>{{ $gaji->nama }}</td></tr>
        <tr><td><strong>Status</strong></td><td>{{ $gaji->status }}</td></tr>
        <tr><td><strong>Lokasi</strong></td><td>{{ $gaji->lokasi }}</td></tr>
        <tr><td><strong>Jenis Proyek</strong></td><td>{{ $gaji->jenis_proyek }}</td></tr>
        <tr><td><strong>Periode</strong></td><td>{{ $gaji->periode_awal }} s/d {{ $gaji->periode_akhir }}</td></tr>
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
            @foreach ($gaji->details as $item)
                <tr>
                    <td>{{ $item->kode }}</td>
                    <td>{{ $item->keterangan }}</td>
                    <td>{{ $item->masuk }}</td>
                    <td>{{ $item->faktor }}</td>
                    <td>{{ $item->nominal }}</td>
                    <td>{{ $item->total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br><br>
@endforeach
