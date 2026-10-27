@extends('layouts.app')

@section('title', 'Blangko')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Blangko</h1>
        <p class="text-sm text-slate-500">
            Dua cara membuat blangko: <strong>desainer kanvas</strong> (gambar + kotak teks) atau <strong>file Blade</strong>
            dari <code>resources/views/blangko/</code>.
        </p>
    </div>
    <a href="{{ route('blangko.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        + Tambah Blangko
    </a>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($blangkos as $blangko)
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex h-40 items-center justify-center bg-slate-100">
                @if ($blangko->urlGambar())
                    <img src="{{ $blangko->urlGambar() }}" alt="{{ $blangko->nama }}" class="max-h-full max-w-full object-contain">
                @elseif ($blangko->pakaiBlade())
                    <div class="px-4 text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Template Blade</p>
                        <p class="mt-1 break-all font-mono text-xs text-slate-600">{{ $blangko->view_blade }}</p>
                    </div>
                @else
                    <span class="text-xs text-slate-400">Belum ada gambar</span>
                @endif
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $blangko->nama }}</h2>
                        <p class="text-xs text-slate-500">
                            {{ $blangko->labelJenis() }}
                            · {{ strtoupper($blangko->ukuran_kertas) }} {{ $blangko->lebar_mm }}×{{ $blangko->tinggi_mm }} mm
                            @if ($blangko->pakaiBlade())
                                · Blade
                            @else
                                · {{ $blangko->elemen_count }} elemen
                            @endif
                            · {{ $blangko->varian_count }} varian
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $blangko->aktif ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                        {{ $blangko->aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="mt-2">
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $blangko->pakaiBlade() ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-sky-50 text-sky-700 ring-sky-200' }}">
                        {{ $blangko->pakaiBlade() ? 'Mode Blade' : 'Mode Kanvas' }}
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <a href="{{ route('blangko.edit', $blangko) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                        {{ $blangko->pakaiBlade() ? 'Template Blade' : 'Desainer' }}
                    </a>
                    <a href="{{ route('blangko.preview', $blangko) }}" target="_blank" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Preview
                    </a>
                    <a href="{{ route('blangko.form', $blangko) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Pengaturan
                    </a>
                    <form method="POST" action="{{ route('blangko.destroy', $blangko) }}" data-confirm="Hapus blangko &quot;{{ $blangko->nama }}&quot; beserta semua elemennya?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-sm text-slate-500">
                Belum ada blangko. Buat blangko dari gambar (kanvas) atau pilih file Blade yang sudah disiapkan.
            </p>
        </div>
    @endforelse
</div>
@endsection
