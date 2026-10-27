@extends('layouts.app')

@section('title', 'Desainer Blangko — '.$blangko->nama)

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Desainer — {{ $blangko->nama }}</h1>
        <p class="text-sm text-slate-500">
            {{ $blangko->jenis === 'sk' ? 'SK Juara' : 'Sertifikat' }} · {{ strtoupper($blangko->ukuran_kertas) }} {{ $blangko->lebar_mm }}×{{ $blangko->tinggi_mm }} mm.
            Seret kotak teks di kanvas untuk menempatkan tulisan pada blangko.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('blangko.preview', $blangko) }}" target="_blank" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Preview</a>
        <a href="{{ route('blangko.form', $blangko) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Ganti Gambar / Ukuran</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-12">
    {{-- Panel kiri: form elemen + varian --}}
    <div class="space-y-6 lg:col-span-4">

        <form id="form-elemen" method="POST" action="{{ route('blangko.elemen.simpan', $blangko) }}"
              class="space-y-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <input type="hidden" name="elemen_id" id="elemen_id" value="">

            <div class="flex items-center justify-between">
                <h2 id="judul-form-elemen" class="font-semibold text-slate-900">Elemen Baru</h2>
                <button type="button" id="btn-reset" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">+ Elemen baru</button>
            </div>

            <div>
                <label for="f_label" class="mb-1 block text-xs font-medium text-slate-600">Label (nama elemen di panel)</label>
                <input type="text" id="f_label" name="label" required placeholder="mis. Nama Peserta"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>

            <div>
                <label for="f_tipe" class="mb-1 block text-xs font-medium text-slate-600">Isi Elemen</label>
                <select id="f_tipe" name="tipe" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    <option value="data">Ambil dari data (nama peserta, kontingen, juara, …)</option>
                    <option value="teks">Teks bebas (bisa pakai {NAMA} dsb.)</option>
                    <option value="varian">Teks varian (Prestasi / Pemasalan)</option>
                    <option value="tabel">Tabel daftar juara (untuk SK)</option>
                </select>
            </div>

            <div id="wrap-sumber" class="hidden">
                <label for="f_sumber_field" class="mb-1 block text-xs font-medium text-slate-600">Sumber Data</label>
                <select id="f_sumber_field" name="sumber_field"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    @foreach ($pilihanField as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div id="wrap-isi" class="hidden">
                <label for="f_isi" class="mb-1 block text-xs font-medium text-slate-600">Teks</label>
                <textarea id="f_isi" name="isi" rows="3"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none"
                          placeholder="Contoh: Diberikan kepada {NAMA} sebagai JUARA {JUARA_ROMAWI}"></textarea>
                <p class="mt-1 text-xs text-slate-400">Placeholder yang tersedia: {NAMA}, {KONTINGEN}, {KATEGORI}, {KELAS}, {JUARA_ROMAWI}, {NO_SERTIFIKAT}, {NO_SK}, {TANGGAL}.</p>
            </div>

            <div id="wrap-varian" class="hidden">
                <label for="f_varian_key" class="mb-1 block text-xs font-medium text-slate-600">Varian Teks</label>
                <select id="f_varian_key" name="varian_key"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    @foreach ($blangko->varian as $varian)
                        <option value="{{ $varian->key }}">{{ $varian->judul }} ({{ $varian->key }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Tambah varian di panel bawah.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="f_x_mm" class="mb-1 block text-xs font-medium text-slate-600">X (mm) dari kiri</label>
                    <input type="number" step="0.5" id="f_x_mm" name="x_mm" value="20" min="0"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
                <div>
                    <label for="f_y_mm" class="mb-1 block text-xs font-medium text-slate-600">Y (mm) dari atas</label>
                    <input type="number" step="0.5" id="f_y_mm" name="y_mm" value="20" min="0"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
                <div>
                    <label for="f_lebar_mm" class="mb-1 block text-xs font-medium text-slate-600">Lebar (mm)</label>
                    <input type="number" step="0.5" id="f_lebar_mm" name="lebar_mm" value="80" min="5"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
                <div>
                    <label for="f_tinggi_mm" class="mb-1 block text-xs font-medium text-slate-600">Tinggi (mm)</label>
                    <input type="number" step="0.5" id="f_tinggi_mm" name="tinggi_mm" value="10" min="3"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="f_font_family" class="mb-1 block text-xs font-medium text-slate-600">Font</label>
                    <select id="f_font_family" name="font_family"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        @foreach ($pilihanFont as $font)
                            <option value="{{ $font }}">{{ ucfirst($font) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="f_font_size" class="mb-1 block text-xs font-medium text-slate-600">Ukuran (pt)</label>
                    <input type="number" id="f_font_size" name="font_size" value="11" min="5" max="72"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
                <div>
                    <label for="f_align" class="mb-1 block text-xs font-medium text-slate-600">Rata Horizontal</label>
                    <select id="f_align" name="align"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        <option value="left">Kiri</option>
                        <option value="center">Tengah</option>
                        <option value="right">Kanan</option>
                        <option value="justify">Rata kanan-kiri</option>
                    </select>
                </div>
                <div>
                    <label for="f_valign" class="mb-1 block text-xs font-medium text-slate-600">Rata Vertikal</label>
                    <select id="f_valign" name="valign"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        <option value="top">Atas</option>
                        <option value="middle">Tengah</option>
                        <option value="bottom">Bawah</option>
                    </select>
                </div>
                <div>
                    <label for="f_warna" class="mb-1 block text-xs font-medium text-slate-600">Warna</label>
                    <input type="color" id="f_warna" name="warna" value="#0f172a"
                           class="h-9 w-full rounded-lg border border-slate-300 px-1 py-0.5 text-sm">
                </div>
                <div>
                    <label for="f_z_index" class="mb-1 block text-xs font-medium text-slate-600">Urutan Lapisan</label>
                    <input type="number" id="f_z_index" name="z_index" value="10" min="0" max="999"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id="f_tebal" name="tebal" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Tebal
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id="f_miring" name="miring" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Miring
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id="f_tampil" name="tampil" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Tampil
                </label>
            </div>

            <div class="flex items-center gap-2 border-t border-slate-100 pt-3">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan Elemen</button>
                <button type="button" id="btn-hapus" class="hidden rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
            </div>
        </form>

        {{-- Daftar elemen: pilih untuk edit, atau hapus --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Daftar Elemen</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $blangko->elemen->count() }}</span>
            </div>

            <div class="mt-3 space-y-2">
                @forelse ($blangko->elemen as $elemen)
                    <div class="flex items-start justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2"
                         data-baris-elemen="{{ $elemen->id }}">
                        <button type="button" data-pilih-elemen="{{ $elemen->id }}" class="min-w-0 flex-1 text-left">
                            <p class="truncate text-sm font-medium text-slate-900" data-label-elemen>{{ $elemen->label }}</p>
                            <p class="text-xs text-slate-500" data-detail-elemen>
                                {{ ucfirst($elemen->tipe) }} · X {{ $elemen->x_mm }} / Y {{ $elemen->y_mm }} · {{ $elemen->lebar_mm }}×{{ $elemen->tinggi_mm }} mm
                            </p>
                        </button>
                        <div class="flex shrink-0 gap-1">
                            <button type="button" data-pilih-elemen="{{ $elemen->id }}"
                                    class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Edit</button>
                            <button type="button" data-hapus-elemen="{{ $elemen->id }}"
                                    class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada elemen. Isi form di atas lalu klik <span class="font-medium">Simpan Elemen</span>.</p>
                @endforelse
            </div>
        </div>

        {{-- Varian teks --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Varian Teks</h2>
            <p class="mt-1 text-xs text-slate-500">
                Satu blangko bisa punya beberapa versi teks. Versi dipilih otomatis dari kelompok usia peserta
                (Pengaturan → daftar kelompok usia untuk sertifikat pemasalan).
            </p>

            <div class="mt-3 space-y-2">
                @forelse ($blangko->varian as $varian)
                    <div class="flex items-start justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">{{ $varian->judul }} <span class="text-xs text-slate-400">({{ $varian->key }})</span></p>
                            <p class="truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($varian->isi, 90) }}</p>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <button type="button" class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50"
                                    data-varian-edit='@json($varian->toArray())'>Edit</button>
                            <form method="POST" action="{{ route('blangko.varian.hapus', [$blangko, $varian]) }}" data-confirm="Hapus varian ini?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50">Hapus</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada varian teks.</p>
                @endforelse
            </div>

            <form id="form-varian" method="POST" action="{{ route('blangko.varian.simpan', $blangko) }}" class="mt-4 space-y-2 border-t border-slate-100 pt-3">
                @csrf
                <input type="hidden" name="varian_id" id="varian_id" value="">

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="v_key" class="mb-1 block text-xs font-medium text-slate-600">Kunci</label>
                        <input type="text" id="v_key" name="key" required placeholder="prestasi"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    </div>
                    <div>
                        <label for="v_judul" class="mb-1 block text-xs font-medium text-slate-600">Judul</label>
                        <input type="text" id="v_judul" name="judul" required placeholder="Sertifikat Prestasi"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="v_isi" class="mb-1 block text-xs font-medium text-slate-600">Isi Teks</label>
                    <textarea id="v_isi" name="isi" rows="3" required
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Simpan Varian</button>
            </form>
        </div>
    </div>

    {{-- Panel kanan: kanvas --}}
    <div class="lg:col-span-8">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div id="kanvas" class="relative bg-white shadow"
                     style="width: {{ $blangko->lebar_mm }}mm; height: {{ $blangko->tinggi_mm }}mm;">

                    @if ($blangko->urlGambar())
                        <img src="{{ $blangko->urlGambar() }}" alt="" class="absolute left-0 top-0" style="width: 100%; height: 100%;">
                    @endif

                    @foreach ($blangko->elemen as $elemen)
                        <div class="elemen absolute border border-indigo-500 bg-indigo-400/10"
                             data-id="{{ $elemen->id }}"
                             data-x="{{ $elemen->x_mm }}" data-y="{{ $elemen->y_mm }}"
                             data-w="{{ $elemen->lebar_mm }}" data-h="{{ $elemen->tinggi_mm }}"
                             style="left: {{ $elemen->x_mm }}mm; top: {{ $elemen->y_mm }}mm; width: {{ $elemen->lebar_mm }}mm; height: {{ $elemen->tinggi_mm }}mm;">
                            <span class="label-elemen pointer-events-none absolute left-0 top-0 bg-indigo-500 px-1 text-[10px] font-semibold text-white">{{ $elemen->label }}</span>
                            <span class="handle absolute bottom-0 right-0 h-3 w-3 cursor-nwse-resize bg-indigo-500/70"></span>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="mt-3 text-xs text-slate-500">
                <span class="font-medium">Cara pakai:</span> seret kotak untuk memindahkan, seret sudut kanan-bawah untuk mengubah ukuran,
                klik kotak lalu ubah teks/format di panel kiri. Perubahan posisi tersimpan otomatis setelah dilepas.
            </p>
        </div>
    </div>
</div>

<script id="data-elemen" type="application/json">@json($blangko->elemen)</script>
<script src="{{ asset('js/blangko.js') }}"></script>
@endsection
