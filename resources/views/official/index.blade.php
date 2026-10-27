@extends('layouts.app')

@section('title', 'Official & Coach')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Official &amp; Coach</h1>
    <p class="text-sm text-slate-500">Ketik nama, pilih kontingen dari daftar, lalu cetak sertifikatnya.</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Form input --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
        <h2 class="mb-4 font-semibold text-slate-900">Tambah Official</h2>

        @if ($kontingens->isEmpty())
            <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Belum ada kontingen terdaftar. <a class="underline" href="{{ route('kontingen.create') }}">Tambah kontingen dulu</a>.
            </p>
        @endif

        <form method="POST" action="{{ route('official.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="nama" class="mb-1 block text-sm font-medium text-slate-700">Nama <span class="text-rose-500">*</span></label>
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
                Simpan Official
            </button>
        </form>
    </div>

    {{-- Daftar --}}
    <div class="lg:col-span-2">
        <div class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <label for="pakai_background_official" class="text-sm font-medium text-slate-600">Background sertifikat</label>
            <select id="pakai_background_official"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                <option value="1" selected>Dengan background</option>
                <option value="0">Tanpa background</option>
            </select>
            <span class="text-xs text-slate-400">Tamu selalu mencetak dengan background.</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama</th>
                        <th class="px-4 py-3 font-semibold">Kontingen</th>
                        <th class="px-4 py-3 font-semibold">Jabatan</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($officials as $official)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $official->nama }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $official->kontingen?->nama }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $official->jabatan }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('cetak.official.sertifikat', $official) }}" target="_blank"
                                       data-cetak-official
                                       class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Cetak
                                    </a>
                                    <form method="POST" action="{{ route('official.destroy', $official) }}"
                                          data-confirm="Hapus official &quot;{{ $official->nama }}&quot;?">
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
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada official. Isi form di samping untuk menambah data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $officials->links() }}</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pilihanBackground = document.getElementById('pakai_background_official');

    if (! pilihanBackground) {
        return;
    }

    const terapkanPilihanBackground = () => {
        document.querySelectorAll('a[data-cetak-official]').forEach((tautan) => {
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
@endsection
