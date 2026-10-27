@extends('layouts.app')

@php
    $isEdit = $blangko->exists;
    $action = $isEdit ? route('blangko.update', $blangko) : route('blangko.store');
    $mode = old('mode', $blangko->mode ?: 'canvas');
@endphp

@section('title', $isEdit ? 'Ubah Blangko' : 'Tambah Blangko')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">{{ $isEdit ? 'Ubah Blangko' : 'Tambah Blangko' }}</h1>
    <p class="text-sm text-slate-500">Pilih cara membuat blangko: <strong>desainer kanvas</strong> (gambar + teks) atau <strong>file Blade</strong>.</p>
</div>

<div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="nama" class="mb-1 block text-sm font-medium text-slate-700">Nama Blangko <span class="text-rose-500">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $blangko->nama) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        {{-- Mode pembuatan blangko --}}
        <div>
            <span class="mb-2 block text-sm font-medium text-slate-700">Cara Membuat <span class="text-rose-500">*</span></span>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-300 p-4 hover:bg-slate-50 has-checked:border-indigo-500 has-checked:bg-indigo-50">
                    <input type="radio" name="mode" value="canvas" @checked($mode === 'canvas')
                           class="mt-1 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Desainer Kanvas</span>
                        <span class="mt-1 block text-xs text-slate-500">
                            Unggah gambar blangko, lalu letakkan kotak teks dengan drag &amp; drop.
                            Cocok untuk blangko hasil scan/foto.
                        </span>
                    </span>
                </label>

                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-300 p-4 hover:bg-slate-50 has-checked:border-indigo-500 has-checked:bg-indigo-50">
                    <input type="radio" name="mode" value="blade" @checked($mode === 'blade')
                           class="mt-1 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">File Blade</span>
                        <span class="mt-1 block text-xs text-slate-500">
                            Pakai file <code>.blade.php</code> dari <code>resources/views/blangko/</code>.
                            Cocok untuk desain yang dibuat langsung dengan HTML/CSS.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="jenis" class="mb-1 block text-sm font-medium text-slate-700">Dipakai Untuk <span class="text-rose-500">*</span></label>
                <select id="jenis" name="jenis" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    @foreach ($pilihanJenis as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('jenis', $blangko->jenis) === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Hanya satu blangko aktif per jenis (paling baru dipakai).</p>
            </div>

            <div>
                <label for="ukuran_kertas" class="mb-1 block text-sm font-medium text-slate-700">Ukuran Kertas <span class="text-rose-500">*</span></label>
                <select id="ukuran_kertas" name="ukuran_kertas" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    @foreach ($pilihanUkuran as $nilai => $opsi)
                        <option value="{{ $nilai }}" @selected(old('ukuran_kertas', $blangko->ukuran_kertas ?: 'a4') === $nilai)>{{ $opsi['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Ukuran cetak mengikuti kertas + orientasi.</p>
            </div>

            <div>
                <label for="orientasi" class="mb-1 block text-sm font-medium text-slate-700">Orientasi <span class="text-rose-500">*</span></label>
                <select id="orientasi" name="orientasi" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    <option value="landscape" @selected(old('orientasi', $blangko->orientasi) === 'landscape')>Landscape (melebar)</option>
                    <option value="portrait" @selected(old('orientasi', $blangko->orientasi) === 'portrait')>Portrait (menjulang)</option>
                </select>
            </div>

            <div class="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <span class="block text-xs font-medium text-slate-500">Ukuran cetak</span>
                <span id="info-ukuran" class="text-sm font-semibold text-slate-900">{{ $blangko->lebar_mm }} × {{ $blangko->tinggi_mm }} mm</span>
                <span class="ml-2 text-xs text-slate-400">— otomatis; pilih Custom untuk isi manual.</span>
            </div>

            <div>
                <label for="lebar_mm" class="mb-1 block text-sm font-medium text-slate-700">Lebar (mm)</label>
                <input type="number" id="lebar_mm" name="lebar_mm" value="{{ old('lebar_mm', $blangko->lebar_mm) }}" min="50" max="600" readonly
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm read-only:bg-slate-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>

            <div>
                <label for="tinggi_mm" class="mb-1 block text-sm font-medium text-slate-700">Tinggi (mm)</label>
                <input type="number" id="tinggi_mm" name="tinggi_mm" value="{{ old('tinggi_mm', $blangko->tinggi_mm) }}" min="50" max="600" readonly
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm read-only:bg-slate-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>
        </div>

        {{-- Mode kanvas --}}
        <div id="blok-canvas" class="space-y-2">
            <label for="gambar" class="mb-1 block text-sm font-medium text-slate-700">Gambar Blangko</label>
            <input type="file" id="gambar" name="gambar" accept="image/*"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
            @if ($blangko->urlGambar())
                <img src="{{ $blangko->urlGambar() }}" alt="" class="mt-2 max-h-40 rounded border border-slate-200 bg-white p-1">
            @endif
            <p class="text-xs text-slate-400">PNG/JPG/WebP, maksimal 4 MB. Sebaiknya resolusi 300 dpi pada ukuran A4.</p>
        </div>

        {{-- Mode blade --}}
        <div id="blok-blade" class="space-y-2">
            <label for="view_blade" class="mb-1 block text-sm font-medium text-slate-700">File Template Blade</label>
            <select id="view_blade" name="view_blade"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                <option value="">— Pilih file template —</option>
                @forelse ($pilihanTemplate as $view => $label)
                    <option value="{{ $view }}" @selected(old('view_blade', $blangko->view_blade) === $view)>{{ $label }}</option>
                @empty
                    <option value="" disabled>Belum ada file .blade.php di resources/views/blangko/</option>
                @endforelse
            </select>
            <p class="text-xs text-slate-400">
                Folder: <code>resources/views/blangko/</code>. Template menerima variabel
                <code>$data</code>, <code>$kop</code>, <code>$varianTeks</code>, <code>$baris</code> (SK), dan <code>$blangko</code>.
            </p>
        </div>

        @if ($isEdit)
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $blangko->aktif))
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Blangko ini dipakai saat pencetakan
            </label>
        @endif

        <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Blangko' }}
            </button>
            <a href="{{ $isEdit ? route('blangko.edit', $blangko) : route('blangko.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const radios = document.querySelectorAll('input[name="mode"]');
        const blokCanvas = document.getElementById('blok-canvas');
        const blokBlade = document.getElementById('blok-blade');

        function perbarui() {
            const mode = document.querySelector('input[name="mode"]:checked')?.value || 'canvas';

            blokCanvas.classList.toggle('hidden', mode !== 'canvas');
            blokBlade.classList.toggle('hidden', mode !== 'blade');
        }

        radios.forEach((radio) => radio.addEventListener('change', perbarui));
        perbarui();

        // --- Ukuran kertas: dimensi mm otomatis mengikuti preset + orientasi ---
        const presetUkuran = {
            @foreach ($pilihanUkuran as $nilai => $opsi)
                @if ($opsi['lebar'] !== null)
                    '{{ $nilai }}': [{{ $opsi['lebar'] }}, {{ $opsi['tinggi'] }}],
                @endif
            @endforeach
        };

        const ukuran = document.getElementById('ukuran_kertas');
        const orientasi = document.getElementById('orientasi');
        const lebar = document.getElementById('lebar_mm');
        const tinggi = document.getElementById('tinggi_mm');
        const info = document.getElementById('info-ukuran');

        function syncUkuran() {
            const preset = presetUkuran[ukuran.value];

            if (!preset) {
                lebar.readOnly = false;
                tinggi.readOnly = false;
                info.textContent = `${lebar.value || 0} × ${tinggi.value || 0} mm`;

                return;
            }

            const landscape = orientasi.value === 'landscape';
            const [w, h] = preset;

            lebar.value = landscape ? h : w;
            tinggi.value = landscape ? w : h;
            lebar.readOnly = true;
            tinggi.readOnly = true;
            info.textContent = `${lebar.value} × ${tinggi.value} mm`;
        }

        ukuran.addEventListener('change', syncUkuran);
        orientasi.addEventListener('change', syncUkuran);
        lebar.addEventListener('input', syncUkuran);
        tinggi.addEventListener('input', syncUkuran);
        syncUkuran();
    });
</script>
@endsection
