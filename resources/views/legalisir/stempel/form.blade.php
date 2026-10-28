@extends('layouts.app')

@section('title', $stempel ? 'Ubah Stempel Legalisir' : 'Tambah Stempel Legalisir')

@section('content')
@php
    $nilaiX = old('x_mm', $stempel?->x_mm ?? $defaultX);
    $nilaiY = old('y_mm', $stempel?->y_mm ?? $defaultY);
    $nilaiLebar = old('lebar_mm', $stempel?->lebar_mm ?? 40);
    $nilaiTinggi = old('tinggi_mm', $stempel?->tinggi_mm ?? 40);
    $persenKiri = $pageWidth > 0 ? ((float) $nilaiX / $pageWidth) * 100 : 0;
    $persenAtas = $pageHeight > 0 ? ((float) $nilaiY / $pageHeight) * 100 : 0;
    $persenLebar = $pageWidth > 0 ? ((float) $nilaiLebar / $pageWidth) * 100 : 0;
    $persenTinggi = $pageHeight > 0 ? ((float) $nilaiTinggi / $pageHeight) * 100 : 0;
@endphp

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $stempel ? 'Ubah Stempel Legalisir' : 'Tambah Stempel Legalisir' }}</h1>
        <p class="text-sm text-slate-500">
            Satu desain stempel memiliki satu posisi. Posisi dihitung dari sudut kiri atas halaman.
        </p>
    </div>
    <a href="{{ route('legalisir.stempel.index') }}"
       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Kembali
    </a>
</div>

<form method="POST"
      action="{{ $stempel ? route('legalisir.stempel.update', $stempel) : route('legalisir.stempel.store') }}"
      enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-2">
    @csrf
    @if ($stempel)
        @method('PUT')
    @endif

    <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="nama" class="mb-1 block text-sm font-medium text-slate-700">Nama Stempel <span class="text-rose-500">*</span></label>
            <input type="text" id="nama" name="nama" value="{{ old('nama', $stempel?->nama) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <div>
            <label for="gambar" class="mb-1 block text-sm font-medium text-slate-700">
                Gambar Stempel @if (! $stempel)<span class="text-rose-500">*</span>@endif
            </label>
            <input type="file" id="gambar" name="gambar" accept="image/png,image/jpeg,image/webp"
                   @required(! $stempel)
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
            <p class="mt-1 text-xs text-slate-400">
                Disarankan PNG transparan, maksimal 4 MB. Warna stempel tidak diubah saat cetak hitam putih.
            </p>
            @if ($stempel?->urlGambar())
                <img src="{{ $stempel->urlGambar() }}" alt="{{ $stempel->nama }}" class="mt-2 h-20 w-auto rounded border border-slate-200 bg-slate-50 p-1">
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="x_mm" class="mb-1 block text-sm font-medium text-slate-700">Posisi X (mm)</label>
                <input type="number" id="x_mm" name="x_mm" value="{{ $nilaiX }}" min="0" max="{{ $pageWidth }}" step="0.1" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>
            <div>
                <label for="y_mm" class="mb-1 block text-sm font-medium text-slate-700">Posisi Y (mm)</label>
                <input type="number" id="y_mm" name="y_mm" value="{{ $nilaiY }}" min="0" max="{{ $pageHeight }}" step="0.1" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>
            <div>
                <label for="lebar_mm" class="mb-1 block text-sm font-medium text-slate-700">Lebar (mm)</label>
                <input type="number" id="lebar_mm" name="lebar_mm" value="{{ $nilaiLebar }}" min="5" max="{{ $pageWidth }}" step="0.1" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>
            <div>
                <label for="tinggi_mm" class="mb-1 block text-sm font-medium text-slate-700">Tinggi (mm)</label>
                <input type="number" id="tinggi_mm" name="tinggi_mm" value="{{ $nilaiTinggi }}" min="5" max="{{ $pageHeight }}" step="0.1" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $stempel?->aktif ?? true))
                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Stempel aktif dan bisa dipilih saat cetak legalisir
        </label>

        <p class="text-xs text-slate-400">
            Ukuran halaman aktif: {{ $pageWidth }} × {{ $pageHeight }} mm.
            Posisi X + lebar dan Y + tinggi tidak boleh melewati halaman.
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-1 font-semibold text-slate-900">Preview Posisi</h2>
        <p class="mb-4 text-xs text-slate-500">Geser nilai di form untuk melihat posisi stempel pada halaman.</p>

        <div class="mx-auto w-full max-w-3xl">
            <div id="halaman-preview"
                 class="relative w-full overflow-hidden rounded-lg border border-slate-300 bg-white shadow-inner"
                 style="aspect-ratio: {{ $pageWidth }} / {{ $pageHeight }}; @if ($blangko?->urlGambar()) background-image: url('{{ $blangko->urlGambar() }}'); background-size: 100% 100%; background-position: center; @endif">
                @if (! $blangko?->urlGambar())
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-slate-50 text-center">
                        <span class="text-sm font-semibold text-slate-500">
                            {{ $blangko ? 'Blangko: '.$blangko->nama : 'Template bawaan' }}
                        </span>
                        <span class="text-xs text-slate-400">{{ $pageWidth }} × {{ $pageHeight }} mm</span>
                    </div>
                @endif

                <div id="stempel-preview"
                     class="absolute flex items-center justify-center overflow-hidden rounded border-2 border-dashed border-indigo-400 bg-white/60 text-xs font-semibold text-indigo-600"
                     style="left: {{ $persenKiri }}%; top: {{ $persenAtas }}%; width: {{ $persenLebar }}%; height: {{ $persenTinggi }}%;">
                    <img id="stempel-preview-img"
                         src="{{ $stempel?->urlGambar() }}"
                         alt=""
                         class="{{ $stempel?->urlGambar() ? '' : 'hidden' }} h-full w-full object-contain">
                    <span id="stempel-preview-label" class="{{ $stempel?->urlGambar() ? 'hidden' : '' }}">Stempel</span>
                </div>
            </div>
        </div>

        <p class="mt-3 text-center text-xs text-slate-400">
            Preview bukan hasil cetak final; ukuran dan posisi mengikuti nilai dalam milimeter.
        </p>
    </div>

    <div class="flex items-center gap-3 lg:col-span-2">
        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
            Simpan Stempel
        </button>
        <a href="{{ route('legalisir.stempel.index') }}"
           class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Batal
        </a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pageWidth = {{ $pageWidth }};
    const pageHeight = {{ $pageHeight }};
    const inputX = document.getElementById('x_mm');
    const inputY = document.getElementById('y_mm');
    const inputLebar = document.getElementById('lebar_mm');
    const inputTinggi = document.getElementById('tinggi_mm');
    const preview = document.getElementById('stempel-preview');
    const image = document.getElementById('stempel-preview-img');
    const label = document.getElementById('stempel-preview-label');
    const file = document.getElementById('gambar');

    if (! preview || ! inputX || ! inputY || ! inputLebar || ! inputTinggi) {
        return;
    }

    const update = () => {
        const x = Number(inputX.value) || 0;
        const y = Number(inputY.value) || 0;
        const lebar = Number(inputLebar.value) || 0;
        const tinggi = Number(inputTinggi.value) || 0;

        preview.style.left = (x / pageWidth * 100) + '%';
        preview.style.top = (y / pageHeight * 100) + '%';
        preview.style.width = (lebar / pageWidth * 100) + '%';
        preview.style.height = (tinggi / pageHeight * 100) + '%';
    };

    [inputX, inputY, inputLebar, inputTinggi].forEach((input) => {
        input.addEventListener('input', update);
    });

    if (file) {
        file.addEventListener('change', (event) => {
            const selected = event.target.files && event.target.files[0];

            if (! selected || ! image) {
                return;
            }

            image.src = URL.createObjectURL(selected);
            image.classList.remove('hidden');
            label?.classList.add('hidden');
        });
    }

    update();
});
</script>
@endsection
