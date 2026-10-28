@extends('layouts.app')

@section('title', 'Legalisir Sertifikat')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Legalisir Sertifikat</h1>
        <p class="text-sm text-slate-500">
            Cetak sertifikat peserta hitam putih dengan satu stempel legalisir.
            Jumlah copy mengikuti Pengaturan.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('legalisir.stempel.index') }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Kelola Stempel
        </a>
        <a href="{{ route('peserta.index') }}"
           class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
            Pilih Peserta
        </a>
    </div>
</div>

@if ($terlaluBanyak)
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Pilihan melebihi batas {{ $maksPeserta }} peserta. Hanya {{ $maksPeserta }} peserta pertama yang ditampilkan.
    </div>
@endif

@if ($pesertas->isEmpty())
    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <p class="text-lg font-semibold text-slate-800">Belum ada peserta yang dipilih</p>
        <p class="mt-1 text-sm text-slate-500">
            Buka halaman <strong>Peserta &amp; Juara</strong>, centang peserta yang akan dilegalisir,
            lalu klik tombol <strong>Legalisir</strong>.
        </p>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            <a href="{{ route('peserta.index') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Buka Peserta &amp; Juara
            </a>
            <a href="{{ route('legalisir.stempel.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Kelola Stempel ({{ $stempels->count() }} aktif)
            </a>
        </div>
        <p class="mt-4 text-xs text-slate-400">
            Jumlah copy saat ini: {{ $jumlahCopy }} per sertifikat. Batas {{ $maksPeserta }} peserta
            dan {{ $maksHalaman }} halaman per PDF.
        </p>
    </div>
@else
    @php
        $tombolNonaktif = $stempels->isEmpty() || $pesertaValid->isEmpty() || $terlaluBanyakHalaman;
    @endphp

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Peserta Dipilih</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $pesertas->count() }}</p>
            <p class="text-xs text-slate-400">{{ $pesertaValid->count() }} siap legalisir</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jumlah Copy</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $jumlahCopy }}×</p>
            <a href="{{ route('pengaturan.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                Ubah di Pengaturan
            </a>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Halaman</p>
            <p class="mt-1 text-2xl font-bold {{ $terlaluBanyakHalaman ? 'text-rose-600' : 'text-slate-900' }}">
                {{ $totalHalaman }}
            </p>
            <p class="text-xs text-slate-400">Maksimal {{ $maksHalaman }} halaman/PDF</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Blangko Peserta</p>
            <p class="mt-1 truncate text-sm font-semibold text-slate-900">
                {{ $blangko?->nama ?? 'Template bawaan' }}
            </p>
            <p class="text-xs text-slate-400">{{ $pageWidth }} × {{ $pageHeight }} mm</p>
        </div>
    </div>

    @if ($terlaluBanyakHalaman)
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            Total halaman {{ $totalHalaman }} melebihi batas {{ $maksHalaman }}. Kurangi peserta
            atau jumlah copy di Pengaturan.
        </div>
    @endif

    @if ($pesertaValid->isEmpty())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            Semua peserta yang dipilih belum memiliki keterangan juara, sehingga belum bisa dilegalisir.
        </div>
    @elseif ($pesertaValid->count() < $pesertas->count())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $pesertas->count() - $pesertaValid->count() }} peserta tanpa keterangan juara akan dilewati.
        </div>
    @endif

    @if ($stempels->isEmpty())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Belum ada stempel legalisir aktif.
            <a href="{{ route('legalisir.stempel.create') }}" class="font-semibold underline">Tambah stempel</a>
            terlebih dahulu.
        </div>
    @endif

    <form method="GET" action="{{ route('legalisir.cetak') }}" target="_blank" class="space-y-5">
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" id="legalisir-select-all" checked
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   title="Pilih semua peserta">
                        </th>
                        <th class="px-4 py-3 font-semibold">Nama</th>
                        <th class="px-4 py-3 font-semibold">Kontingen</th>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 text-center font-semibold">Juara</th>
                        <th class="px-4 py-3 font-semibold">No. Sertifikat</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($pesertas as $peserta)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $peserta->id }}" checked
                                       class="legalisir-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $peserta->nama }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $peserta->kontingen?->nama ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $peserta->kategori }}{{ $peserta->kelas ? ' — '.$peserta->kelas : '' }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $peserta->labelJuara() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $peserta->no_sertifikat ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($peserta->bolehCetakSertifikatPublik())
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        Siap
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-500 ring-1 ring-inset ring-slate-200">
                                        Dilewati
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-slate-900">Pilih Stempel Legalisir</h2>
                    <p class="text-xs text-slate-500">Satu sertifikat memakai tepat satu stempel. Posisi stempel mengikuti desain.</p>
                </div>
                <a href="{{ route('legalisir.stempel.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Kelola stempel →
                </a>
            </div>

            @if ($stempels->isEmpty())
                <p class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                    Belum ada stempel aktif. Tambahkan stempel terlebih dahulu.
                </p>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($stempels as $stempel)
                        <label class="group flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 hover:border-indigo-300 hover:bg-indigo-50/40">
                            <input type="radio" name="stempel_id" value="{{ $stempel->id }}"
                                   @checked($loop->first) required
                                   class="rounded-full border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                @if ($stempel->urlGambar())
                                    <img src="{{ $stempel->urlGambar() }}" alt="{{ $stempel->nama }}" class="h-full w-full object-contain">
                                @else
                                    <span class="text-[10px] text-slate-400">Tanpa gambar</span>
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $stempel->nama }}</span>
                                <span class="block text-xs text-slate-500">{{ $stempel->labelPosisi() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <button type="submit"
                    @disabled($tombolNonaktif)
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                Cetak Legalisir ({{ $jumlahCopy }} copy)
            </button>
            <span class="text-sm text-slate-500">
                PDF dibuka di tab baru. Sertifikat dicetak hitam putih dan diberi 1 stempel.
            </span>
            <span class="ml-auto text-xs text-slate-400">
                {{ $pesertaValid->count() }} peserta × {{ $jumlahCopy }} copy = {{ $totalHalaman }} halaman
            </span>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('legalisir-select-all');

            if (! selectAll) {
                return;
            }

            const checkboxes = () => document.querySelectorAll('.legalisir-checkbox');

            selectAll.addEventListener('change', () => {
                checkboxes().forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });
        });
    </script>
@endif
@endsection
