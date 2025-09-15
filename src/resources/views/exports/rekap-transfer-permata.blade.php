{{-- resources/views/exports/rekap-transfer-permata-pdf.blade.php --}}
<!doctype html>
<html>
<head>
    @php
    // Shim helper kalau belum ada
        if (! function_exists('__clean_utf8')) {
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
        if (! function_exists('__h')) {
            function __h($v) {
                return htmlspecialchars(__clean_utf8($v), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
            }
        }
    @endphp

    <meta charset="utf-8">
    <title>Rekap Transfer Permata</title>
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

        .totals td {
            font-weight: 700;
            background: #fafafa;
            font-size: 8px;       /* sudah kecil */
            padding: 2px 6px;     /* tambahin padding biar ada jarak */
        }
    </style>
</head>
<body>
    @php
    $maskOffsite = function ($v) {
        $s = trim((string) $v);
        if ($s === '') return '—';
        return preg_match('/off[\s-]?site/i', $s) ? '—' : $s;
    };
    $lokasi = $maskOffsite($lokasi ?? ($header->lokasi ?? null));
    $proyek = $maskOffsite($proyek ?? ($header->proyek ?? null));
@endphp

@php
    use Carbon\Carbon;

    // ... fungsi __clean_utf8 & __h tetap

    /** @var \App\Models\RekapTransferPermata $header */

    $bank    = $header->bank ?? 'PERMATA';
    $periode = ($header->period_start && $header->period_end)
        ? Carbon::parse($header->period_start)->format('d M Y').' – '.Carbon::parse($header->period_end)->format('d M Y')
        : ($labelPeriode ?? '—');

    // masking supaya kata "offsite" tidak tampil di header
    $maskOffsite = function ($v) {
        $s = trim(mb_strtolower((string) $v));
        return ($s === 'offsite' || $s === 'off site') ? '—' : ($v ?: '—');
    };

    $lokasi  = $maskOffsite($header->lokasi ?? '—');
    $proyek  = $maskOffsite($header->proyek ?? '—');

    // label pendek agar header kolom tetap 1 baris
    $ps = $header->period_start ? Carbon::parse($header->period_start) : null;
    $pe = $header->period_end   ? Carbon::parse($header->period_end)   : null;

    if ($ps && $pe && $ps->isSameMonth($pe) && $ps->day === 1 && $pe->day === 15) {
        $labelGaji15Short = $ps->format('M Y');
        $labelGaji16Short = $ps->copy()->subMonth()->format('M Y');
    } else {
        $labelGaji15Short = isset($labelGaji15) ? $labelGaji15 : ($ps ? $ps->format('M Y') : '—');
        $labelGaji16Short = isset($labelGaji16) ? $labelGaji16 : ($ps ? $ps->format('M Y') : '—');
    }
@endphp

<h2>{{ $bank }} — TRANSFER PERMATA</h2>
<div class="meta">
    Periode: <strong>{{ $periode }}</strong>
    {{-- &nbsp; • &nbsp; Lokasi: {{ $lokasi }}
    &nbsp; • &nbsp; Proyek: {{ $proyek }} --}}
    &nbsp; • &nbsp; Gaji 01–15: {{ $labelGaji15Short }} &nbsp;•&nbsp; Gaji 16–31: {{ $labelGaji16Short }}
</div>

<table>
    {{-- widths: No & No ID kecil, lainnya disesuaikan --}}
    <colgroup>
        <col style="width:18px">   {{-- No --}}
        {{-- <col style="width:40px">   
        <col style="width:72px">   
        <col style="width:60px">   
        <col style="width:94px">    --}}
        <col style="width:auto">   {{-- Nama (fleksibel) --}}
        <col style="width:82px">   {{-- Pembulatan --}}
        <col style="width:72px">   {{-- Kasbon --}}
        <col style="width:82px">   {{-- Sisa Kasbon --}}
        <col style="width:92px">   {{-- Gaji 16–31 --}}
        <col style="width:92px">   {{-- Gaji 01–15 --}}
    </colgroup>

    <thead>
        <tr>
            <th class="nowrap">No</th>
            {{-- <th class="nowrap">No ID</th>
            <th class="nowrap">Bagian</th>
            <th class="nowrap">Lokasi</th>
            <th class="nowrap">Proyek</th> --}}
            <th class="nowrap">Nama</th>
            <th class="num nowrap">Pembulatan</th>
            <th class="num nowrap">Kasbon</th>
            <th class="num nowrap">Sisa Kasbon</th>
            <th class="num" style="white-space:normal">Gaji 16–31<br>({{ $labelGaji16Short }})</th>
            <th class="num" style="white-space:normal">Gaji 01–15<br>({{ $labelGaji15Short }})</th>


        </tr>
    </thead>

    <tbody>
        @forelse ($rows as $i => $r)
            <tr>
                <td class="nowrap">{{ $i + 1 }}</td>
                {{-- <td class="nowrap">{!! __h($r['no_id'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['bagian'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['lokasi'] ?? '') !!}</td>
                <td class="cut">{!! __h($r['project'] ?? '') !!}</td> --}}
                <td class="cut">{!! __h($r['nama'] ?? '') !!}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['pembulatan'] ?? 0), 0, ',', '.') }}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['kasbon'] ?? 0), 0, ',', '.') }}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['sisa_kasbon'] ?? 0), 0, ',', '.') }}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['gaji_16_31'] ?? 0), 0, ',', '.') }}</td>
                <td class="num nowrap">Rp {{ number_format((float)($r['gaji_15_31'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center; padding:10px;">Tidak ada data pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>

    @if (!empty($rows))
    <tfoot>
        <tr class="totals">
            <td colspan="2" class="num" style="text-align:right; padding-right:10px;">
                TOTAL
            </td>
            <td class="num">Rp {{ number_format((float)($totals['pembulatan'] ?? 0), 0, ',', '.') }}</td>
            <td class="num">Rp {{ number_format((float)($totals['kasbon'] ?? 0), 0, ',', '.') }}</td>
            <td class="num">Rp {{ number_format((float)($totals['sisa_kasbon'] ?? 0), 0, ',', '.') }}</td>
            <td class="num">Rp {{ number_format((float)($totals['gaji_16_31'] ?? 0), 0, ',', '.') }}</td>
            <td class="num">Rp {{ number_format((float)($totals['gaji_15_31'] ?? 0), 0, ',', '.') }}</td>
        </tr>
    </tfoot>



    @endif
</table>

<div class="small" style="margin-top:6px;">
    Dicetak: {{ now()->format('d M Y H:i') }}
</div>
</body>
</html>
