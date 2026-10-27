@extends('layouts.app')

@section('title', 'Pengaturan Dokumen')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Pengaturan Dokumen</h1>
    <p class="text-sm text-slate-500">Identitas kejuaraan ini dipakai pada seluruh sertifikat dan SK.</p>
</div>

<form method="POST" action="{{ route('pengaturan.update') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Identitas --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-900">Identitas Kejuaraan</h2>

            <div class="space-y-4">
                <div>
                    <label for="nama_kejuaraan" class="mb-1 block text-sm font-medium text-slate-700">Nama Kejuaraan <span class="text-rose-500">*</span></label>
                    <input type="text" id="nama_kejuaraan" name="nama_kejuaraan" value="{{ old('nama_kejuaraan', $values['nama_kejuaraan']) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>

                <div>
                    <label for="singkatan_kejuaraan" class="mb-1 block text-sm font-medium text-slate-700">Singkatan</label>
                    <input type="text" id="singkatan_kejuaraan" name="singkatan_kejuaraan" value="{{ old('singkatan_kejuaraan', $values['singkatan_kejuaraan']) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>

                <div>
                    <label for="penyelenggara" class="mb-1 block text-sm font-medium text-slate-700">Penyelenggara</label>
                    <input type="text" id="penyelenggara" name="penyelenggara" value="{{ old('penyelenggara', $values['penyelenggara']) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>

                <div>
                    <label for="tempat_tanggal" class="mb-1 block text-sm font-medium text-slate-700">Tempat &amp; Tanggal</label>
                    <input type="text" id="tempat_tanggal" name="tempat_tanggal" value="{{ old('tempat_tanggal', $values['tempat_tanggal']) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>

                <div>
                    <label for="kota_ttd" class="mb-1 block text-sm font-medium text-slate-700">Kota Penandatangan</label>
                    <input type="text" id="kota_ttd" name="kota_ttd" value="{{ old('kota_ttd', $values['kota_ttd']) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>

                <div>
                    <label for="varian_pemasalan" class="mb-1 block text-sm font-medium text-slate-700">Kelompok Usia → Sertifikat Pemasalan</label>
                    <input type="text" id="varian_pemasalan" name="varian_pemasalan" value="{{ old('varian_pemasalan', $values['varian_pemasalan']) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    <p class="mt-1 text-xs text-slate-400">
                        Daftar kelompok usia (dipisah koma) yang memakai varian teks <strong>Pemasalan</strong>;
                        kelompok usia lainnya memakai varian <strong>Prestasi</strong>.
                    </p>
                </div>
            </div>
        </div>

        {{-- Tanda tangan --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-900">Penandatangan</h2>

            <div class="space-y-4">
                <div>
                    <label for="nama_ttd" class="mb-1 block text-sm font-medium text-slate-700">Nama Penandatangan</label>
                    <input type="text" id="nama_ttd" name="nama_ttd" value="{{ old('nama_ttd', $values['nama_ttd']) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>

                <div>
                    <label for="jabatan_ttd" class="mb-1 block text-sm font-medium text-slate-700">Jabatan</label>
                    <input type="text" id="jabatan_ttd" name="jabatan_ttd" value="{{ old('jabatan_ttd', $values['jabatan_ttd']) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>

                @foreach (['logo_kiri' => 'Logo Kiri', 'logo_kanan' => 'Logo Kanan', 'background_sertifikat' => 'Background Sertifikat'] as $key => $label)
                    <div>
                        <label for="{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
                        <input type="file" id="{{ $key }}" name="{{ $key }}" accept="image/*"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                        @if (! empty($values[$key]))
                            <img src="{{ $urlGambar($values[$key]) }}" alt="{{ $label }}" class="mt-2 h-16 w-auto rounded border border-slate-200 bg-white p-1">
                        @endif
                        <p class="mt-1 text-xs text-slate-400">jpg/png/webp, maksimal 2 MB. Unggahan baru akan menggantikan yang lama.</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Template teks --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="mb-1 font-semibold text-slate-900">Template Teks Dokumen</h2>
            <p class="mb-4 text-xs text-slate-500">
                Placeholder tersedia: <code class="rounded bg-slate-100 px-1">{NAMA}</code>,
                <code class="rounded bg-slate-100 px-1">{NOMOR_SERTIFIKAT}</code>,
                <code class="rounded bg-slate-100 px-1">{JUARA_ANGKA}</code>,
                <code class="rounded bg-slate-100 px-1">{JUARA_ROMAWI}</code>,
                <code class="rounded bg-slate-100 px-1">{KATEGORI}</code>,
                <code class="rounded bg-slate-100 px-1">{KELAS}</code>,
                <code class="rounded bg-slate-100 px-1">{KONTINGEN}</code>,
                <code class="rounded bg-slate-100 px-1">{JABATAN}</code>
            </p>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label for="teks_sertifikat" class="mb-1 block text-sm font-medium text-slate-700">Teks Sertifikat Peserta</label>
                    <textarea id="teks_sertifikat" name="teks_sertifikat" rows="4"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">{{ old('teks_sertifikat', $values['teks_sertifikat']) }}</textarea>
                </div>

                <div>
                    <label for="teks_sertifikat_official" class="mb-1 block text-sm font-medium text-slate-700">Teks Sertifikat Official</label>
                    <textarea id="teks_sertifikat_official" name="teks_sertifikat_official" rows="4"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">{{ old('teks_sertifikat_official', $values['teks_sertifikat_official']) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
            Simpan Pengaturan
        </button>
        <span class="text-xs text-slate-400">Perubahan langsung dipakai pada cetak berikutnya.</span>
    </div>
</form>
@endsection
