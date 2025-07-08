<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji Massal</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px; }
        th { background-color: #f2f2f2; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($gajis as $gaji)
        <img src="{{ public_path('images/logorku.jpg') }}" alt="Logo" style="width: 100%; margin-bottom: 20px;">

        <h2 style="margin-bottom: 10px;">SLIP GAJI - {{ $gaji->nama }}</h2>

        <table>
            <tr><td><strong>ID Karyawan</strong></td><td>{{ $gaji->id_karyawan }}</td></tr>
            <tr><td><strong>Nama</strong></td><td>{{ $gaji->nama }}</td></tr>
            <tr><td><strong>Status</strong></td><td>{{ $gaji->status }}</td></tr>
            <tr><td><strong>Lokasi</strong></td><td>{{ $gaji->lokasi }}</td></tr>
            <tr><td><strong>Jenis Proyek</strong></td><td>{{ $gaji->jenis_proyek }}</td></tr>
            <tr><td><strong>Periode</strong></td>
                <td>{{ \Carbon\Carbon::parse($gaji->periode_awal)->format('Y-m-d') }} s/d {{ \Carbon\Carbon::parse($gaji->periode_akhir)->format('Y-m-d') }}</td></tr>
        </table>

        <h4 style="margin-top: 20px;">Rincian Gaji</h4>
        <table>
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
                @php $labels = range('a', 'z'); $i = 0; @endphp
                @forelse ($gaji->details as $item)
                    <tr>
                        <td>
                            @if (!in_array(strtolower($item->kode), ['jml', 'grand']))
                                {{ $labels[$i++] ?? '?' }}
                            @else
                                {{ $item->kode }}
                            @endif
                        </td>
                        <td>{{ $item->keterangan }}</td>
                        <td align="center">
                            @if(is_numeric($item->masuk) && $item->masuk > 0)
                                {{ number_format($item->masuk, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td align="center">
                            @if(is_numeric($item->faktor) && $item->faktor > 0)
                                {{ fmod($item->faktor, 1) == 0 ? number_format($item->faktor, 0, ',', '.') : number_format($item->faktor, 1, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td align="right">Rp {{ number_format($item->nominal ?? 0, 0, ',', '.') }}</td>
                        <td align="right"><strong>Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="6" align="center">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
