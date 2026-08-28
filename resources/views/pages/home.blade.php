<?php

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Beranda minimal: hanya pintu masuk ke kalkulator.
 * Desain beranda belum ada di mockup, jadi halaman ini sengaja ditahan
 * seadanya dan memakai header/footer yang sama dengan halaman kalkulator.
 */
new #[Title('Tokio Marine Green Campaign')] class extends Component
{
    //
};
?>

<div class="flex min-h-screen flex-col bg-slate-50">
    <x-site-header active="home" />

    <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col items-center justify-center px-4 py-20 text-center">
        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600">
            Tokio Marine Green Campaign
        </span>

        <h1 class="mt-5 text-3xl font-bold text-slate-900 sm:text-4xl">
            {{ __('calculator.title') }}
        </h1>

        <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-600">
            Hitung jejak karbon tahunanmu dari transportasi, listrik rumah, dan pola konsumsi harian —
            lalu dapatkan rekomendasi aksi yang bisa langsung kamu mulai.
        </p>

        <a
            href="{{ route('calculator') }}"
            wire:navigate
            class="mt-8 inline-flex items-center gap-2 rounded-lg bg-deep-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-deep-700"
        >
            Mulai Hitung
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" />
            </svg>
        </a>
    </main>

    <x-site-footer />
</div>
