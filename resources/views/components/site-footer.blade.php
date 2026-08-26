<footer class="bg-navy-900 text-slate-300">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-10 lg:flex-row lg:justify-between">
            <div class="max-w-md space-y-4">
                <p class="text-xl font-semibold text-white">Tokio Marine Group</p>

                <p class="flex gap-3 text-xs leading-relaxed">
                    <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9.69 18.933A9.5 9.5 0 0 0 10 19a9.5 9.5 0 0 0 .31-.067c.17-.06.42-.157.72-.293.6-.27 1.4-.69 2.2-1.28 1.6-1.18 3.27-3.05 3.27-5.61a6.5 6.5 0 1 0-13 0c0 2.56 1.67 4.43 3.27 5.61.8.59 1.6 1.01 2.2 1.28.3.136.55.232.72.293ZM10 9.75a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Z" clip-rule="evenodd" />
                    </svg>
                    International Financial Center Tower 2, Jl. Jenderal Sudirman No.32A Kavling 22-23, RT.10/RW.1,
                    Kuningan, Karet, Kecamatan Setiabudi, Jakarta, Daerah Khusus Ibukota Jakarta 12920
                </p>

                <div class="flex flex-wrap gap-6 text-xs">
                    <a href="tel:+622112345678" class="flex items-center gap-2 transition hover:text-white">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.5 11.5 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15A13 13 0 0 1 2 5V3.5Z" clip-rule="evenodd" />
                        </svg>
                        (021) 1234 5678
                    </a>
                    <a href="mailto:marketing@tokiomarine.com" class="flex items-center gap-2 transition hover:text-white">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M3 4a2 2 0 0 0-2 2v.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 6.161V6a2 2 0 0 0-2-2H3Z" />
                            <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                        </svg>
                        marketing@tokiomarine.com
                    </a>
                </div>
            </div>

            <div class="flex flex-col gap-8 lg:items-end">
                <nav class="flex gap-8 text-sm">
                    <a href="{{ url('/') }}" class="transition hover:text-white">{{ __('nav.home') }}</a>
                    <a href="{{ url('/#about') }}" class="transition hover:text-white">{{ __('nav.about_us') }}</a>
                    <a href="{{ route('calculator') }}" class="transition hover:text-white">{{ __('nav.calculator') }}</a>
                </nav>

                <div class="flex gap-4">
                    @foreach (['Facebook', 'YouTube', 'Instagram'] as $channel)
                        <a href="#" aria-label="{{ $channel }}" class="grid size-8 place-items-center rounded-full border border-slate-600 text-xs transition hover:border-white hover:text-white">
                            {{ substr($channel, 0, 1) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="mt-10 border-t border-white/10 pt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} {{ __('footer.rights') }}
        </p>
    </div>
</footer>
