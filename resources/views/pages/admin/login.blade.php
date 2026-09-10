<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Masuk ke area admin.
 *
 * Sengaja tanpa pendaftaran, tanpa reset password, dan tanpa tautan dari
 * halaman publik mana pun.
 */
new #[Title('Masuk Admin')] class extends Component
{
    /** Percobaan gagal yang diizinkan sebelum dikunci sementara. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate([
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
            $this->reset('password');

            // Satu pesan untuk email tidak dikenal DAN password salah. Pesan
            // yang berbeda akan memberi tahu penyerang email mana yang terdaftar.
            throw ValidationException::withMessages(['email' => __('admin.auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());

        // Auth::attempt sudah memutar ulang id sesi lewat SessionGuard, baris
        // ini menegaskannya saja.
        session()->regenerate();

        $this->reset('password');

        return $this->redirectIntended(route('admin.participants'), navigate: false);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('admin.auth.throttled', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    /**
     * Dikunci ke email + IP, bukan email saja: kalau hanya email, orang luar
     * bisa mengunci akun admin cuma dengan menebak alamatnya berkali-kali.
     */
    private function throttleKey(): string
    {
        return 'admin-login|'.Str::transliterate(Str::lower(trim($this->email)).'|'.request()->ip());
    }
};
?>

<div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-navy-900 text-sm font-bold text-white">TM</span>
            <div class="leading-tight">
                <p class="text-sm font-bold text-navy-900">{{ __('admin.title') }}</p>
                <p class="text-xs text-slate-500">{{ __('admin.brand', ['brand' => config('carbon-calculator.brand.name')]) }}</p>
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-lg font-bold text-navy-900">{{ __('admin.auth.heading') }}</h1>
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.auth.subtitle') }}</p>

            <form wire:submit="login" class="mt-6 space-y-5">
                <div>
                    <label for="email" class="text-sm font-semibold text-slate-800">{{ __('admin.auth.email') }}</label>
                    <input
                        id="email" type="email" inputmode="email" autocomplete="username"
                        wire:model="email" required autofocus
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
                    >
                    @error('email') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="text-sm font-semibold text-slate-800">{{ __('admin.auth.password') }}</label>
                    <input
                        id="password" type="password" autocomplete="current-password"
                        wire:model="password" required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
                    >
                    @error('password') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2.5 text-sm text-slate-600">
                    <input
                        type="checkbox" wire:model="remember"
                        class="size-4 rounded border-slate-300 text-deep-600 focus:ring-deep-500"
                    >
                    {{ __('admin.auth.remember') }}
                </label>

                <button
                    type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-deep-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-deep-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-deep-800 disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="login">{{ __('admin.auth.submit') }}</span>
                    <span wire:loading wire:target="login">{{ __('admin.auth.processing') }}</span>
                </button>
            </form>
        </div>
    </div>
</div>
