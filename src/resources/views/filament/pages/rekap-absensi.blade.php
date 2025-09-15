<x-filament::page>
    <x-filament::card class="bg-blue-100 rounded-xl p-6">
        {{-- FORM FILTER TERPADU --}}
        @if (session('edit_alert'))
            <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg mb-4">
                <strong class="font-semibold">Mode Edit:</strong>
                Anda sedang mengedit rekap absensi yang sudah tersimpan.
            </div>
        @endif
        <form id="rekap-form" method="GET" wire:submit.prevent="filter"
            class="mb-6 flex flex-wrap items-center gap-2">

        {{-- KARYAWAN (Tom Select) --}}
        <div id="karyawan_select_wrap"
            class="w-72"
            wire:ignore
            x-data="karyawanSelect()"
            x-init="$nextTick(() => init())">
        <select x-ref="sel" id="karyawan_select" placeholder="Select an option">
            <option value="">Select an option</option>
            @foreach ($all_karyawan ?? [] as $k)
            <option value="{{ $k->id_karyawan }}">{{ $k->nama }}</option>
            @endforeach
        </select>
        </div>

        <input type="hidden" id="selected_id"   name="selected_id"   wire:model="selected_id">
        <input type="hidden" id="selected_name" name="selected_name" wire:model="selected_name">

        <select name="status_karyawan" wire:model="status_karyawan"
        class="rounded-lg px-3 py-1 bg-blue-200 text-sm border border-blue-500">
        <option value="all">Show All</option>
        <option value="staff">Staff</option>
        <option value="harian tetap">Harian Tetap</option>
        <option value="harian lepas">Harian Lepas</option>
        </select>

        <select name="lokasi" wire:model="selected_lokasi" class="rounded-lg px-3 py-1 bg-blue-200 text-sm">
        <option value="">Lokasi</option>
        @foreach ($lokasi_options as $lokasi)
            <option value="{{ $lokasi }}">{{ ucfirst($lokasi) }}</option>
        @endforeach
        </select>

        <select name="proyek" wire:model="selected_proyek" class="rounded-lg px-3 py-1 bg-blue-200 text-sm">
        <option value="">Proyek</option>
        @foreach ($proyek_options as $proyek)
            <option value="{{ $proyek }}">{{ $proyek }}</option>
        @endforeach
        </select>

        <input type="text" id="start_date" name="start_date" wire:model="start_date"
        class="rounded-lg px-3 py-1 bg-blue-200 text-sm" placeholder="Start Date" />
        <span>-</span>
        <input type="text" id="end_date" name="end_date" wire:model="end_date"
        class="rounded-lg px-3 py-1 bg-blue-200 text-sm" placeholder="End Date" />
 
            {{-- Tombol Aksi --}}
            <x-filament::button type="submit">Filter</x-filament::button>
            <x-filament::button type="button" color="success" wire:click="simpan">
                Simpan ke Database
            </x-filament::button>
        </form>

        @if (!empty($data_harian))
        @php
            $nama_karyawan_unik = $data_harian->pluck('name')->unique();
        @endphp

        <div>
            @foreach ($nama_karyawan_unik as $nama_karyawan)
                @php
                    $data_karyawan = \App\Models\Karyawan::where('nama', $nama_karyawan)->first();
                    $data_absensi_karyawan = $data_harian->where('name', $nama_karyawan);
                @endphp

                <div class="flex flex-col lg:flex-row gap-4 items-start mb-6">
                {{-- BAGIAN KIRI: IDENTITAS --}}
                <div class="bg-white border border-gray-300 rounded-lg px-2 py-1 shadow-sm text-sm w-48 leading-tight">
                    <div class="space-y-1">
                        <div>
                            <span class="text-gray-500">ID Karyawan</span><br>
                            <span class="text-gray-800">{{ $data_karyawan->id_karyawan ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Nama Karyawan</span><br>
                            <span class="text-gray-800">{{ $nama_karyawan }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Periode</span><br>
                            <span class="text-gray-900 font-semibold">
                                {{ \Carbon\Carbon::parse($start_date)->format('d-m-Y') }}
                                s/d
                                {{ \Carbon\Carbon::parse($end_date)->format('d-m-Y') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Status</span><br>
                            <span class="text-gray-800">{{ $data_karyawan->status ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Lokasi</span><br>
                            <span class="text-gray-800">{{ $data_karyawan->lokasi ?? '-' }}</span>
                        </div>

                        @if ($data_karyawan?->lokasi === 'proyek')
                            <div>
                                <span class="text-gray-500">Jenis Proyek</span><br>
                                <span class="text-gray-800">{{ $data_karyawan->jenis_proyek ?? '-' }}</span>
                            </div>
                        @endif
                    </div>
                    {{-- TOMBOL EXPORT EXCEL --}}
                    <div class="mt-3">
                        <a href="{{ url('/export-absensi?start_date=' . $start_date . '&end_date=' . $end_date . '&id_karyawan=' . ($data_karyawan->id_karyawan ?? '')) }}"
                        target="_blank"
                        class="text-gray-500">
                            Download Excel
                            </a>
                        </div>
                    </div>
        {{-- BAGIAN KANAN: 2 TABEL (HORIZONTAL) --}}
        <div class="w-full lg:w-2/3 flex flex-row gap-2">
            {{-- TABEL DETAIL ABSENSI --}}
            <div class="flex-1 overflow-x-auto">
                    <table class="custom-table">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="tanggal">Tanggal</th>
                        <th class="border border-black px-2 py-1">Masuk Pagi</th>
                        <th class="border border-black px-2 py-1">Keluar Siang</th>
                        <th class="border border-black px-2 py-1">Masuk Siang</th>
                        <th class="border border-black px-2 py-1">Pulang Kerja</th>
                        <th class="border border-black px-2 py-1">Masuk Lembur</th>
                        <th class="border border-black px-2 py-1">Pulang Lembur</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalTidakMasuk = 0;
                        foreach ($data_absensi_karyawan as $absen) {
                            $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
                            $rekap_tanggal = $rekap['per_tanggal'][$nama_karyawan][$tanggal] ?? [];

                            if (($rekap_tanggal['tidak_masuk'] ?? '-') !== '-') {
                                // Ambil angka dari string '8 jam'
                                $jam = intval($rekap_tanggal['tidak_masuk']);
                                $totalTidakMasuk += $jam;
                            }
                        }
                    @endphp

                    @foreach ($data_absensi_karyawan as $absen)
                        <tr>
                            <td class="tanggal">{{ \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y') }}</td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->masuk_pagi ? \Carbon\Carbon::parse($absen->masuk_pagi)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->keluar_siang ? \Carbon\Carbon::parse($absen->keluar_siang)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->masuk_siang ? \Carbon\Carbon::parse($absen->masuk_siang)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->pulang_kerja ? \Carbon\Carbon::parse($absen->pulang_kerja)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->masuk_lembur ? \Carbon\Carbon::parse($absen->masuk_lembur)->format('H:i') : '-' }}
                            </td>
                            <td class="border border-black px-2 py-1">
                                {{ $absen->pulang_lembur ? \Carbon\Carbon::parse($absen->pulang_lembur)->format('H:i') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TABEL PERHITUNGAN JAM --}}
        <div class="overflow-x-auto">
            <table class="custom-table">
                <thead>
                    @php
                        $isHarianLepas = strtolower($data_karyawan->status ?? '') === 'harian lepas';
                        $jumlahHari = 0;

                        if ($isHarianLepas) {
                            $jumlahHari = app(\App\Services\AbsensiRekapService::class)
                                ->hitungJumlahHariHarianLepas($data_absensi_karyawan);
                            $jumlahHariPerTanggal = app(\App\Services\AbsensiRekapService::class)
                                ->hitungJumlahHariPerTanggal($data_absensi_karyawan);
                        }
                    @endphp
            
                    @if ($isHarianLepas)
                        {{-- HEADER 2 BARIS: Khusus Harian Lepas --}}
                        <tr class="bg-gray-100 text-xs text-center">
                            <th rowspan="2" class="border border-black px-2 py-1">Tanggal</th>
                            <th rowspan="2" class="border border-black px-2 py-1">SJ</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Sabtu</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Minggu</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Hari<br>Besar</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Tidak<br>Masuk</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Sisa<br>Jam</th>
                            <th rowspan="2" class="border border-black px-2 py-1">Jumlah<br>Hari</th>
                        </tr>
                        <tr class="bg-gray-100 text-xs text-center">
                            {{-- Kosong karena semua kolom pakai rowspan --}}
                        </tr>
                    @else
                        {{-- HEADER 1 BARIS: Untuk Staff dan Harian Tetap --}}
                        <tr class="bg-gray-100 text-xs text-center">
                            <th class="border border-black px-2 py-1">Tanggal</th>
                            <th class="border border-black px-2 py-1">SJ</th>
                            <th class="border border-black px-2 py-1">Sabtu</th>
                            <th class="border border-black px-2 py-1">Minggu</th>
                            <th class="border border-black px-2 py-1">Hari Besar</th>
                            <th class="border border-black px-2 py-1">Tidak Masuk</th>
                            <th class="border border-black px-2 py-1">Sisa Jam</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @foreach ($data_absensi_karyawan as $absen)
                        @php
                            $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
                            $rekap_tanggal = $rekap['per_tanggal'][$nama_karyawan][$tanggal] ?? [
                                'sj' => '-',
                                'sabtu' => '-',
                                'minggu' => '-',
                                'hari_besar' => '-',
                                'tidak_masuk' => '-',
                            ];
                        @endphp
                        <tr>
                            <td class="tanggal">{{ \Carbon\Carbon::parse($absen->tanggal)->format('d-m-Y') }}</td>
                            <td class="border border-black px-2 py-1">
                                {{ ($rekap_tanggal['sj'] ?? '-') }}
                            </td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['sabtu'] }}</td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['minggu'] }}</td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['hari_besar'] }}</td>
                            <td class="border border-black px-2 py-1">{{ $rekap_tanggal['tidak_masuk'] }}</td>
                            <td class="border border-black px-2 py-1">
                                {{ ($rekap_tanggal['sisa_jam'] ?? 0) > 0 ? $rekap_tanggal['sisa_jam'] . ' jam' : '-' }}
                            </td>

                            @if ($isHarianLepas)
                                @php
                                    $tanggal = \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d');
                                    $rekapHari = $jumlahHariPerTanggal[$tanggal] ?? ['jumlah_hari' => null, 'sisa_jam' => null];
                                @endphp
                                {{-- <td class="border border-black px-2 py-1">
                                    {{ ($rekapHari['sisa_jam'] ?? 0) > 0 && ($rekapHari['jumlah_hari'] ?? 0) > 0
                                        ? $rekapHari['sisa_jam'] . ' jam'
                                        : '-' }}
                                </td> --}}
                                <td class="border border-black px-2 py-1">
                                    {{ ($rekapHari['jumlah_hari'] ?? 0) > 0
                                        ? $rekapHari['jumlah_hari'] . ' hari'
                                        : '-' }}
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    {{-- TOTAL per kolom --}}
                    <tr class="bg-gray-200 font-semibold">
                        <td class="border border-black px-2 py-1 text-right">Total</td>
                        <td class="border border-black px-2 py-1">
                            {{ ($rekap['per_user'][$nama_karyawan]['sj'] ?? 0) . ' jam' }}
                        </td>
                        <td class="border border-black px-2 py-1">{{ ($rekap['per_user'][$nama_karyawan]['sabtu'] ?? 0) . ' jam' }}</td>
                        <td class="border border-black px-2 py-1">{{ ($rekap['per_user'][$nama_karyawan]['minggu'] ?? 0) . ' jam' }}</td>
                        <td class="border border-black px-2 py-1">{{ ($rekap['per_user'][$nama_karyawan]['hari_besar'] ?? 0) . ' jam' }}</td>
                        <td class="border border-black px-2 py-1 font-semibold">{{ $totalTidakMasuk }} jam</td>
                        <td class="border border-black px-2 py-1 font-semibold">
                            {{ ($rekap['per_user'][$nama_karyawan]['sisa_jam'] ?? 0) > 0
                                ? $rekap['per_user'][$nama_karyawan]['sisa_jam'] . ' jam'
                                : '-' }}
                        </td>
                        @if ($isHarianLepas)
                            @php
                                $totalSisaJam = collect($jumlahHariPerTanggal)
                                    ->filter(fn($item) => ($item['jumlah_hari'] ?? 0) > 0)
                                    ->sum('sisa_jam');
                            @endphp
                            {{-- <td class="border border-black px-2 py-1 font-semibold">
                                {{ $totalSisaJam > 0 ? $totalSisaJam . ' jam' : '-' }}
                            </td> --}}
                            <td class="border border-black px-2 py-1 font-semibold">
                                {{ $jumlahHari }} hari
                            </td>
                        @endif
                    </tr>

                    {{-- @php
                        $grandTotalSJ = $rekap['sj'] ?? 0;
                        $grandTotalSabtu = $rekap['sabtu'] ?? 0;
                        $grandTotalMinggu = $rekap['minggu'] ?? 0;
                        $grandTotalHariBesar = $rekap['hari_besar'] ?? 0;
                        $grandTotalTidakMasuk = $rekap['tidak_masuk'] ?? 0;

                        $grandTotalSisaJam = $rekap['per_user'][$nama_karyawan]['sisa_jam'] ?? 0;
                        $grandTotalJam = (
                            $grandTotalSJ + $grandTotalSabtu + $grandTotalMinggu + $grandTotalHariBesar
                        ) - $grandTotalTidakMasuk - $grandTotalSisaJam;

                        if ($grandTotalJam < 0) {
                            $grandTotalJam = 0;
                        }
                    @endphp --}}
                    {{-- <tr class="bg-green-200 font-semibold">
                        <td class="border border-black px-2 py-1 text-right">Grand Total</td>
                        <td colspan="6" class="border border-black px-2 py-1 text-center">
                            {{ $grandTotalJam }} jam
                        </td>
                        @if ($isHarianLepas)
                            <td class="border border-black px-2 py-1 text-center">
                                {{ $jumlahHari > 0 ? $jumlahHari . ' hari' : '-' }}
                            </td>
                        @endif
                    </tr> --}}
                </tbody>
            </table>
            {{-- @if ($edit_mode)
                <div class="mt-4 flex items-center gap-4">
                    <x-filament::button wire:click="simpanEditRekap">
                        Simpan ke Database
                    </x-filament::button>
                    <a href="{{ route('filament.admin.pages.histori-rekap-absensi') }}" class="text-gray-500 underline">
                        Batal Edit
                    </a>
                </div>
            @endif --}}
        </div>
    </div>
</div>
            @endforeach
        </div>
    @endif
    </x-filament::card>
</x-filament::page>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/airbnb.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            fetch("https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/holidays.json")
                .then(response => response.json())
                .then(libur => {
                    const tanggalMerah = Object.keys(libur);
                    const commonOptions = {
                        dateFormat: "Y-m-d",
                        onDayCreate: function (dObj, dStr, fp, dayElem) {
                            const dateStr = dayElem.dateObj.toLocaleDateString('sv-SE');
                            if (tanggalMerah.includes(dateStr)) {
                                dayElem.style.backgroundColor = "#f87171";
                                dayElem.style.color = "white";
                                dayElem.title = libur[dateStr];
                            }
                        },
                        // >>> tambahan penting untuk sinkron ke Livewire
                        onChange: function (selectedDates, dateStr, instance) {
                            instance.input.value = dateStr;
                            // trigger ke Livewire
                            instance.input.dispatchEvent(new Event('input', { bubbles: true }));
                            instance.input.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    };

                    flatpickr("#start_date", commonOptions);
                    flatpickr("#end_date", commonOptions);
                });
        });
    </script>
@endpush

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
  // Inisialisasi yang kuat: coba terus sampai elemen & lib siap
  function initTomSelect() {
    const el = document.getElementById('karyawan_select');
    if (!el || el.tomselect || !window.TomSelect) return;

    const ts = new TomSelect(el, {
      create:false,
      maxOptions:500,
      placeholder:'Select an option',
      plugins:['dropdown_input','clear_button'],
      allowEmptyOption:true,
      searchField:['text'],
      copyClassesToDropdown:false,
    });

    // tandai siap -> CSS menyembunyikan select asli
    el.setAttribute('data-ts-ready', '1');

    // Sinkron ke Livewire via hidden inputs (tanpa Alpine)
    const hiddenId   = document.getElementById('selected_id');
    const hiddenName = document.getElementById('selected_name');
    const form       = document.getElementById('rekap-form');

    el.addEventListener('change', () => {
      const id = el.value || '';
      if (hiddenId)   { hiddenId.value = id;   hiddenId.dispatchEvent(new Event('input', {bubbles:true})); }
      if (hiddenName) { hiddenName.value = ''; hiddenName.dispatchEvent(new Event('input', {bubbles:true})); }

      // Update query string tanpa reload
      const params = new URLSearchParams(new FormData(form));
      if (id) { params.set('selected_id', id); params.delete('selected_name'); }
      else    { params.delete('selected_id');  params.delete('selected_name'); }
      history.replaceState({}, '', `${location.pathname}?${params.toString()}`);

      // Jalankan filter (wire:submit.prevent akan menangkap)
      form.dispatchEvent(new Event('submit', { bubbles:true }));
    });

    ts.on('clear', () => {
      if (hiddenId)   { hiddenId.value = ''; hiddenId.dispatchEvent(new Event('input', {bubbles:true})); }
      if (hiddenName) { hiddenName.value = ''; hiddenName.dispatchEvent(new Event('input', {bubbles:true})); }

      const params = new URLSearchParams(new FormData(form));
      params.delete('selected_id'); params.delete('selected_name');
      history.replaceState({}, '', `${location.pathname}?${params.toString()}`);

      form.dispatchEvent(new Event('submit', { bubbles:true }));
    });

    // Preset dari hidden jika ada
    const preset = hiddenId?.value;
    if (preset) ts.setValue(preset, true);
  }

  // Coba beberapa kali sampai siap (hindari “harus refresh”)
  let tries = 0;
  const boot = () => {
    initTomSelect();
    if (!document.getElementById('karyawan_select')?.tomselect && tries++ < 40) {
      setTimeout(boot, 50); // total ±2s retry window
    }
  };
  document.addEventListener('DOMContentLoaded', boot);
  window.addEventListener('livewire:navigated', boot);     // untuk Filament Navigate / SPA
  window.addEventListener('filament:page-rendered', boot); // untuk render ulang Filament
})();
</script>
@endpush

@push('styles')
  {{-- Tom Select CSS (cukup 1x di halaman ini) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
  <style>
    #karyawan_select[data-ts-ready="1"]{
        position:absolute !important; left:-9999px; width:1px; height:1px;
        overflow:hidden; opacity:0; pointer-events:none;
    }
    .custom-table {
      border-collapse: collapse;
      width: auto;
      margin: 0 auto;
      background-color: #ffffff;
      font-size: 0.75rem;
    }
    .custom-table th,
    .custom-table td {
      border: 1px solid black;
      padding: 6px 10px;
      text-align: center;
      vertical-align: middle;
      white-space: normal;
      word-break: break-word;
      font-size: 0.75rem;
    }
    .custom-table th.tanggal,
    .custom-table td.tanggal { width: 110px; }
    .custom-table th { background-color: #f3f4f6; font-weight: 600; }
    .custom-table tr:nth-child(even) { background-color: #f9fafb; }
    .custom-table tr:hover { background-color: #f1f5f9; }
    @media print {
      .custom-table { font-size: 0.8rem; background: #fff; }
      .custom-table tr:nth-child(even),
      .custom-table tr:hover { background: #fff; }
    }

    #karyawan_select_wrap .ts-wrapper.single .ts-control{
    background:#fff; border:1px solid #111827; border-radius:.5rem;
    padding:.25rem .75rem; font-size:.875rem; line-height:1.25rem;
    min-height:2rem; color:#111827; box-shadow:none; position:relative; padding-right:2rem;
  }
  #karyawan_select_wrap .ts-wrapper.single .ts-control:hover{ border-color:#111827; }
  #karyawan_select_wrap .ts-wrapper .clear-button{
    position:absolute !important; right:.5rem; top:50%; transform:translateY(-50%);
    opacity:.7; z-index:2;
  }
  #karyawan_select_wrap .ts-wrapper .clear-button:hover{ opacity:1; }
  #karyawan_select_wrap .ts-wrapper .ts-control > input{ padding-right:2rem; }
  #karyawan_select_wrap .ts-wrapper.single .ts-control .item{ padding-right:1.5rem; background:transparent; }
  #karyawan_select_wrap .ts-wrapper.single .ts-control::after{ display:none; }
</style>
@endpush