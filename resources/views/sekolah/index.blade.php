@extends('layouts.app')

@section('title', 'Sertifikat Sekolah')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Sertifikat Sekolah</h1>
        <p class="text-sm text-slate-500">
            Cetak sertifikat ucapan terima kasih untuk sekolah yang telah berpartisipasi dalam kejuaraan.
            Hanya admin yang dapat menambah sekolah dan mencetak sertifikat.
        </p>
    </div>
</div>

@if ($blangkoSekolah)
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        Blangko aktif: <strong>{{ $blangkoSekolah->nama }}</strong>
        <a href="{{ route('blangko.edit', $blangkoSekolah) }}" class="ml-2 font-semibold underline hover:no-underline">Ubah blangko</a>
    </div>
@else
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Belum ada blangko khusus <strong>Sertifikat Sekolah</strong>. Cetak akan memakai template bawaan.
        <a href="{{ route('blangko.create', ['jenis' => 'sertifikat_sekolah', 'mode' => 'blade', 'view_blade' => 'blangko.contoh-sekolah-sbcc']) }}" class="ml-1 font-semibold underline hover:no-underline">Buat blangko sekolah</a>
    </div>
@endif

<div class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="POST" action="{{ route('sekolah.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label for="nama" class="mb-1 block text-sm font-medium text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required maxlength="150"
                   placeholder="Contoh: SMA Negeri 1 Sumbermaneg"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Tambah Sekolah
        </button>
    </form>
</div>

<div class="mb-3 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" action="{{ route('sekolah.index') }}" class="flex items-center gap-2">
        <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama sekolah..."
               class="w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cari</button>
        @if ($q !== '')
            <a href="{{ route('sekolah.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
        @endif
    </form>
    <p class="text-sm text-slate-500">Total {{ $sekolahs->total() }} sekolah</p>
</div>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 font-semibold">No</th>
                <th class="px-4 py-3 font-semibold">Nama Sekolah</th>
                <th class="px-4 py-3 font-semibold">Status Cetak</th>
                <th class="px-4 py-3 text-center font-semibold">Jumlah Cetak</th>
                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($sekolahs as $sekolah)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-500">
                        {{ ($sekolahs->currentPage() - 1) * $sekolahs->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $sekolah->nama }}</td>
                    <td class="px-4 py-3">
                        @if ($sekolah->terakhir_dicetak_at)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200">
                                Sudah dicetak
                            </span>
                            <span class="ml-1 text-xs text-slate-500">{{ $sekolah->terakhir_dicetak_at->format('d-m-Y H:i') }}</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-inset ring-slate-200">
                                Belum pernah dicetak
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $sekolah->jumlah_cetak }}×</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('cetak.sekolah.sertifikat', $sekolah) }}" target="_blank" rel="noopener"
                           class="inline-flex rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                            Cetak Sertifikat
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                        @if ($q !== '')
                            Tidak ada sekolah yang cocok dengan pencarian "{{ $q }}".
                        @else
                            Belum ada sekolah. Tambahkan nama sekolah di atas untuk mulai mencetak.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $sekolahs->links() }}</div>
@endsection
