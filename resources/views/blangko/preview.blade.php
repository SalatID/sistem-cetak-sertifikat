@extends('layouts.app')

@section('title', 'Preview Blangko')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Preview — {{ $blangko->nama }}</h1>
        <p class="text-sm text-slate-500">
            {{ strtoupper($blangko->ukuran_kertas) }} {{ $blangko->lebar_mm }}×{{ $blangko->tinggi_mm }} mm ·
            contoh tampilan dengan data contoh. Tutup tab ini untuk kembali.
        </p>
    </div>
    <a href="{{ route('blangko.edit', $blangko) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        {{ $blangko->pakaiBlade() ? 'Kembali ke Editor' : 'Kembali ke Desainer' }}
    </a>
</div>

<div class="overflow-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    {!! $html !!}
</div>
@endsection
