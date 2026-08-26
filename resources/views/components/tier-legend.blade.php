{{-- Legenda gauge: "Dampak Ringan (0-30)" dengan titik berwarna. --}}
@props(['tiers'])

<ul {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @foreach ($tiers as $tier)
        <li class="flex items-center gap-2 text-[0.7rem] text-slate-600">
            <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $tier->color }}"></span>
            {{ $tier->tr('label') }} ({{ $tier->scoreRangeLabel() }})
        </li>
    @endforeach
</ul>
