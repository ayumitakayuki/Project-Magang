{{-- resources/views/exports/rekap-gaji-nonpayroll-pdf.blade.php --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Gaji Non Payroll</title>
    <style>
        /* ==== COMPACT PORTRAIT ==== */
        @page { margin: 10px 12px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color:#111; }
        h2 { margin: 0 0 4px; font-size: 13px; }
        .meta { margin-bottom: 6px; font-size:9px; color:#444; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #777; padding: 2px 3px; line-height: 1.15; }
        th { background: #f2f2f2; text-align: left; word-break: break-word; }

        .num { text-align: right; }
        .nowrap { white-space: nowrap; }                  /* jaga 1 baris */
        .cut { overflow: hidden; text-overflow: ellipsis; }/* potong teks panjang */
        .totals td { font-weight: 700; background: #fafafa; }
        .small { font-size: 8px; color: #666; }
    </style>
</head>
<body>
@php
    use Carbon\Carbon;

    // sanitizer aman UTF-8 (anti "Malformed UTF-8 characters")
    if (!function_exists('__clean_utf8')) {
        function __clean_utf8($v) {
            if ($v === null) return '';
            $s = (string) $v;
            // buang kontrol ASCII
            $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s) ?? $s;
            // paksa ke UTF-8
            if (!mb_check_encoding($s, 'UTF-8')) {
                $tmp = @mb_convert_encoding($s, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                if ($tmp === false) $tmp = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
                $s = $tmp !== false ? $tmp : '';
            }
            // drop emoji/surrogates di luar BMP (Dompdf sering bermasalah)
            $s = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $s) ?? $s;
            return $s;
        }
    }
    if (!function_exists('__h')) {
        function __h($v) { return htmlspecialchars(__clean_utf8($v), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); }
    }

    /** @var \App\Models\RekapGajiNonPayroll $header */
    /** @var array<int,array<string,mixed>> $rows */
    /** @var array<string,float|int> $totals */

    $periode = ($header->period_start && $header->period_end)
        ? Carbon::parse($header->period_start)->format('d M Y').' – '.Carbon::parse($header->period_end)->format('d M Y')
        : ($labelPeriode ?? '—');

    $rangeLabel = match($header->range_type) {
        'first'  => '01–15',
        'second' => '16–Akhir',
        default  => 'Custom',
    };
@endphp

<h2>REKAP GAJI NON PAYROLL</h2>
<div class="meta">
    Periode: <strong>{!! __h($periode) !!}</strong>
    &nbsp; • &nbsp; Range: {!! __h($rangeLabel) !!}
    @if (!empty($header->period_label))
        &nbsp; • &nbsp; <span class="small">{!! __h($header->period_label) !!}</span>
    @endif
</div>

<table>
    <colgroup>
        <col style="width:18px">   {{-- No --}}
        <col style="width:40px">   {{-- No ID --}}
        <col style="width:72px">   {{-- Bagian --}}
        <col style="width:60px">   {{-- Lokasi --}}
        <col style="width:94px">   {{-- Proyek --}}
        <col style="width:auto">   {{-- Nama (fleksibel) --}}
        <col style="width:82px">   {{-- Pembulatan --}}
        <col style="width:72px">   {{-- Kasbon --}}
        <col style="width:82px">   {{-- Sisa Kasbon --}}
        <col style="width:92px">   {{-- Total Setelah Bon --}}
    </colgroup>

    <thead>
        <tr>
            <th class="nowrap">No</th>
            <th class="nowrap">No ID</th>
            <th class="nowrap">Bagian</th>
            <th class="nowrap">Lokasi</th>
            <th class="nowrap">Proyek</th>
            <th class="nowrap">Nama</th>
            <th class="num nowrap">Pembulatan</th>
            <th class="num nowrap">Kasbon</th>
            <th class="num nowrap">Sisa Kasbon</th>
            <th class="num nowrap">Total</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($rows as $i => $r)
            <tr>
                <td class="nowrap">{{ $i + 1 }}</td>
                <td class="nowrap">{!! __h($r['no_id'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['bagian'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['lokasi'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['project'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['nama'] ?? '') !!}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['pembulatan'] ?? 0), 0, ',', '.') }}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['kasbon'] ?? 0), 0, ',', '.') }}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['sisa_kasbon'] ?? 0), 0, ',', '.') }}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['total_setelah_bon'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" style="text-align:center; padding:10px;">Tidak ada data pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>

    @if (!empty($rows))
    <tfoot>
        <tr class="totals">
            <td colspan="6" class="num nowrap">TOTAL</td>
            <td class="num nowrap">Rp {{ number_format((float)($totals['pembulatan'] ?? 0), 0, ',', '.') }}</td>
            <td class="num nowrap">Rp {{ number_format((float)($totals['kasbon'] ?? 0), 0, ',', '.') }}</td>
            <td class="num nowrap">Rp {{ number_format((float)($totals['sisa_kasbon'] ?? 0), 0, ',', '.') }}</td>
            <td class="num nowrap">Rp {{ number_format((float)($totals['total_setelah_bon'] ?? 0), 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="small" style="margin-top:6px;">
    Dicetak: {{ now()->format('d M Y H:i') }}
</div>
</body>
</html>
