@php
    use Carbon\Carbon;

    /** @var \App\Models\RekapGajiPeriod $rekap */
    /** @var array $rows  (hasil HoRekapService::rekapPeriodeLaporan) */

    $start  = $rekap->start_date instanceof Carbon ? $rekap->start_date : Carbon::parse($rekap->start_date);
    $end    = $rekap->end_date   instanceof Carbon ? $rekap->end_date   : Carbon::parse($rekap->end_date);
    $rupiah = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

    $rows   = collect($rows ?? []);
    $hasNon = $rows->contains(fn($r) =>
        ($r['keterangan'] ?? '') === 'TOTAL CASH' ||
        ( ($r['keterangan'] ?? '') === 'Gaji Harian' && (($r['jumlah'] ?? 0) > 0) )
    );
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Rekap Gaji Periode</title>
    <style>
        /* ====== basic ====== */
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 26px 28px; font-size: 11.5px; color: #111; }
        h1 { margin: 0 0 8px; text-align: center; font-size: 16.5px; letter-spacing: .3px; }
        .meta { width: 100%; margin-bottom: 12px; }
        .meta td { vertical-align: top; font-size: 11px; color: #333; }
        .meta .left { text-align: left; }
        .meta .right { text-align: right; }

        /* ====== summary cards ====== */
        .cards { width: 100%; border-collapse: separate; border-spacing: 10px 10px; margin: 6px 0 12px; }
        .card {
            border: 0.8px solid #CFCFCF; padding: 10px 12px; border-radius: 4px;
            height: 54px;
        }
        .card .label { font-size: 10.5px; color: #666; margin-bottom: 4px; }
        .card .value { font-weight: 700; font-size: 13px; }

        /* ====== table ====== */
        table.grid { width: 100%; border-collapse: collapse; }
        col.no   { width: 44px; }
        col.ket  { width: auto; }
        col.lok  { width: 120px; }
        col.prj  { width: 170px; }
        col.jml  { width: 130px; }
        col.org  { width: 120px; }
        col.trf  { width: 86px; }

        th, td { border: 0.7px solid #DADADA; padding: 7px 8px; vertical-align: middle; }
        th { background: #F4F6F8; color: #333; font-weight: 700; }
        td.right { text-align: right; }
        td.center { text-align: center; }

        /* baris total */
        .row-total td {
            background: #FAFAFA;
            font-weight: 700;
            border-top-color: #BFBFBF;
            border-bottom-color: #BFBFBF;
        }

        /* garis penutup terakhir lebih tegas */
        .last td { border-bottom-color: #AFAFAF; }

        /* kecilkan spasi sebelum tabel */
        .mt-8 { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>REKAP GAJI PERIODE</h1>

    <table class="meta">
        <tr>
            <td class="left">Periode: <strong>{{ $start->format('d M Y') }} — {{ $end->format('d M Y') }}</strong></td>
            <td class="right">
                Dibuat oleh: <strong>{{ optional($rekap->user)->name ?? '—' }}</strong>
            </td>
        </tr>
    </table>

    {{-- Summary 2×2 --}}
    <table class="cards">
        <tr>
            <td class="card">
                <div class="label">Total Payroll</div>
                <div class="value">{{ $rupiah($rekap->total_payroll ?? 0) }}</div>
            </td>
            <td class="card">
                <div class="label">Total Non Payroll</div>
                <div class="value">{{ $hasNon ? $rupiah($rekap->total_non_payroll ?? 0) : $rupiah(0) }}</div>
            </td>
        </tr>
        <tr>
            <td class="card">
                <div class="label">Grand Total</div>
                <div class="value">{{ $rupiah($rekap->total_grand ?? 0) }}</div>
            </td>
            <td class="card">
                <div class="label">Total Karyawan</div>
                <div class="value">{{ (int) ($rekap->count_grand ?? 0) }}</div>
            </td>
        </tr>
    </table>

    {{-- Tabel utama --}}
    <table class="grid mt-8">
        <colgroup>
            <col class="no"><col class="ket"><col class="lok"><col class="prj">
            <col class="jml"><col class="org"><col class="trf">
        </colgroup>
        <thead>
            <tr>
                <th class="center">No ID</th>
                <th>Keterangan</th>
                <th>Lokasi</th>
                <th>Proyek</th>
                <th class="right">Jumlah</th>
                <th class="right">Jumlah Karyawan</th>
                <th>TRF</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $r)
                @php
                    $isTotal = in_array($r['keterangan'] ?? '', ['TOTAL PAYROLL','TOTAL CASH','Grand Total'], true);
                    $classes = trim(($isTotal ? 'row-total ' : '') . ($i === count($rows)-1 ? 'last' : ''));
                @endphp
                <tr class="{{ $classes }}">
                    <td class="center">{{ $r['no_id'] ?? '' }}</td>
                    <td>{{ $r['keterangan'] ?? '' }}</td>
                    <td>{{ $r['lokasi'] ?? '' }}</td>
                    <td>{{ ($r['proyek'] ?? '') ?: 'Tanpa Proyek' }}</td>
                    <td class="right">{{ $rupiah($r['jumlah'] ?? 0) }}</td>
                    <td class="right">{{ (int) ($r['jumlah_karyawan'] ?? 0) }}</td>
                    <td>{{ $r['trf'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
