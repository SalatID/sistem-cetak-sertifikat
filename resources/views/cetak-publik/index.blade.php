@extends('layouts.publik')

@section('title', 'Cari & Cetak Sertifikat')
@section('judul', 'Cari Peserta & Cetak Sertifikat')

@section('content')
<div class="mb-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="font-semibold text-slate-900">Cetak Sertifikat Anda</h2>
    <p class="mt-1 text-sm text-slate-500">
        Cari nama Anda pada daftar peserta, lalu cetak sertifikat (dan SK) langsung ke PDF.
        Untuk sertifikat coach/pelatih, gunakan menu <span class="font-medium">Sertifikat Coach/Official</span>.
    </p>
    <p class="mt-1 text-xs text-slate-400">
        Sertifikat dapat dicetak untuk semua peserta yang memiliki keterangan juara, termasuk Peserta.
        Nomor sertifikat dari data hanya diberikan kepada Juara 1, Juara 2, Juara 3, dan Pesilat Terbaik.
    </p>

    <form method="GET" action="{{ route('publik.index') }}" class="mt-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ $cari }}" placeholder="Ketik nama Anda…"
               class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Cari</button>
        @if ($cari !== '')
            <a href="{{ route('publik.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>
        @endif
    </form>
</div>

<div class="mb-2 flex items-center justify-between gap-2 sm:hidden">
    <p class="text-xs text-slate-500">Geser tabel ke samping untuk melihat semua kolom.</p>
</div>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm [-webkit-overflow-scrolling:touch]">
    <table class="w-full min-w-[860px] divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">No</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kontingen</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kategori</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kelompok Usia</th>
                <th class="whitespace-nowrap px-4 py-3 text-center font-semibold">Juara</th>
                <th class="whitespace-nowrap px-4 py-3 text-right font-semibold">Cetak</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($peserta as $index => $row)
                @php
                    $juara = strtolower(trim((string) $row->juara));
                    $badge = match (true) {
                        in_array($juara, ['1', 'juara 1', 'emas'], true) => 'bg-amber-100 text-amber-800 ring-amber-200',
                        in_array($juara, ['2', 'juara 2', 'perak'], true) => 'bg-slate-200 text-slate-700 ring-slate-300',
                        in_array($juara, ['3', 'juara 3', 'perunggu'], true) => 'bg-orange-100 text-orange-800 ring-orange-200',
                        $juara === 'peserta' => 'bg-white text-slate-700 ring-slate-300',
                        $juara === 'pesilat terbaik' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                        default => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                    };
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $peserta->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->nama }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->kontingen?->nama }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->kategori }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $row->kelompok_usia ?: '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-center">
                        @if ($row->juara)
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badge }}">
                                {{ $row->labelJuara() }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @if ($row->bolehCetakSertifikatPublik())
                                <a href="{{ route('publik.sertifikat', $row) }}" target="_blank"
                                   class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                    Sertifikat
                                </a>
                            @endif
                            @if ($row->juara || $row->no_sk)
                                <a href="{{ route('publik.sk', $row) }}" target="_blank"
                                   title="{{ $row->no_sk ? 'Cetak SK' : 'Nomor SK belum diisi; SK akan tercetak tanpa nomor' }}"
                                   class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $row->no_sk ? 'border-slate-300 text-slate-700 hover:bg-slate-50' : 'border-amber-300 text-amber-700 hover:bg-amber-50' }}">
                                    SK
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                        @if ($cari !== '')
                            Tidak ada peserta dengan nama “{{ $cari }}”.
                        @else
                            Belum ada data peserta.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $peserta->links() }}</div>
@endsection
