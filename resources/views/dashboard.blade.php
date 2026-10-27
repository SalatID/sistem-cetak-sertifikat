@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
    <p class="text-sm text-slate-500">Ringkasan data kejuaraan.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Kontingen</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalKontingen }}</p>
        <a href="{{ route('kontingen.index') }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Kelola kontingen →</a>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Peserta</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalPeserta }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ $totalBelumJuara }} peserta belum memiliki keterangan juara</p>
        <p class="mt-1 text-xs text-slate-400">{{ $jumlahPesertaLabel }} Peserta · {{ $jumlahPesilatTerbaik }} Pesilat Terbaik</p>
        <a href="{{ route('peserta.index') }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Kelola peserta →</a>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Juara 1 (Emas)</p>
        <p class="mt-1 text-3xl font-bold text-amber-500">{{ $juaraEmas }}</p>
        <p class="mt-1 text-xs text-slate-400">Total juara: {{ $totalJuara }}</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Juara 2 &amp; 3</p>
        <p class="mt-1 text-3xl font-bold text-slate-700">{{ $juaraPerak }} <span class="text-base font-medium text-slate-400">/</span> {{ $juaraPerunggu }}</p>
        <p class="mt-1 text-xs text-slate-400">Perak / Perunggu</p>
    </div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Halaman tercetak hari ini</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $halamanHariIni }}</p>
        <p class="mt-1 text-xs text-slate-400">Total {{ $totalHalaman }} halaman sepanjang waktu</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Cetak Sertifikat &amp; SK</p>
        <p class="mt-1 text-sm text-slate-600">Pilih peserta pada daftar, lalu cetak langsung ke PDF.</p>
        <a href="{{ route('peserta.index') }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Buka daftar peserta →</a>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Sertifikat Coach / Official</p>
        <p class="mt-1 text-sm text-slate-600">Ketik nama, pilih kontingen, cetak sertifikatnya.</p>
        <a href="{{ route('official.index') }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Kelola official →</a>
    </div>
</div>

<div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
    <p class="font-semibold">Fase P5 belum dijalankan</p>
    <p class="mt-1">
        Modul <strong>Cetak Sertifikat, SK Juara, dan Sertifikat Official</strong> sudah aktif.
        Yang tersisa: setup domain publik (Cloudflare Tunnel), hardening file .env produksi, dan UAT bersama panitia.
        Pengaturan dokumen (kop, logo, background, template teks) serta riwayat pencetakan tersedia di menu terkait.
    </p>
</div>
@endsection
