<?php

use App\Models\ResultTier;
use App\Models\Submission;
use App\Support\ResultText;
use App\Support\SubmissionFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar peserta kalkulator.
 *
 * Filternya disusun App\Support\SubmissionFilter yang juga dipakai kedua ekspor
 * (CSV dan Excel), sehingga berkas yang terunduh selalu berisi persis apa yang
 * terlihat di layar.
 */
new #[Title('Peserta | Admin')] class extends Component
{
    use WithPagination;

    /** Filter ikut di URL supaya bisa disalin, di-bookmark, dan di-refresh. */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'tier', except: '')]
    public string $tier = '';

    #[Url(as: 'dari', except: '')]
    public string $from = '';

    #[Url(as: 'sampai', except: '')]
    public string $until = '';

    /** Mengubah filter apa pun harus mengembalikan pembaca ke halaman satu. */
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'tier', 'from', 'until']);
        $this->resetPage();
    }

    #[Computed]
    public function filter(): SubmissionFilter
    {
        return new SubmissionFilter(
            search: trim($this->search),
            status: $this->status,
            tierId: is_numeric($this->tier) ? (int) $this->tier : null,
            from: $this->from,
            until: $this->until,
        );
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->filter
            ->apply(Submission::query())
            // Sama persis dengan ResultReport::forUuid(): tanpa `translations`
            // nama tier akan memicu satu query per baris.
            ->with(['lead', 'result.tier.translations'])
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function tiers(): Collection
    {
        return ResultTier::query()->active()->ordered()->withTranslation()->get();
    }

    #[Computed]
    public function timezone(): string
    {
        return config('carbon-calculator.display_timezone', 'UTC');
    }
};
?>

<x-admin-shell :title="__('admin.list.title')" :subtitle="__('admin.list.timezone_note', ['zone' => $this->timezone])">
    <x-slot:actions>
        {{--
            Tautan biasa, bukan wire:navigate: unduhan butuh navigasi nyata.

            Excel lebih dulu karena itu yang dipakai sehari-hari; CSV tetap ada
            untuk diimpor ke alat lain. Keduanya membawa filter yang sedang
            aktif, jadi yang terunduh persis yang terlihat di tabel bawah.
        --}}
        <span class="text-xs font-semibold text-slate-500">{{ __('admin.list.export') }}</span>

        <div class="inline-flex overflow-hidden rounded-lg border border-deep-600">
            <a
                href="{{ route('admin.participants.export.xlsx', $this->filter->toQuery()) }}"
                title="{{ __('admin.list.export_xlsx_title') }}"
                class="inline-flex items-center gap-2 bg-deep-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-deep-700"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('admin.list.export_xlsx') }}
            </a>

            <a
                href="{{ route('admin.participants.export', $this->filter->toQuery()) }}"
                title="{{ __('admin.list.export_csv_title') }}"
                class="border-l border-deep-600 bg-white px-4 py-2 text-sm font-semibold text-deep-700 transition hover:bg-deep-50"
            >
                {{ __('admin.list.export_csv') }}
            </a>
        </div>
    </x-slot:actions>

    {{-- Filter --}}
    <div class="rounded-card border border-slate-200 bg-white p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="search" class="sr-only">{{ __('admin.list.search_label') }}</label>
                <input
                    id="search" type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('admin.list.search_placeholder') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none"
                >
            </div>

            <select wire:model.live="status" aria-label="{{ __('admin.columns.status') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none">
                <option value="">{{ __('admin.list.status_all') }}</option>
                <option value="completed">{{ __('admin.status.completed') }}</option>
                <option value="draft">{{ __('admin.status.draft') }}</option>
            </select>

            <select wire:model.live="tier" aria-label="{{ __('admin.columns.tier') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none">
                <option value="">{{ __('admin.list.tier_all') }}</option>
                @foreach ($this->tiers as $tier)
                    <option value="{{ $tier->id }}">{{ $tier->tr('label') }}</option>
                @endforeach
            </select>

            <div class="grid grid-cols-2 gap-2">
                <input type="date" wire:model.live="from" aria-label="{{ __('admin.list.from') }}"
                       class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none">
                <input type="date" wire:model.live="until" aria-label="{{ __('admin.list.until') }}"
                       class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-700 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none">
            </div>
        </div>

        @unless ($this->filter->isEmpty())
            <button type="button" wire:click="resetFilters"
                    class="mt-3 text-xs font-semibold text-deep-600 transition hover:text-deep-800">
                {{ __('admin.list.reset') }}
            </button>
        @endunless
    </div>

    {{-- Tabel --}}
    <div class="mt-4 overflow-hidden rounded-card border border-slate-200 bg-white">
        @if ($this->rows->isEmpty())
            <p class="px-6 py-16 text-center text-sm text-slate-500">
                {{ $this->filter->isEmpty() ? __('admin.list.empty') : __('admin.list.empty_filtered') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('admin.columns.name') }}</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('admin.columns.whatsapp') }}</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('admin.columns.status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('admin.columns.score') }}</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('admin.columns.tier') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('admin.columns.total') }}</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('admin.columns.created_at') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium"><span class="sr-only">{{ __('admin.list.detail') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($this->rows as $row)
                            @php($tier = $row->result?->tier)
                            <tr class="transition hover:bg-slate-50" wire:key="submission-{{ $row->id }}">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $row->lead?->name ?? __('admin.empty_value') }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->lead?->email ?? __('admin.empty_value') }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $row->lead?->whatsapp_number ?? __('admin.empty_value') }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        'bg-deep-50 text-deep-700' => $row->status === 'completed',
                                        'bg-slate-100 text-slate-600' => $row->status !== 'completed',
                                    ])>{{ __('admin.status.'.$row->status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900 tabular-nums">
                                    {{ $row->result?->score ?? __('admin.empty_value') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($tier)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold" style="color: {{ $tier->color }}">
                                            <span class="size-1.5 rounded-full" style="background-color: {{ $tier->color }}"></span>
                                            {{ $tier->tr('label') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">{{ __('admin.empty_value') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700 tabular-nums">
                                    @if ($row->result)
                                        {{ ResultText::tonCompact($row->result->total_kg_co2e_year) }} Ton
                                    @else
                                        {{ __('admin.empty_value') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 tabular-nums">
                                    {{ $row->created_at?->timezone($this->timezone)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.participants.show', $row->uuid) }}" wire:navigate
                                       class="text-sm font-semibold text-deep-600 transition hover:text-deep-800">
                                        {{ __('admin.list.detail') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                <p class="mb-3 text-xs text-slate-500">
                    {{ __('admin.list.showing', ['count' => $this->rows->count(), 'total' => $this->rows->total()]) }}
                </p>
                {{ $this->rows->links() }}
            </div>
        @endif
    </div>
</x-admin-shell>
