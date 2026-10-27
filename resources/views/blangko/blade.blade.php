@extends('layouts.app')

@section('title', 'Template Blade — '.$blangko->nama)

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-slate-900">{{ $blangko->nama }}</h1>
            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">Mode Blade</span>
        </div>
        <p class="text-sm text-slate-500">
            {{ $blangko->jenis === 'sk' ? 'SK Juara' : 'Sertifikat' }} · {{ strtoupper($blangko->ukuran_kertas) }} · {{ $blangko->lebar_mm }}×{{ $blangko->tinggi_mm }} mm
            · Template: <code class="rounded bg-slate-100 px-1">{{ $blangko->view_blade ?: '—' }}</code>
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('blangko.preview', $blangko) }}" target="_blank"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Preview</a>
        <a href="{{ route('blangko.form', $blangko) }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Pengaturan Blangko</a>
        <a href="{{ route('blangko.index') }}"
           class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Daftar Blangko</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-12">
    <div class="space-y-6 lg:col-span-5">
        <form method="POST" action="{{ route('blangko.update', $blangko) }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PUT')
            <input type="hidden" name="nama" value="{{ $blangko->nama }}">

            <h2 class="font-semibold text-slate-900">Template Blade</h2>

            <div>
                <label for="view_blade" class="mb-1 block text-xs font-medium text-slate-600">File Template</label>
                <select id="view_blade" name="view_blade"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    <option value="">— Pilih file template —</option>
                    @foreach ($pilihanTemplate as $view => $label)
                        <option value="{{ $view }}" @selected($blangko->view_blade === $view)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">
                    File dibaca dari <code>resources/views/blangko/</code>. Setelah mengubah kode template, klik Preview.
                </p>
            </div>

            <div>
                <label for="ukuran_kertas" class="mb-1 block text-xs font-medium text-slate-600">Ukuran Kertas</label>
                <select id="ukuran_kertas" name="ukuran_kertas"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    @foreach ($pilihanUkuran as $nilai => $opsi)
                        <option value="{{ $nilai }}" @selected(($blangko->ukuran_kertas ?: 'a4') === $nilai)>{{ $opsi['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Dimensi mm mengikuti ukuran + orientasi; pilih Custom untuk manual.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="jenis" class="mb-1 block text-xs font-medium text-slate-600">Jenis</label>
                    <select id="jenis" name="jenis"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        <option value="sertifikat" @selected($blangko->jenis === 'sertifikat')>Sertifikat</option>
                        <option value="sk" @selected($blangko->jenis === 'sk')>SK Juara</option>
                    </select>
                </div>
                <div>
                    <label for="orientasi" class="mb-1 block text-xs font-medium text-slate-600">Orientasi</label>
                    <select id="orientasi" name="orientasi"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        <option value="landscape" @selected($blangko->orientasi === 'landscape')>Landscape</option>
                        <option value="portrait" @selected($blangko->orientasi === 'portrait')>Portrait</option>
                    </select>
                </div>
                <div>
                    <label for="lebar_mm" class="mb-1 block text-xs font-medium text-slate-600">Lebar (mm)</label>
                    <input type="number" id="lebar_mm" name="lebar_mm" value="{{ $blangko->lebar_mm }}" min="50" max="600"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
                <div>
                    <label for="tinggi_mm" class="mb-1 block text-xs font-medium text-slate-600">Tinggi (mm)</label>
                    <input type="number" id="tinggi_mm" name="tinggi_mm" value="{{ $blangko->tinggi_mm }}" min="50" max="600"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="aktif" value="1" @checked($blangko->aktif)
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Blangko ini dipakai saat pencetakan
            </label>

            <input type="hidden" name="mode" value="blade">

            <div class="border-t border-slate-100 pt-3">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan</button>
            </div>
        </form>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Varian Teks</h2>
            <p class="mt-1 text-xs text-slate-500">
                Tersedia di template sebagai <code>$varianTeks</code>. Varian dipilih otomatis dari kelompok usia peserta
                (Pengaturan → daftar kelompok usia pemasalan).
            </p>

            <div class="mt-3 space-y-2">
                @forelse ($blangko->varian as $varian)
                    <div class="rounded-lg border border-slate-200 px-3 py-2">
                        <p class="text-sm font-medium text-slate-900">{{ $varian->judul }} <span class="text-xs text-slate-400">({{ $varian->key }})</span></p>
                        <p class="truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($varian->isi, 80) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada varian.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('blangko.varian.simpan', $blangko) }}" class="mt-4 space-y-2 border-t border-slate-100 pt-3">
                @csrf
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
                    <label for="v_isi" class="mb-1 block text-xs font-medium text-slate-600">Isi</label>
                    <textarea id="v_isi" name="isi" rows="3" required
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none"></textarea>
                </div>
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Simpan Varian</button>
            </form>
        </div>
    </div>

    <div class="space-y-6 lg:col-span-7">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Variabel yang Tersedia</h2>
            <p class="mt-1 text-xs text-slate-500">
                Tulis hanya <strong>isi halaman</strong> (satu <code>&lt;div&gt;</code> berukuran mm) — jangan sertakan
                <code>&lt;html&gt;</code>/<code>&lt;body&gt;</code>; pembungkus &amp; <code>@page</code> dibuat sistem.
            </p>

            <div class="mt-3 grid gap-3 text-xs sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="font-semibold text-slate-700">$data (peserta)</p>
                    <p class="mt-1 leading-relaxed text-slate-600">
                        nama, jenis_kelamin, jenis_kelamin_teks, kontingen, kategori, kelas,
                        kelompok_usia, juara, juara_romawi, label_juara,
                        no_sertifikat, no_sk, tanggal, nama_kejuaraan, penyelenggara, tempat_tanggal,
                        kota_ttd, nama_ttd, jabatan_ttd
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="font-semibold text-slate-700">$kop (pengaturan)</p>
                    <p class="mt-1 leading-relaxed text-slate-600">
                        nama_kejuaraan, singkatan, penyelenggara, tempat_tanggal, nama_ttd, jabatan_ttd,
                        kota_ttd, logo_kiri, logo_kanan, background (path absolut untuk PDF)
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="font-semibold text-slate-700">$varianTeks / $varian</p>
                    <p class="mt-1 leading-relaxed text-slate-600">
                        Teks varian yang sudah disubstitusi ({NAMA}, {KONTINGEN}, …) dan kuncinya
                        (<code>prestasi</code> / <code>pemasalan</code>).
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="font-semibold text-slate-700">$baris (khusus SK)</p>
                    <p class="mt-1 leading-relaxed text-slate-600">
                        Daftar baris juara: [nama, kontingen, kategori, kelas, juara] — siap di-<code>&#64;foreach</code>.
                    </p>
                </div>
            </div>

            @if (! $pathTemplate)
                <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    File template belum dipilih atau tidak ditemukan. Pilih file pada panel kiri.
                </p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="font-semibold text-slate-900">Isi File</h2>
                <span class="text-xs text-slate-400">{{ $pathTemplate ? str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $pathTemplate) : '—' }}</span>
            </div>
            <pre class="max-h-[32rem] overflow-auto bg-slate-950 p-4 text-xs leading-relaxed text-slate-100"><code>{{ $sumberTemplate ?? '// Belum ada file template yang dipilih.' }}</code></pre>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
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

        function syncUkuran() {
            const preset = presetUkuran[ukuran.value];

            if (!preset) {
                lebar.readOnly = false;
                tinggi.readOnly = false;

                return;
            }

            const landscape = orientasi.value === 'landscape';
            const [w, h] = preset;

            lebar.value = landscape ? h : w;
            tinggi.value = landscape ? w : h;
            lebar.readOnly = true;
            tinggi.readOnly = true;
        }

        ukuran.addEventListener('change', syncUkuran);
        orientasi.addEventListener('change', syncUkuran);
        syncUkuran();
    });
</script>
@endsection
