<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Cetak Sertifikat' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased">
<div class="min-h-full">

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Sistem Cetak</p>
                <h1 class="text-base font-bold text-slate-900 sm:text-lg">{{ $judul ?? 'Cetak Sertifikat' }}</h1>
            </div>
            <nav class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                <a href="{{ route('publik.index') }}" class="font-medium text-slate-600 hover:text-slate-900">Cari Peserta</a>
                <a href="{{ route('publik.official') }}" class="font-medium text-slate-600 hover:text-slate-900">Sertifikat Coach/Official</a>
                {{-- <a href="{{ route('login') }}" class="rounded-lg bg-slate-800 px-3 py-2 font-medium text-white hover:bg-slate-900">Masuk Panitia</a> --}}
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-8 pt-4 text-center text-xs text-slate-400">
        Layanan cetak sertifikat — {{ \App\Models\Setting::getValue('nama_kejuaraan', 'Kejuaraan') }}
    </footer>
</div>
</body>
</html>
