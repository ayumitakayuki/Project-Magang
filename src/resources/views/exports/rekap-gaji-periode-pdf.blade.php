{{-- resources/views/exports/rekap-gaji-periode-pdf.blade.php --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Gaji Periode</title>
    <style>
        /* ==== COMPACT PORTRAIT ala Permata ==== */
        @page { margin: 10px 12px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color:#111; }
        h2 { margin: 0 0 4px; font-size: 13px; }
        .meta { margin-bottom: 6px; font-size:9px; color:#444; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #777; padding: 2px 3px; line-height: 1.15; }
        th { background: #f2f2f2; text-align: left; word-break: break-word; }

        .num { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }                   /* 1 baris */
        .cut { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .totals td { font-weight: 700; background: #fafafa; }
        .small { font-size: 8px; color: #666; }
    </style>
</head>
<body>
@php
    use Carbon\Carbon;

    /** @var \App\Models\RekapGajiPeriod $rekap */
    /** @var array<int,array<string,mixed>> $rows */

    // sanitizer (sama dengan template Permata)
    if (!function_exists('__clean_utf8')) {
        function __clean_utf8($v) {
            if ($v === null) return '';
            $s = (string) $v;
            $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s) ?? $s;
            if (!mb_check_encoding($s, 'UTF-8')) {
                $tmp = @mb_convert_encoding($s, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                if ($tmp === false) $tmp = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
                $s = $tmp !== false ? $tmp : '';
            }
            $s = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $s) ?? $s; // buang emoji
            return $s;
        }
    }
    if (!function_exists('__h')) {
        function __h($v) { return htmlspecialchars(__clean_utf8($v), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); }
    }

    $start  = $rekap->start_date instanceof Carbon ? $rekap->start_date : Carbon::parse($rekap->start_date);
    $end    = $rekap->end_date   instanceof Carbon ? $rekap->end_date   : Carbon::parse($rekap->end_date);
    $periode = $start->format('d M Y').' – '.$end->format('d M Y');
    $rupiah = fn($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

    $rows = collect($rows ?? []);
    $isTotalRow = fn($r) => in_array($r['keterangan'] ?? '', ['TOTAL PAYROLL','TOTAL CASH','Grand Total'], true);

    // total sederhana untuk footer (hanya baris non-total)
    $sumJumlah = $rows->filter(fn($r) => !$isTotalRow($r))->sum(fn($r) => (float)($r['jumlah'] ?? 0));
    $sumKaryawan = $rows->filter(fn($r) => !$isTotalRow($r))->sum(fn($r) => (int)($r['jumlah_karyawan'] ?? 0));
@endphp

<h2>REKAP GAJI PERIODE</h2>
<div class="meta">
    Periode: <strong>{!! __h($periode) !!}</strong>
</div>

<table>
    <colgroup>
        <col style="width:18px">   {{-- No --}}
        <col style="width:42px">   {{-- No ID --}}
        <col style="width:auto">   {{-- Keterangan --}}
        <col style="width:72px">   {{-- Lokasi --}}
        <col style="width:92px">   {{-- Proyek --}}
        <col style="width:92px">   {{-- Jumlah --}}
        <col style="width:84px">   {{-- Jumlah Karyawan --}}
        <col style="width:50px">   {{-- TRF --}}
    </colgroup>

    <thead>
        <tr>
            <th class="nowrap">No</th>
            <th class="nowrap">No ID</th>
            <th class="nowrap">Keterangan</th>
            <th class="nowrap">Lokasi</th>
            <th class="nowrap">Proyek</th>
            <th class="num nowrap">Jumlah</th>
            <th class="num nowrap">Jml Karyawan</th>
            <th class="nowrap">TRF</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($rows as $i => $r)
            @php $totalRow = $isTotalRow($r); @endphp
            <tr class="{{ $totalRow ? 'totals' : '' }}">
                <td class="nowrap center">{{ $totalRow ? '' : $i + 1 }}</td>
                <td class="nowrap">{!! __h($r['no_id'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['keterangan'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['lokasi'] ?? '') !!}</td>
                <td class="cut">{!! __h(($r['proyek'] ?? '') ?: 'Tanpa Proyek') !!}</td>
                <td class="num nowrap">{{ $rupiah($r['jumlah'] ?? 0) }}</td>
                <td class="num nowrap">{{ (int) ($r['jumlah_karyawan'] ?? 0) }}</td>
                <td class="nowrap">{!! __h($r['trf'] ?? '') !!}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="text-align:center; padding:10px;">Tidak ada data pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>

    @if ($rows->isNotEmpty())
    <tfoot>
        <tr class="totals">
            <td colspan="5" class="num nowrap">TOTAL</td>
            <td class="num nowrap">{{ $rupiah($sumJumlah) }}</td>
            <td class="num nowrap">{{ (int) $sumKaryawan }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="small" style="margin-top:6px;">
    Dicetak: {{ now()->format('d M Y H:i') }}
</div>
</body>
</html>
