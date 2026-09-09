{{--
    Kerangka halaman admin.

    Komponen anonim di dalam layout yang sudah ada, bukan layout kedua: layout
    terpisah berarti menduplikasi @vite, @livewireStyles, meta CSRF, dan
    preconnect font — dua dokumen yang lambat laun akan berbeda isi.

    Bar atas sengaja navy, bukan putih seperti header kampanye, supaya sekali
    lihat jelas ini area internal.
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'back' => null,      // URL tautan "kembali", opsional
    'actions' => null,   // slot tombol di kanan judul
])

<div class="flex min-h-screen flex-col bg-slate-50">
    <header class="sticky top-0 z-30 bg-navy-900 text-slate-200">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6">
                <a href="{{ route('admin.participants') }}" wire:navigate class="flex items-center gap-2.5">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-white text-[11px] font-bold text-navy-900">TM</span>
                    <span class="text-sm font-bold text-white">{{ __('admin.title') }}</span>
                </a>

                <a
                    href="{{ route('admin.participants') }}"
                    wire:navigate
                    @class([
                        'text-sm transition hover:text-white',
                        'font-semibold text-white' => request()->routeIs('admin.participants*'),
                        'text-slate-300' => ! request()->routeIs('admin.participants*'),
                    ])
                >{{ __('admin.nav.participants') }}</a>
            </div>

            <div class="flex items-center gap-4">
                <span class="hidden text-xs text-slate-300 sm:block">{{ auth()->user()?->email }}</span>

                {{-- POST + CSRF: logout lewat GET bisa dipicu dari situs lain. --}}
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-slate-400 hover:text-white"
                    >{{ __('admin.auth.logout') }}</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-deep-100 bg-deep-50 px-4 py-3 text-sm font-medium text-deep-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($back)
            <a href="{{ $back }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-deep-600 transition hover:text-deep-800">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                {{ __('admin.detail.back') }}
            </a>
        @endif

        @if ($title)
            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-navy-900">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>

                @if ($actions)
                    <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                @endif
            </div>
        @endif

        <div class="mt-6">{{ $slot }}</div>
    </main>
</div>
