<?php

namespace App\Http\Controllers;

use App\Models\Blangko;
use App\Models\BlangkoElemen;
use App\Models\Sekolah;
use App\Models\TeksVarian;
use App\Services\BlangkoRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BlangkoController extends Controller
{
    public function index(): View
    {
        return view('blangko.index', [
            'blangkos' => Blangko::withCount(['elemen', 'varian'])->orderBy('jenis')->orderBy('nama')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $jenis = $request->string('jenis')->toString();

        if (! array_key_exists($jenis, Blangko::pilihanJenis())) {
            $jenis = Blangko::JENIS_SERTIFIKAT;
        }

        $mode = $request->string('mode')->toString();

        if (! in_array($mode, [Blangko::MODE_CANVAS, Blangko::MODE_BLADE], true)) {
            $mode = Blangko::MODE_CANVAS;
        }

        $viewBlade = $request->string('view_blade')->toString();

        if ($mode !== Blangko::MODE_BLADE || ! array_key_exists($viewBlade, Blangko::pilihanTemplate())) {
            $viewBlade = null;
        }

        return view('blangko.form', [
            'blangko' => new Blangko([
                'jenis' => $jenis,
                'mode' => $mode,
                'view_blade' => $viewBlade,
                'orientasi' => 'landscape',
                'ukuran_kertas' => 'a4',
                'lebar_mm' => 297,
                'tinggi_mm' => 210,
            ]),
            'pilihanTemplate' => Blangko::pilihanTemplate(),
            'pilihanUkuran' => Blangko::pilihanUkuran(),
            'pilihanJenis' => Blangko::pilihanJenis(),
        ]);
    }

    /**
     * Form pengaturan blangko yang sudah ada (ganti gambar/ukuran/mode).
     */
    public function form(Blangko $blangko): View
    {
        return view('blangko.form', [
            'blangko' => $blangko,
            'pilihanTemplate' => Blangko::pilihanTemplate(),
            'pilihanUkuran' => Blangko::pilihanUkuran(),
            'pilihanJenis' => Blangko::pilihanJenis(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('blangko', 'public');
        }

        $blangko = Blangko::create($data);

        return redirect()
            ->route('blangko.edit', $blangko)
            ->with('success', $blangko->pakaiBlade()
                ? "Blangko \"{$blangko->nama}\" dibuat dengan template Blade."
                : "Blangko \"{$blangko->nama}\" dibuat. Sekarang tempatkan elemen teksnya.");
    }

    public function edit(Blangko $blangko): View
    {
        if ($blangko->pakaiBlade()) {
            $path = $blangko->pathTemplate();

            return view('blangko.blade', [
                'blangko' => $blangko->load(['varian']),
                'pilihanTemplate' => Blangko::pilihanTemplate(),
                'pilihanUkuran' => Blangko::pilihanUkuran(),
                'pathTemplate' => $path,
                'sumberTemplate' => $path && is_readable($path) ? (string) file_get_contents($path) : null,
            ]);
        }

        return view('blangko.desainer', [
            'blangko' => $blangko->load(['elemen', 'varian']),
            'pilihanField' => BlangkoElemen::pilihanField(),
            'pilihanFont' => BlangkoElemen::pilihanFont(),
        ]);
    }

    public function update(Request $request, Blangko $blangko): RedirectResponse
    {
        $data = $this->validasi($request);

        $data['aktif'] = $request->boolean('aktif');

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('blangko', 'public');
        }

        $blangko->update($data);

        return redirect()
            ->route('blangko.edit', $blangko)
            ->with('success', 'Blangko diperbarui.');
    }

    /**
     * Validasi data blangko (canvas maupun blade).
     *
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        $custom = $request->input('ukuran_kertas') === 'custom';

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(array_keys(Blangko::pilihanJenis()))],
            'mode' => ['required', 'in:canvas,blade'],
            'view_blade' => ['nullable', 'string', 'max:120', Rule::in(array_keys(Blangko::pilihanTemplate()))],
            'ukuran_kertas' => ['required', Rule::in(array_keys(Blangko::pilihanUkuran()))],
            'orientasi' => ['required', 'in:landscape,portrait'],
            // Dimensi hanya divalidasi untuk ukuran custom; preset dihitung sistem.
            'lebar_mm' => $custom ? ['required', 'integer', 'min:50', 'max:600'] : ['nullable'],
            'tinggi_mm' => $custom ? ['required', 'integer', 'min:50', 'max:600'] : ['nullable'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'view_blade.in' => 'File template Blade tidak ditemukan di resources/views/blangko/.',
        ]);

        if ($data['mode'] === Blangko::MODE_BLADE && blank($data['view_blade'] ?? null)) {
            throw ValidationException::withMessages([
                'view_blade' => 'Pilih file template Blade untuk mode ini.',
            ]);
        }

        if ($data['mode'] === Blangko::MODE_CANVAS) {
            $data['view_blade'] = null;
        }

        // Ukuran standar (A4/F4/Letter) menimpa input mm; custom wajib isi manual.
        $dimensi = Blangko::dimensiUkuran($data['ukuran_kertas'], $data['orientasi']);

        if ($dimensi !== null) {
            $data = array_merge($data, $dimensi);
        } elseif (blank($data['lebar_mm'] ?? null) || blank($data['tinggi_mm'] ?? null)) {
            throw ValidationException::withMessages([
                'lebar_mm' => 'Lebar dan tinggi (mm) wajib diisi untuk ukuran Custom.',
            ]);
        }

        $data['lebar_mm'] = (int) $data['lebar_mm'];
        $data['tinggi_mm'] = (int) $data['tinggi_mm'];

        return $data;
    }

    public function destroy(Blangko $blangko): RedirectResponse
    {
        $nama = $blangko->nama;
        $blangko->delete();

        return redirect()
            ->route('blangko.index')
            ->with('success', "Blangko \"{$nama}\" dihapus.");
    }

    /**
     * Simpan elemen (baru atau perbarui posisi/teks).
     *
     * Dipanggil dari form desainer (biasa) maupun dari AJAX saat kotak digeser.
     */
    public function simpanElemen(Request $request, Blangko $blangko): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'elemen_id' => ['nullable', 'integer'],
            'label' => ['required', 'string', 'max:100'],
            'tipe' => ['required', 'in:teks,data,varian,tabel'],
            'x_mm' => ['required', 'numeric', 'min:0', 'max:600'],
            'y_mm' => ['required', 'numeric', 'min:0', 'max:600'],
            'lebar_mm' => ['required', 'numeric', 'min:5', 'max:600'],
            'tinggi_mm' => ['required', 'numeric', 'min:3', 'max:600'],
            'isi' => ['nullable', 'string', 'max:2000'],
            'sumber_field' => ['nullable', 'string', 'max:50'],
            'varian_key' => ['nullable', 'string', 'max:50'],
            'font_family' => ['required', 'in:sans,serif,mono'],
            'font_size' => ['required', 'integer', 'min:5', 'max:72'],
            'tebal' => ['nullable', 'boolean'],
            'miring' => ['nullable', 'boolean'],
            'align' => ['required', 'in:left,center,right,justify'],
            'valign' => ['required', 'in:top,middle,bottom'],
            'warna' => ['nullable', 'string', 'max:20'],
            'z_index' => ['nullable', 'integer', 'min:0', 'max:999'],
            'tampil' => ['nullable', 'boolean'],
        ]);

        $data['tebal'] = $request->boolean('tebal');
        $data['miring'] = $request->boolean('miring');
        $data['tampil'] = $request->boolean('tampil');
        $data['z_index'] = $data['z_index'] ?? 10;
        $data['warna'] = $data['warna'] ?? '#0f172a';

        $baru = empty($data['elemen_id']);

        if (! $baru) {
            $elemen = $blangko->elemen()->findOrFail((int) $data['elemen_id']);
            $elemen->update($data);
        } else {
            unset($data['elemen_id']);
            $elemen = $blangko->elemen()->create($data);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'baru' => $baru,
                'pesan' => 'Elemen tersimpan.',
                'elemen' => $elemen->fresh(),
            ]);
        }

        return back()->with('success', 'Elemen tersimpan.');
    }

    /**
     * Hapus satu elemen dari blangko.
     */
    public function hapusElemen(Request $request, Blangko $blangko, BlangkoElemen $elemen): RedirectResponse|JsonResponse
    {
        abort_unless($elemen->blangko_id === $blangko->id, 404);

        $elemen->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'pesan' => 'Elemen dihapus.']);
        }

        return back()->with('success', 'Elemen dihapus.');
    }

    /**
     * Simpan varian teks (mis. "Prestasi" & "Pemasalan").
     */
    public function simpanVarian(Request $request, Blangko $blangko): RedirectResponse
    {
        $data = $request->validate([
            'varian_id' => ['nullable', 'integer'],
            'key' => ['required', 'string', 'max:50', 'alpha_dash'],
            'judul' => ['required', 'string', 'max:100'],
            'isi' => ['required', 'string', 'max:5000'],
        ]);

        if (! empty($data['varian_id'])) {
            $varian = $blangko->varian()->findOrFail((int) $data['varian_id']);
            $varian->update($data);
        } else {
            unset($data['varian_id']);
            $blangko->varian()->updateOrCreate(['key' => $data['key']], $data);
        }

        return back()->with('success', 'Varian teks tersimpan.');
    }

    public function hapusVarian(Blangko $blangko, TeksVarian $varian): RedirectResponse
    {
        abort_unless($varian->blangko_id === $blangko->id, 404);

        $varian->delete();

        return back()->with('success', 'Varian teks dihapus.');
    }

    /**
     * Preview blangko dengan data contoh (untuk mengecek posisi sebelum dicetak).
     */
    public function preview(Blangko $blangko): View
    {
        $blangko->load(['elemen', 'varian']);

        $data = $blangko->jenis === Blangko::JENIS_SEKOLAH
            ? BlangkoRenderer::dataSekolah(new Sekolah(['nama' => 'NAMA SEKOLAH']))
            : array_merge(BlangkoRenderer::dataUmum(), [
                'nama' => 'NAMA PESERTA',
                'jenis_kelamin' => 'L',
                'jenis_kelamin_teks' => 'Putra',
                'kontingen' => 'NAMA KONTINGEN',
                'kategori' => 'Kategori Lomba',
                'kelas' => '-60 kg',
                'kelompok_usia' => 'Usia Dini 2',
                'juara' => '1',
                'juara_romawi' => 'I',
                'label_juara' => 'Emas',
                'no_sertifikat' => '001',
                'no_sk' => '029/SK/PANPEL SBCKC/II/X/2026',
                'jabatan' => 'Coach',
            ]);

        $baris = [
            ['NAMA PESERTA', 'NAMA KONTINGEN', 'Kategori Lomba', '-60 kg', '1'],
            ['NAMA PESERTA 2', 'NAMA KONTINGEN', 'Kategori Lomba', '-55 kg', '2'],
        ];

        return view('blangko.preview', [
            'blangko' => $blangko,
            'html' => $blangko->pakaiBlade()
                ? BlangkoRenderer::renderBlade($blangko, $data, BlangkoRenderer::VARIAN_PRESTASI, $baris, BlangkoRenderer::kop())
                : BlangkoRenderer::render($blangko, $data, $blangko->urlGambar(), BlangkoRenderer::VARIAN_PRESTASI, $baris),
        ]);
    }
}
