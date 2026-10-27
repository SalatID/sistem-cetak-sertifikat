@extends('layouts.app')

@php
    $isEdit = $kontingen->exists;
    $action = $isEdit ? route('kontingen.update', $kontingen) : route('kontingen.store');
@endphp

@section('title', $isEdit ? 'Ubah Kontingen' : 'Tambah Kontingen')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">{{ $isEdit ? 'Ubah Kontingen' : 'Tambah Kontingen' }}</h1>
    <p class="text-sm text-slate-500">Data kontingen dipakai pada peserta, sertifikat, dan SK.</p>
</div>

<div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="nama" class="mb-1 block text-sm font-medium text-slate-700">Nama Kontingen <span class="text-rose-500">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $kontingen->nama) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <p class="mt-1 text-xs text-slate-400">Nama harus unik antar kontingen.</p>
        </div>

        <div>
            <label for="kota" class="mb-1 block text-sm font-medium text-slate-700">Kota / Daerah</label>
            <input type="text" id="kota" name="kota" value="{{ old('kota', $kontingen->kota) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Kontingen' }}
            </button>
            <a href="{{ route('kontingen.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
        </div>
    </form>
</div>
@endsection
