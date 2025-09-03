<x-filament::page>
    <div class="space-y-6">

        {{-- 1) Histori Rekap Absensi --}}
        <a
            href="{{ \App\Filament\Admin\Pages\HistoriRekapAbsensi::getUrl() }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Histori Rekap Absensi"
        >
            <img src="{{ asset('images/kasbon-loan.jpg') }}"
                 onerror="this.style.display='none'"
                 class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Histori Rekap Absensi</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Daftar rekap absensi per periode yang pernah dibuat.
                </p>

                <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                    Buka Histori Rekap Absensi
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </a>

        {{-- 2) Histori Slip Gaji --}}
        <a
            href="{{ \App\Filament\Admin\Pages\HistoriSlipGaji::getUrl() }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Histori Slip Gaji"
        >
            <img src="{{ asset('images/kasbon-loan.jpg') }}"
                 onerror="this.style.display='none'"
                 class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Histori Slip Gaji</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Semua slip gaji yang pernah dihitung & disimpan.
                </p>

                <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                    Buka Histori Slip Gaji
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </a>

        {{-- 3) Histori Gaji Periode --}}
        <a
            href="{{ \App\Filament\Admin\Pages\HistoriRekapGajiPeriode::getUrl() }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Histori Gaji Periode"
        >
            <img src="{{ asset('images/kasbon-loan.jpg') }}"
                 onerror="this.style.display='none'"
                 class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Histori Gaji Periode</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Rekap gaji per periode (ringkasan payroll & non-payroll).
                </p>

                <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                    Buka Histori Gaji Periode
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </a>

        {{-- 4) Histori TRF Permata --}}
        <a
            href="{{ \App\Filament\Admin\Pages\HistoriTransferPermata::getUrl() }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Histori TRF Permata"
        >
            <img src="{{ asset('images/kasbon-loan.jpg') }}"
                 onerror="this.style.display='none'"
                 class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Histori TRF Permata</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Riwayat file transfer payroll (format Permata) yang pernah dibuat.
                </p>

                <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                    Buka Histori TRF Permata
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </a>

        {{-- 5) Histori Non Payroll --}}
        <a
            href="{{ \App\Filament\Admin\Pages\HistoriRekapGajiNonPayroll::getUrl() }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Histori Non Payroll"
        >
            <img src="{{ asset('images/kasbon-loan.jpg') }}"
                 onerror="this.style.display='none'"
                 class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Histori Non-Payroll</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Riwayat pembayaran non-payroll (kas/tunai) per periode.
                </p>

            <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                Buka Histori Non-Payroll
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </span>
            </div>
        </a>

    </div>
</x-filament::page>
