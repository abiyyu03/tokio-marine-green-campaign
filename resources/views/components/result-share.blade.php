@props(['share'])

{{-- Bagikan hasil. Facebook memakai URL share resminya; Instagram dan
     TikTok tidak menerima tautan dari web, jadi tombolnya membuat
     gambar kartu hasil dulu — lihat resources/js/result-share.js. --}}
<div x-data="resultShare(@js($share))" class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
    <h3 class="text-center text-sm font-bold text-slate-900">{{ __('result.share.heading') }}</h3>
    <p class="mt-1 text-center text-[11px] leading-relaxed text-slate-500">{{ __('result.share.body') }}</p>

    <div class="mt-4 grid grid-cols-3 gap-2">
        <button type="button" x-on:click="shareImage('instagram')" :aria-busy="busy === 'instagram'"
            :class="busy === 'instagram' && 'animate-pulse'"
            class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 px-1 py-3 text-[11px] font-semibold text-slate-700 transition hover:border-[#0d9488]/50 hover:bg-slate-50">
            <span class="grid size-9 place-items-center rounded-full bg-gradient-to-tr from-[#feda75] via-[#d62976] to-[#4f5bd5] text-white">
                <x-social-icon name="instagram" class="size-4" />
            </span>
            {{ __('result.share.instagram') }}
        </button>
        <a href="{{ $share['facebook'] }}" target="_blank" rel="noopener"
            class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 px-1 py-3 text-[11px] font-semibold text-slate-700 transition hover:border-[#0d9488]/50 hover:bg-slate-50">
            <span class="grid size-9 place-items-center rounded-full bg-[#1877F2] text-white">
                <x-social-icon name="facebook" class="size-4" />
            </span>
            {{ __('result.share.facebook') }}
        </a>
        <button type="button" x-on:click="shareImage('tiktok')" :aria-busy="busy === 'tiktok'"
            :class="busy === 'tiktok' && 'animate-pulse'"
            class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 px-1 py-3 text-[11px] font-semibold text-slate-700 transition hover:border-[#0d9488]/50 hover:bg-slate-50">
            <span class="grid size-9 place-items-center rounded-full bg-black text-white">
                <x-social-icon name="tiktok" class="size-4" />
            </span>
            {{ __('result.share.tiktok') }}
        </button>
    </div>

    <p x-show="failed" x-cloak role="alert" class="mt-3 text-[11px] leading-relaxed text-amber-700">{{ __('result.share.failed') }}</p>

    {{-- Pratinjau untuk perangkat tanpa share sheet berkas (desktop,
         sebagian in-app browser). Dipindah ke <body> supaya tidak
         terkurung stacking context panel sticky ini. --}}
    <template x-teleport="body">
        <div x-show="preview" x-cloak x-transition.opacity
            x-on:click.self="closePreview()" x-on:keydown.escape.window="closePreview()"
            role="dialog" aria-modal="true" aria-labelledby="share-preview-title"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/70 p-4">
            <div class="flex max-h-full w-full max-w-sm flex-col overflow-y-auto rounded-2xl bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="share-preview-title" class="text-sm font-bold text-slate-900">{{ __('result.share.preview_title') }}</h2>
                    <button type="button" x-on:click="closePreview()" aria-label="{{ __('result.share.close') }}"
                        class="-m-1 rounded-md p-1 text-slate-400 transition hover:text-slate-700">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <img :src="preview?.src" alt="{{ __('result.share.image_alt') }}"
                    class="mx-auto mt-4 max-h-[50vh] w-auto rounded-xl border border-slate-200">

                <p class="mt-4 text-xs leading-relaxed text-slate-600">
                    <span x-show="preview?.platform === 'instagram'">{{ __('result.share.preview_instagram') }}</span>
                    <span x-show="preview?.platform === 'tiktok'">{{ __('result.share.preview_tiktok') }}</span>
                    </p>
                <p class="mt-2 text-[11px] leading-relaxed text-slate-500">{{ __('result.share.preview_hint') }}</p>

                <a :href="preview?.src" download="{{ __('result.share.file_name') }}"
                    class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg bg-[#0d9488] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    {{ __('result.share.download') }}
                </a>
            </div>
        </div>
    </template>
</div>
