{{-- Langkah terakhir: form data diri + persetujuan. --}}
<h2 class="text-lg font-bold text-brand-500">{{ __('calculator.personal.title') }}</h2>

<div class="mt-4 space-y-5">
    <div>
        <label for="lead-name" class="text-sm font-semibold text-slate-800">
            {{ __('calculator.personal.name') }}
        </label>
        <input
            id="lead-name" type="text" wire:model.blur="name"
            placeholder="{{ __('calculator.personal.name_placeholder') }}"
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
        >
        @error('name') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="lead-email" class="text-sm font-semibold text-slate-800">
            {{ __('calculator.personal.email') }}
        </label>
        <input
            id="lead-email" type="email" inputmode="email" wire:model.blur="email"
            placeholder="{{ __('calculator.personal.email_placeholder') }}"
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
        >
        @error('email') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="lead-whatsapp" class="text-sm font-semibold text-slate-800">
            {{ __('calculator.personal.whatsapp') }}
        </label>
        <input
            id="lead-whatsapp" type="tel" inputmode="tel" wire:model.blur="whatsapp"
            placeholder="{{ __('calculator.personal.whatsapp_placeholder') }}"
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
        >
        @error('whatsapp') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="lead-dob" class="text-sm font-semibold text-slate-800">
            {{ __('calculator.personal.dob') }}
        </label>
        <input
            id="lead-dob" type="date" wire:model.blur="dob" max="{{ now()->toDateString() }}"
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
        >
        @error('dob') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
    </div>

    <fieldset>
        <legend class="text-sm font-semibold text-slate-800">{{ __('calculator.personal.gender') }}</legend>
        <div class="mt-2 flex flex-wrap gap-6">
            @foreach (['male' => __('calculator.personal.gender_male'), 'female' => __('calculator.personal.gender_female')] as $value => $label)
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                    <input type="radio" wire:model.live="gender" value="{{ $value }}" class="size-4 border-slate-300 text-brand-500 focus:ring-brand-200">
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('gender') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
    </fieldset>

    <fieldset class="border-t border-slate-200 pt-5">
        <legend class="text-sm leading-snug font-semibold text-slate-800">
            {{ __('calculator.personal.intent') }}
        </legend>

        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            @foreach (['belum_tahu', 'mungkin', 'tentu'] as $value)
                @php $selected = $intent === $value; @endphp
                <button
                    type="button"
                    wire:click="$set('intent', '{{ $value }}')"
                    aria-pressed="{{ $selected ? 'true' : 'false' }}"
                    @class([
                        'group relative flex h-16 items-start rounded-lg border p-3 text-left transition',
                        'border-brand-500 bg-brand-50 ring-1 ring-brand-500' => $selected,
                        'border-slate-200 bg-white hover:border-brand-300' => ! $selected,
                    ])
                >
                    <span @class([
                        'text-xs font-medium',
                        'text-brand-700' => $selected,
                        'text-slate-700' => ! $selected,
                    ])>{{ __('calculator.personal.intent_'.$value) }}</span>

                    <span @class([
                        'absolute top-3 right-3 grid size-4 place-items-center rounded-full border-2 transition',
                        'border-brand-500' => $selected,
                        'border-slate-300 group-hover:border-brand-300' => ! $selected,
                    ])>
                        @if ($selected)
                            <span class="size-2 rounded-full bg-brand-500"></span>
                        @endif
                    </span>
                </button>
            @endforeach
        </div>
        @error('intent') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
    </fieldset>

    <div class="border-t border-slate-200 pt-5">
        <label class="flex cursor-pointer items-start gap-2.5">
            <input type="checkbox" wire:model.live="consent" class="mt-0.5 size-4 shrink-0 rounded border-slate-300 text-brand-500 focus:ring-brand-200">
            <span class="text-[0.7rem] leading-relaxed text-slate-600">
                {{ __('calculator.personal.consent', [
                    'terms' => __('calculator.personal.consent_terms'),
                    'privacy' => __('calculator.personal.consent_privacy'),
                ]) }}
            </span>
        </label>
        @error('consent') <p class="mt-1 text-xs text-tm-red">{{ $message }}</p> @enderror
    </div>
</div>
