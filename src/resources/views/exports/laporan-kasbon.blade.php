@php
    use Carbon\Carbon;

    $bulanLabel = Carbon::parse(($bulan ?? now()->format('Y-m')) . '-01')->translatedFormat('F Y');
    function rupiah($n){ return 'Rp ' . number_format((float)($n ?? 0), 0, ',', '.'); }

    // === Ambil logo & jadikan base64 ===
    $logoData = null;
    $logoPath = public_path('images/logorku.jpg');   // pastikan nama file benar
    if (is_file($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoData = 'data:image/'.$type.';base64,'.base64_encode(file_get_contents($logoPath));
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Kasbon - {{ $bulanLabel }}</title>
<style>
    @page { margin: 24px 24px 40px 24px; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
    .header { text-align: center; margin-bottom: 12px; }
    .header .logo { height: 56px; margin-bottom: 6px; }
    .header .title { font-size: 20px; font-weight: 700; letter-spacing: .4px; }
    .header .subtitle { font-size: 12px; color:#555; margin-top: 2px; }
    .meta { margin: 6px 0 12px; text-align: center; color:#666; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; }
    th { background: #f2f2f2; text-align: left; }
    td.num, th.num { text-align: right; white-space: nowrap; }
    tfoot td { font-weight: 600; background: #fafafa; }
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    tr { page-break-inside: avoid; }
</style>
</head>
<body>

<div class="header">
    @if($logoData)
        <img class="logo" src="{{ $logoData }}" alt="Logo">
    @endif
    <div class="title">LAPORAN KASBON</div>
    <div class="subtitle">{{ $bulanLabel }}</div>
</div>

<div class="meta">
    Dibuat: {{ now()->format('d/m/Y H:i') }}
    @if(!empty($q)) &nbsp;|&nbsp; Karyawan: <strong>{{ $q }}</strong> @endif
</div>

<!-- tabel kamu lanjut seperti sebelumnya -->
    <table>
        <colgroup>
            <col style="width:26%">
            <col style="width:12%">
            <col style="width:8%">
            <col style="width:14%">
            <col style="width:14%">
            <col style="width:14%">
            <col style="width:8%">
            <col style="width:14%">
        </colgroup>
        <thead>
            <tr>
                <th>Nama</th>
                <th class="num">Pokok</th>
                <th class="num">X</th>
                <th class="num">Sisa Bulan Lalu</th>
                <th class="num">Potong 01–15</th>
                <th class="num">Potong 16–Akhir</th>
                <th class="num">Sisa X</th>
                <th class="num">Sisa Bulan Ini</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['nama'] }}</td>
                    <td class="num">{{ rupiah($r['pokok'] ?? 0) }}</td>
                    <td class="num">{{ (int)($r['x'] ?? 0) }}</td>
                    <td class="num">{{ rupiah($r['sisa_prev'] ?? 0) }}</td>
                    <td class="num">{{ rupiah($r['pot15'] ?? 0) }}</td>
                    <td class="num">{{ rupiah($r['pot_end'] ?? 0) }}</td>
                    <td class="num">{{ (int)($r['sisa_x'] ?? 0) }}</td>
                    <td class="num">{{ rupiah($r['sisa_now'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:16px; color:#777;">
                        Tidak ada data untuk filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td style="text-align:right;">Total</td>
                <td class="num">{{ rupiah($totals['pokok'] ?? 0) }}</td>
                <td class="num">{{ (int)($totals['x'] ?? 0) }}</td>
                <td class="num">{{ rupiah($totals['sisa_prev'] ?? 0) }}</td>
                <td class="num">{{ rupiah($totals['pot15'] ?? 0) }}</td>
                <td class="num">{{ rupiah($totals['pot_end'] ?? 0) }}</td>
                <td class="num">{{ (int)($totals['sisa_x'] ?? 0) }}</td>
                <td class="num">{{ rupiah($totals['sisa_now'] ?? 0) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
