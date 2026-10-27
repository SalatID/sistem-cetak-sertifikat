<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased">
<div class="flex min-h-full">

    {{-- Sidebar --}}
    <aside class="hidden w-64 shrink-0 flex-col bg-slate-900 md:flex">
        <div class="border-b border-slate-800 px-5 py-4">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Sistem Cetak</p>
            <p class="text-lg font-bold text-white">Sertifikat Kejuaraan</p>
        </div>

        <nav class="flex-1 space-y-1 p-3 text-sm">
            <a href="{{ route('dashboard') }}"
               class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Dashboard
            </a>
            <a href="{{ route('kontingen.index') }}"
               class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('kontingen.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Kontingen
            </a>
            <a href="{{ route('peserta.index') }}"
               class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('peserta.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Peserta &amp; Juara
            </a>
            <a href="{{ route('official.index') }}"
               class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('official.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                Official &amp; Coach
            </a>

            @if (auth()->user()?->isAdmin())
                <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Administrasi</p>
                <a href="{{ route('blangko.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('blangko.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Blangko &amp; Teks
                </a>
                <a href="{{ route('sekolah.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('sekolah.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Sertifikat Sekolah
                </a>
                <a href="{{ route('pengaturan.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('pengaturan.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Pengaturan
                </a>
                <a href="{{ route('log-cetak.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('log-cetak.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Riwayat Cetak
                </a>
                <a href="{{ route('user.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('user.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Manajemen User
                </a>
            @endif

            <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Fase berikutnya</p>
            <span class="block cursor-not-allowed rounded-lg px-3 py-2 text-slate-500">Deploy Tunnel (P5)</span>
        </nav>

        <div class="border-t border-slate-800 p-4 text-sm">
            <p class="font-medium text-white">{{ auth()->user()?->name }}</p>
            <p class="text-xs uppercase tracking-wide text-slate-400">{{ auth()->user()?->role }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-slate-700 px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white">
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Konten --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 md:hidden">
            <p class="font-semibold">Sertifikat Kejuaraan</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-600">Keluar</button>
            </form>
        </header>

        <main class="flex-1 p-4 md:p-6">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Periksa kembali isian berikut:</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
