{{--
    Navigasi atas: logo, menu, tombol Login, dan pemilih bahasa.
    `active` menerima salah satu dari: home, about, calculator.
--}}
@props(['active' => null])

@php
    $links = [
        'home' => ['label' => __('nav.home'), 'url' => url('/')],
        'about' => ['label' => __('nav.about'), 'url' => url('/#about')],
        'calculator' => ['label' => __('nav.calculator'), 'url' => route('calculator')],
    ];
    $locale = app()->getLocale();
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2">
            <span class="grid size-9 place-items-center rounded-full bg-brand-500 text-sm font-bold text-white">TM</span>
            <span class="hidden text-xs leading-tight font-bold tracking-wide text-navy-900 uppercase sm:block">
                Tokio Marine<br>
                <span class="text-[0.6rem] font-medium text-slate-500">Insurance Group</span>
            </span>
        </a>

        <nav class="hidden items-center gap-8 md:flex">
            @foreach ($links as $key => $link)
                <a
                    href="{{ $link['url'] }}"
                    @class([
                        'text-sm transition hover:text-brand-600',
                        'font-semibold text-brand-600 underline decoration-2 underline-offset-8' => $active === $key,
                        'font-medium text-slate-600' => $active !== $key,
                    ])
                >{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <a
                href="{{ Route::has('login') ? route('login') : url('/') }}"
                class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-600"
            >{{ __('nav.login') }}</a>

            <form method="GET" action="{{ route('locale.switch') }}" class="relative">
                <select
                    name="locale"
                    onchange="this.form.submit()"
                    aria-label="{{ __('nav.language') }}"
                    class="cursor-pointer appearance-none rounded-lg border border-slate-300 py-2 pr-8 pl-3 text-sm font-medium text-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
                >
                    @foreach (config('carbon-calculator.locales') as $option)
                        <option value="{{ $option }}" @selected($locale === $option)>{{ strtoupper($option) }}</option>
                    @endforeach
                </select>
                <svg class="pointer-events-none absolute top-1/2 right-2 size-4 -translate-y-1/2 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </form>
        </div>
    </div>
</header>
