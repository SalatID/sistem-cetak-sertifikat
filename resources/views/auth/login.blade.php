@extends('layouts.guest')

@section('title', 'Masuk — Sistem Cetak Sertifikat')

@section('content')
<div class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Sistem Cetak</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Sertifikat Kejuaraan</h1>
        <p class="mt-1 text-sm text-slate-500">Silakan masuk untuk mengelola data peserta.</p>
    </div>

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            Masuk
        </button>
    </form>
</div>
@endsection
