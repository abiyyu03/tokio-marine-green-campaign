{{--
    Email hasil kalkulator.

    Ditulis dengan tabel dan gaya inline, bukan kelas Tailwind: klien email
    (Outlook, Gmail app) membuang <style> dan tidak mengenal flex/grid. Tanpa
    satu pun gambar eksternal juga, supaya isinya tetap utuh meski pemuatan
    gambar diblokir — yang merupakan setelan bawaan di banyak klien.
--}}
@php($ink = '#1f2937')
@php($muted = '#5f7370')
@php($teal = '#0d9488')
@php($navy = '#0b3b36')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light only">
    <title>{{ __('mail.result.subject', ['name' => $name]) }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7f9; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; color:{{ $ink }};">

    {{-- Cuplikan yang tampil di daftar inbox, tidak ikut terlihat di badan email. --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        {{ __('mail.result.preheader') }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f7f9;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; background-color:#ffffff; border-radius:14px; overflow:hidden;">

                    {{-- Kepala --}}
                    <tr>
                        <td style="background-color:{{ $navy }}; padding:20px 28px;">
                            <p style="margin:0; font-size:13px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; color:#ffffff;">
                                {{ config('carbon-calculator.brand.name') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Sapaan --}}
                    <tr>
                        <td style="padding:28px 28px 0 28px;">
                            <h1 style="margin:0; font-size:22px; line-height:1.3; color:{{ $ink }};">
                                {{ __('mail.result.greeting', ['name' => $name]) }}
                            </h1>
                            <p style="margin:12px 0 0 0; font-size:14px; line-height:1.6; color:{{ $muted }};">
                                {{ __('mail.result.intro') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Kartu ringkasan angka --}}
                    <tr>
                        <td style="padding:20px 28px 0 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef8f8; border:1px solid #d6eef0; border-radius:12px;">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <p style="margin:0; font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:0.06em; color:{{ $muted }};">
                                            {{ __('mail.result.score_label') }}
                                        </p>
                                        <p style="margin:6px 0 0 0; font-size:34px; line-height:1; font-weight:bold; color:{{ $ink }};">
                                            {{ $score }}
                                            @if ($tierLabel)
                                                <span style="display:inline-block; margin-left:8px; padding:5px 12px; font-size:12px; font-weight:bold; border-radius:999px; color:{{ $tierColor }}; background-color:{{ $tierColor }}1A; vertical-align:middle;">
                                                    {{ $tierLabel }}
                                                </span>
                                            @endif
                                        </p>

                                        <p style="margin:18px 0 0 0; padding-top:16px; border-top:1px solid #d6eef0; font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:0.06em; color:{{ $muted }};">
                                            {{ __('mail.result.total_label') }}
                                        </p>
                                        <p style="margin:6px 0 0 0; font-size:24px; line-height:1.2; font-weight:bold; color:{{ $ink }};">
                                            ±{{ $totalTon }}
                                            <span style="font-size:13px; font-weight:normal; color:{{ $muted }};">{{ __('mail.result.ton_unit') }}</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if ($tierHeadline)
                        <tr>
                            <td style="padding:20px 28px 0 28px;">
                                <p style="margin:0; font-size:15px; line-height:1.5; font-weight:bold; color:{{ $navy }};">
                                    {!! $tierHeadline !!}
                                </p>
                            </td>
                        </tr>
                    @endif

                    @if ($recommendations !== [])
                        <tr>
                            <td style="padding:20px 28px 0 28px;">
                                <p style="margin:0 0 8px 0; font-size:14px; font-weight:bold; color:{{ $ink }};">
                                    {{ __('mail.result.recommendation_heading') }}
                                </p>
                                <ul style="margin:0; padding-left:20px;">
                                    @foreach ($recommendations as $recommendation)
                                        <li style="margin:0 0 8px 0; font-size:14px; line-height:1.6; color:{{ $muted }};">
                                            {!! $recommendation !!}
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif

                    {{-- Dua tombol: kembali ke hasil, dan unduh laporan --}}
                    <tr>
                        <td style="padding:26px 28px 0 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-radius:8px; background-color:{{ $teal }};">
                                        <a href="{{ $resultUrl }}" style="display:inline-block; padding:13px 22px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:8px;">
                                            {{ __('mail.result.cta_result') }}
                                        </a>
                                    </td>
                                </tr>
                                <tr><td style="height:10px; line-height:10px; font-size:0;">&nbsp;</td></tr>
                                <tr>
                                    <td style="border-radius:8px; border:1px solid {{ $teal }};">
                                        <a href="{{ $reportUrl }}" style="display:inline-block; padding:12px 21px; font-size:14px; font-weight:bold; color:{{ $teal }}; text-decoration:none; border-radius:8px;">
                                            {{ __('mail.result.cta_report') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:14px 0 0 0; font-size:12px; line-height:1.6; color:{{ $muted }};">
                                {!! __('mail.result.download_hint', [
                                    'action' => '<strong style="color:'.$ink.';">'.e(__('mail.result.download_hint_action')).'</strong>',
                                ]) !!}
                            </p>
                        </td>
                    </tr>

                    {{-- Tautan mentah: tombol kerap dinonaktifkan atau tidak terbaca --}}
                    <tr>
                        <td style="padding:20px 28px 0 28px;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:{{ $muted }};">
                                {{ __('mail.result.cta_help') }}<br>
                                <a href="{{ $resultUrl }}" style="color:{{ $teal }}; word-break:break-all;">{{ $resultUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Simpan tautannya: tidak ada akun, ini satu-satunya jalan kembali --}}
                    <tr>
                        <td style="padding:22px 28px 0 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f9ff; border:1px solid #e0f2fe; border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <p style="margin:0; font-size:12px; line-height:1.6; color:#0f5b7a;">
                                            {{ __('mail.result.keep_link') }}
                                        </p>
                                        <p style="margin:8px 0 0 0; font-size:12px; line-height:1.6; color:#0f5b7a;">
                                            {{ __('mail.result.private_link') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Kaki --}}
                    <tr>
                        <td style="padding:26px 28px 30px 28px;">
                            <p style="margin:22px 0 0 0; padding-top:18px; border-top:1px solid #e6ecea; font-size:11px; line-height:1.6; color:#8a9a97;">
                                {{ __('mail.result.footer_auto') }}<br>
                                {{ __('mail.result.footer_reason', ['app' => config('carbon-calculator.brand.name')]) }}
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
