@extends('layouts.app')

@section('title', 'Peserta & Juara')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Peserta &amp; Juara</h1>
        <p class="text-sm text-slate-500">Kelola peserta, kategori, dan keterangan juara.</p>
    </div>
    <a href="{{ route('peserta.create') }}"
       class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        + Tambah Peserta
    </a>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('peserta.index') }}"
      class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
    <div class="lg:col-span-2">
        <label for="q" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Cari Nama</label>
        <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama peserta…"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
    </div>

    <div>
        <label for="kontingen_id" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Kontingen</label>
        <select id="kontingen_id" name="kontingen_id"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <option value="">Semua</option>
            @foreach ($kontingens as $kontingen)
                <option value="{{ $kontingen->id }}" @selected($filters['kontingen_id'] === $kontingen->id)>{{ $kontingen->nama }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="kategori" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Kategori</label>
        <select id="kategori" name="kategori"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <option value="">Semua</option>
            @foreach ($kategoriList as $kategori)
                <option value="{{ $kategori }}" @selected($filters['kategori'] === $kategori)>{{ $kategori }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="kelompok_usia" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Kelompok Usia</label>
        <select id="kelompok_usia" name="kelompok_usia"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <option value="">Semua</option>
            @foreach ($kelompokUsiaList as $kelompokUsia)
                <option value="{{ $kelompokUsia }}" @selected($filters['kelompok_usia'] === $kelompokUsia)>{{ $kelompokUsia }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="juara" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Juara</label>
        <select id="juara" name="juara"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <option value="">Semua</option>
            <option value="1" @selected($filters['juara'] === '1')>Juara 1 (Emas)</option>
            <option value="2" @selected($filters['juara'] === '2')>Juara 2 (Perak)</option>
            <option value="3" @selected($filters['juara'] === '3')>Juara 3 (Perunggu)</option>
            @foreach ($juaraList as $juara)
                @continue(in_array((string) $juara, ['1', '2', '3'], true))
                <option value="{{ $juara }}" @selected($filters['juara'] === (string) $juara)>
                    {{ \App\Models\Peserta::labelJuaraValue($juara) }}
                </option>
            @endforeach
            <option value="-" @selected($filters['juara'] === '-')>Belum Juara</option>
        </select>
    </div>

    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Terapkan</button>
        <a href="{{ route('peserta.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>
        <span class="ml-auto text-xs text-slate-400">Total: {{ $peserta->total() }} peserta</span>
    </div>
</form>

{{-- Tabel --}}
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="w-10 px-4 py-3">
                    <input type="checkbox" id="select-all" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           title="Pilih semua di halaman ini">
                </th>
                <th class="px-4 py-3 font-semibold">No</th>
                <th class="px-4 py-3 font-semibold">Nama</th>
                <th class="px-4 py-3 font-semibold">Kontingen</th>
                <th class="px-4 py-3 font-semibold">Kategori</th>
                <th class="px-4 py-3 font-semibold">Kelas</th>
                <th class="px-4 py-3 text-center font-semibold">Juara</th>
                <th class="px-4 py-3 font-semibold">No. Sertifikat</th>
                <th class="px-4 py-3 font-semibold">No. SK</th>
                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($peserta as $index => $row)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="ids[]" value="{{ $row->id }}" form="form-cetak"
                               class="peserta-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $peserta->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->nama }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->kontingen?->nama }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->kategori }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->kelas ?: '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $badge = match ((string) $row->juara) {
                                '1' => 'bg-amber-100 text-amber-800 ring-amber-200',
                                '2' => 'bg-slate-200 text-slate-700 ring-slate-300',
                                '3' => 'bg-orange-100 text-orange-800 ring-orange-200',
                                default => $row->juara
                                    ? 'bg-indigo-50 text-indigo-700 ring-indigo-200'
                                    : 'bg-slate-50 text-slate-500 ring-slate-200',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badge }}">
                            {{ $row->labelJuara() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->no_sertifikat ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $row->no_sk ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @if ($row->bolehCetakSertifikatPublik())
                                <a href="{{ route('cetak.peserta.sertifikat', ['ids' => [$row->id]]) }}" target="_blank"
                                   data-cetak-sertifikat
                                   class="rounded-lg border border-indigo-300 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                    Cetak
                                </a>
                            @endif
                            <a href="{{ route('peserta.edit', $row) }}"
                               class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Ubah
                            </a>
                            <form method="POST" action="{{ route('peserta.destroy', $row) }}"
                                  data-confirm="Hapus peserta &quot;{{ $row->nama }}&quot;?">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500">
                        Belum ada peserta yang cocok dengan filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Aksi cetak (satuan & bulk) --}}
<form id="form-cetak" method="GET" action="{{ route('cetak.peserta.sertifikat') }}" target="_blank"
      class="mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <span id="pilihan-info" class="text-sm font-medium text-slate-600">0 peserta dipilih</span>
    <button type="submit" formaction="{{ route('cetak.peserta.sertifikat') }}"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        Cetak Sertifikat
    </button>
    <button type="submit" formaction="{{ route('cetak.peserta.sk') }}"
            class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
        Cetak SK Juara
    </button>
    <div class="flex items-center gap-2">
        <label for="pakai_background" class="text-sm font-medium text-slate-600">Background sertifikat</label>
        <select id="pakai_background" name="pakai_background"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <option value="1" selected>Dengan background</option>
            <option value="0">Tanpa background</option>
        </select>
    </div>
    <span class="text-xs text-slate-400">Centang peserta pada tabel di atas · maksimal 50 peserta per PDF.</span>
    <span class="text-xs text-slate-400">Label Peserta tetap bisa dicetak tanpa nomor sertifikat.</span>
    <span class="text-xs text-slate-400">SK tanpa No. SK tetap bisa dicetak dengan nomor kosong.</span>
    <span class="text-xs text-slate-400">Tamu selalu mencetak dengan background.</span>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pilihanBackground = document.getElementById('pakai_background');

    if (! pilihanBackground) {
        return;
    }

    const terapkanPilihanBackground = () => {
        document.querySelectorAll('a[data-cetak-sertifikat]').forEach((tautan) => {
            if (! tautan.dataset.hrefAsli) {
                tautan.dataset.hrefAsli = tautan.href;
            }

            const url = new URL(tautan.dataset.hrefAsli, window.location.origin);
            url.searchParams.set('pakai_background', pilihanBackground.value);
            tautan.href = url.toString();
        });
    };

    pilihanBackground.addEventListener('change', terapkanPilihanBackground);
    terapkanPilihanBackground();
});
</script>

<div class="mt-4">{{ $peserta->links() }}</div>
@endsection
