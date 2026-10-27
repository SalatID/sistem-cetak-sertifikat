@extends('layouts.app')

@php
    $isEdit = $peserta->exists;
    $action = $isEdit ? route('peserta.update', $peserta) : route('peserta.store');
@endphp

@section('title', $isEdit ? 'Ubah Peserta' : 'Tambah Peserta')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">{{ $isEdit ? 'Ubah Peserta' : 'Tambah Peserta' }}</h1>
    <p class="text-sm text-slate-500">Satu baris peserta mewakili satu orang dalam satu kategori.</p>
</div>

<div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="nama" class="mb-1 block text-sm font-medium text-slate-700">Nama Peserta <span class="text-rose-500">*</span></label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $peserta->nama) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>

            <div>
                <label for="kontingen_id" class="mb-1 block text-sm font-medium text-slate-700">Kontingen <span class="text-rose-500">*</span></label>
                <select id="kontingen_id" name="kontingen_id" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    <option value="">— Pilih kontingen —</option>
                    @foreach ($kontingens as $kontingen)
                        <option value="{{ $kontingen->id }}" @selected((string) old('kontingen_id', $peserta->kontingen_id) === (string) $kontingen->id)>
                            {{ $kontingen->nama }}
                        </option>
                    @endforeach
                </select>
                @if ($kontingens->isEmpty())
                    <p class="mt-1 text-xs text-amber-600">Belum ada kontingen. <a class="underline" href="{{ route('kontingen.create') }}">Tambah kontingen dulu</a>.</p>
                @endif
            </div>

            <div>
                <label for="jenis_kelamin" class="mb-1 block text-sm font-medium text-slate-700">Jenis Kelamin</label>
                <select id="jenis_kelamin" name="jenis_kelamin"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    <option value="">—</option>
                    <option value="L" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'P')>Perempuan</option>
                </select>
            </div>

            <div>
                <label for="kategori" class="mb-1 block text-sm font-medium text-slate-700">Kategori <span class="text-rose-500">*</span></label>
                <input type="text" id="kategori" name="kategori" value="{{ old('kategori', $peserta->kategori) }}" required
                       list="daftar-kategori"
                       placeholder="mis. Kata Perorangan Putra"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                <datalist id="daftar-kategori">
                    @foreach (\App\Models\Peserta::query()->distinct()->orderBy('kategori')->pluck('kategori') as $item)
                        <option value="{{ $item }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label for="kelas" class="mb-1 block text-sm font-medium text-slate-700">Kelas</label>
                <input type="text" id="kelas" name="kelas" value="{{ old('kelas', $peserta->kelas) }}" placeholder="mis. -60 kg"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>

            <div>
                <label for="juara" class="mb-1 block text-sm font-medium text-slate-700">Juara / Keterangan</label>
                <input type="text" id="juara" name="juara" list="daftar-juara"
                       value="{{ old('juara', $peserta->juara) }}"
                       placeholder="mis. Peserta, Pesilat Terbaik, atau Juara 1"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                <datalist id="daftar-juara">
                    @foreach (\App\Models\Peserta::pilihanJuara() as $pilihan)
                        <option value="{{ $pilihan }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-slate-400">Bebas teks. Contoh: <em>Peserta</em>, <em>Pesilat Terbaik</em>, atau <em>Juara 1</em>.</p>
            </div>

            <div>
                <label for="kelompok_usia" class="mb-1 block text-sm font-medium text-slate-700">Kelompok Usia</label>
                <select id="kelompok_usia" name="kelompok_usia"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    <option value="">—</option>
                    @foreach (\App\Models\Peserta::pilihanKelompokUsia() as $usia)
                        <option value="{{ $usia }}" @selected(strcasecmp((string) old('kelompok_usia', $peserta->kelompok_usia), $usia) === 0)>{{ $usia }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Menentukan sertifikat yang dipakai: prestasi atau pemasalan.</p>
            </div>

            <div>
                <label for="no_sk" class="mb-1 block text-sm font-medium text-slate-700">Nomor SK</label>
                <input type="text" id="no_sk" name="no_sk" value="{{ old('no_sk', $peserta->no_sk) }}"
                       placeholder="mis. 001/SK/SKX26/III/2026"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                <p class="mt-1 text-xs text-slate-400">Diisi manual oleh panitia (dibaca saat cetak SK).</p>
            </div>

            <div>
                <label for="no_sertifikat" class="mb-1 block text-sm font-medium text-slate-700">Nomor Sertifikat</label>
                <input type="text" id="no_sertifikat" value="{{ $peserta->no_sertifikat ?? 'otomatis saat cetak' }}" disabled
                       class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Diberi sistem saat sertifikat dicetak pertama kali.</p>
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Peserta' }}
            </button>
            <a href="{{ route('peserta.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
        </div>
    </form>
</div>
@endsection
