{{--
    Gauge "Skor Kamu": busur 270° dari kiri-bawah ke kanan-bawah.
    Warna busur mengikuti warna tier yang sedang aktif.
--}}
@props([
    'score' => 0,
    'tier' => null,
    'size' => 132,
    'emptyLabel' => null,
])

@php
    $radius = 48;
    $circumference = 2 * M_PI * $radius;
    $sweep = $circumference * 0.75;                      // 270 dari 360 derajat
    $progress = $sweep * max(0, min(100, (int) $score)) / 100;
    $color = $tier?->color ?: '#CBD5E1';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center']) }}>
    <div class="relative" style="width: {{ $size }}px; height: {{ $size }}px;">
        <svg viewBox="0 0 120 120" class="size-full -rotate-0" role="img"
             aria-label="{{ __('calculator.score_card.title') }}: {{ $score }}">
            <circle
                cx="60" cy="60" r="{{ $radius }}"
                fill="none" stroke="#E2E8F0" stroke-width="10" stroke-linecap="round"
                stroke-dasharray="{{ $sweep }} {{ $circumference }}"
                transform="rotate(135 60 60)"
            />
            <circle
                cx="60" cy="60" r="{{ $radius }}"
                fill="none" stroke="{{ $color }}" stroke-width="10" stroke-linecap="round"
                stroke-dasharray="{{ $progress }} {{ $circumference }}"
                transform="rotate(135 60 60)"
                class="transition-all duration-500 ease-out"
            />
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-3xl leading-none font-bold text-slate-900">{{ $score }}</span>
            <span class="mt-1 px-2 text-center text-[0.65rem] leading-tight font-medium text-slate-500">
                {{ $tier?->tr('label') ?? $emptyLabel ?? __('calculator.score_card.empty') }}
            </span>
        </div>
    </div>
</div>
