@extends('layouts.app')

@section('title', 'Stempel Legalisir')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Stempel Legalisir</h1>
        <p class="text-sm text-slate-500">
            Setiap desain stempel memiliki satu posisi tetap di halaman sertifikat.
            Pilih salah satu stempel saat mencetak legalisir.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('legalisir.index') }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Legalisir
        </a>
        <a href="{{ route('legalisir.stempel.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Tambah Stempel
        </a>
    </div>
</div>

<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Blangko Peserta Aktif</p>
        <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $blangko?->nama ?? 'Template bawaan' }}</p>
        <p class="text-xs text-slate-400">{{ $pageWidth }} × {{ $pageHeight }} mm</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jumlah Copy</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $jumlahCopy }}×</p>
        <p class="text-xs text-slate-400">Diatur di Pengaturan</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Stempel Aktif</p>
        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stempels->where('aktif', true)->count() }}</p>
        <p class="text-xs text-slate-400">Dari {{ $stempels->count() }} desain</p>
    </div>
</div>

<div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
    Posisi stempel dihitung dari sudut kiri atas halaman aktif. Gunakan PNG transparan
    agar stempel menyatu dengan sertifikat. Warna stempel dipertahankan saat cetak hitam putih.
</div>

@forelse ($stempels as $stempel)
    @if ($loop->first)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @endif

    <div class="flex flex-col rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex h-44 items-center justify-center overflow-hidden rounded-t-xl border-b border-slate-100 bg-slate-50 p-3">
            @if ($stempel->urlGambar())
                <img src="{{ $stempel->urlGambar() }}" alt="{{ $stempel->nama }}" class="max-h-full max-w-full object-contain">
            @else
                <span class="text-sm text-slate-400">Gambar tidak ditemukan</span>
            @endif
        </div>

        <div class="flex flex-1 flex-col gap-3 p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900">{{ $stempel->nama }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $stempel->labelPosisi() }}</p>
                </div>
                @if ($stempel->aktif)
                    <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                        Aktif
                    </span>
                @else
                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 ring-1 ring-inset ring-slate-200">
                        Nonaktif
                    </span>
                @endif
            </div>

            <div class="mt-auto flex items-center justify-end gap-2">
                <a href="{{ route('legalisir.stempel.edit', $stempel) }}"
                   class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Ubah
                </a>
                <form method="POST" action="{{ route('legalisir.stempel.destroy', $stempel) }}"
                      data-confirm="Hapus stempel &quot;{{ $stempel->nama }}&quot;?">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($loop->last)
        </div>
    @endif
@empty
    <div class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <p class="text-lg font-semibold text-slate-800">Belum ada stempel legalisir</p>
        <p class="mt-1 text-sm text-slate-500">
            Unggah desain stempel (disarankan PNG transparan), lalu atur posisinya di halaman sertifikat.
        </p>
        <a href="{{ route('legalisir.stempel.create') }}"
           class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Tambah Stempel Pertama
        </a>
    </div>
@endforelse
@endsection
