{{--
    Versi teks. Dikirim berdampingan dengan versi HTML: klien yang menolak
    HTML tetap dapat isinya, dan email multipart lebih jarang dianggap spam.
--}}
{!! __('mail.result.greeting', ['name' => $name]) !!}

{!! __('mail.result.intro') !!}

{!! __('mail.result.score_label') !!}: {{ $score }}@if ($tierLabel) ({{ $tierLabel }})@endif

{!! __('mail.result.total_label') !!}: ±{{ $totalTon }} {!! __('mail.result.ton_unit') !!}
@if ($recommendations !== [])

{!! __('mail.result.recommendation_heading') !!}
@foreach ($recommendations as $recommendation)
- {!! html_entity_decode(trim(strip_tags((string) $recommendation)), ENT_QUOTES) !!}
@endforeach
@endif

{!! __('mail.result.cta_result') !!}:
{{ $resultUrl }}

{!! __('mail.result.cta_report') !!}:
{{ $reportUrl }}

{!! __('mail.result.download_hint', ['action' => __('mail.result.download_hint_action')]) !!}

{!! __('mail.result.keep_link') !!}
{!! __('mail.result.private_link') !!}

--
{!! __('mail.result.footer_auto') !!}
{!! __('mail.result.footer_reason', ['app' => __('mail.result.brand')]) !!}
