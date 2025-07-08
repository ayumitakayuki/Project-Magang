@foreach ($gaji as $item)
    <table>
        <tr><td><strong>ID Karyawan</strong></td><td>{{ $item->id_karyawan }}</td></tr>
        <tr><td><strong>Nama</strong></td><td>{{ $item->nama }}</td></tr>
        <tr><td><strong>Status</strong></td><td>{{ $item->status }}</td></tr>
        <tr><td><strong>Lokasi</strong></td><td>{{ $item->lokasi }}</td></tr>
        <tr><td><strong>Jenis Proyek</strong></td><td>{{ $item->jenis_proyek }}</td></tr>
        <tr><td><strong>Periode</strong></td><td>{{ $item->periode_awal }} s/d {{ $item->periode_akhir }}</td></tr>
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
            @foreach ($item->details as $detail)
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

    <br><br>
@endforeach
