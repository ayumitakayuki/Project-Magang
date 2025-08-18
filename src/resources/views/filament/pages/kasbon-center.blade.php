<x-filament::page>
    <div class="space-y-6">
        {{-- CARD 1: Kasbon Loan --}}
        <a
            href="{{ \App\Filament\Admin\Resources\KasbonLoanResource::getUrl('index') }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Kasbon Loan"
        >
            {{-- background image --}}
            <img src="{{ asset('images/kasbon-loan.jpg') }}"
                onerror="this.style.display='none'"
                class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            {{-- content --}}
            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Kasbon Loan</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Buat & kelola pinjaman kasbon (pokok, tenor, cicilan).
                </p>

                <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                    Buka Kasbon Loan
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </a>

        {{-- CARD 2: Kasbon Payment --}}
        <a
            href="{{ \App\Filament\Admin\Resources\KasbonPaymentResource::getUrl('index') }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Kasbon Payment"
        >
            <img src="{{ asset('images/kasbon-payment.jpg') }}"
                onerror="this.style.display='none'"
                class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Kasbon Payment</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Catatan pembayaran / pemotongan kasbon.
                </p>

                <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                    Buka Kasbon Payment
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </a>

        {{-- CARD 3: Laporan Kasbon --}}
        <a
            href="{{ \App\Filament\Admin\Pages\LaporanKasbon::getUrl() }}"
            class="group block relative min-h-[220px] md:min-h-[320px]
                rounded-[22px] md:rounded-[28px] overflow-hidden
                ring-1 ring-gray-200/70 shadow-sm
                transition duration-300 hover:scale-[1.01] hover:shadow-2xl"
            aria-label="Buka Laporan Kasbon"
        >
            <img src="{{ asset('images/laporan-kasbon.jpg') }}"
                onerror="this.style.display='none'"
                class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/40 to-transparent"></div>

            <div class="relative px-10 md:px-7 pr-16 md:pr-12 py-4 md:py-6 flex flex-col justify-center h-full">
                <h2 class="mt-1 text-3xl md:text-4xl font-extrabold text-white">Laporan Kasbon</h2>
                <p class="mt-3 max-w-xl text-white/80">
                    Rekap sisa bulan lalu, potong 01–15 / 16–akhir, dan sisa bulan ini.
                </p>

                <span class="mt-6 inline-flex w-max items-center gap-2 rounded-full border-2 border-emerald-500 px-5 py-2 text-emerald-500 font-semibold transition group-hover:bg-emerald-500 group-hover:text-white">
                    Buka Laporan
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 11-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </a>
    </div>
</x-filament::page>
