<!DOCTYPE html>
{{-- Tambahkan scroll-smooth di tag html ini --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Judul, deskripsi, kartu WhatsApp/sosmed, dan structured data --}}
        <x-seo-head :title="$title ?? null" />

        <link rel="icon" href="{{ asset('asset/images/logo.png') }}" type="image/png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    {{-- Hapus scroll-smooth dari tag body --}}
    <body class="min-h-screen bg-white font-sans text-slate-800 antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>