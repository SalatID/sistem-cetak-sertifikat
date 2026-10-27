@extends('layouts.publik')

@section('title', 'Sertifikat Coach/Official')
@section('judul', 'Sertifikat Coach / Pelatih / Official')

@section('content')
<div class="mx-auto w-full max-w-xl rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
    <h2 class="font-semibold text-slate-900">Cetak Sertifikat Coach / Official</h2>
    <p class="mt-1 text-sm text-slate-500">Isi nama Anda dan pilih kontingen Anda. Sertifikat langsung tercetak dalam PDF.</p>

    <form method="POST" action="{{ route('publik.official.cetak') }}" target="_blank" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="nama" class="mb-1 block text-sm font-medium text-slate-700">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <div>
            <label for="kontingen_id" class="mb-1 block text-sm font-medium text-slate-700">Kontingen <span class="text-rose-500">*</span></label>
            <select id="kontingen_id" name="kontingen_id" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                <option value="">— Pilih kontingen —</option>
                @foreach ($kontingens as $kontingen)
                    <option value="{{ $kontingen->id }}" @selected((string) old('kontingen_id') === (string) $kontingen->id)>{{ $kontingen->nama }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="jabatan" class="mb-1 block text-sm font-medium text-slate-700">Jabatan <span class="text-rose-500">*</span></label>
            <select id="jabatan" name="jabatan" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                @foreach ($jabatanOptions as $jabatan)
                    <option value="{{ $jabatan }}" @selected(old('jabatan', 'Coach') === $jabatan)>{{ $jabatan }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
            Cetak Sertifikat
        </button>
    </form>
</div>
@endsection
