<?php

namespace App\Http\Controllers;

use App\Http\Requests\StempelLegalisirRequest;
use App\Models\Blangko;
use App\Models\Peserta;
use App\Models\StempelLegalisir;
use App\Services\LayananCetak;
use App\Services\NomorSertifikat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Legalisir sertifikat peserta (khusus admin): pilih peserta, pilih satu
 * stempel, lalu cetak PDF hitam putih sebanyak copy yang diatur di Pengaturan.
 */
class LegalisirController extends Controller
{
    private const MAKS_PESERTA = 50;

    private const MAKS_HALAMAN = 200;

    public function __construct(private readonly LayananCetak $layanan) {}

    /**
     * Halaman pemilihan peserta, stempel, dan jumlah copy legalisir.
     */
    public function index(Request $request): View
    {
        $ids = $request->input('ids', []);
        $ids = is_array($ids) ? $ids : [$ids];

        $ids = collect($ids)
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $terlaluBanyak = count($ids) > self::MAKS_PESERTA;
        $ids = array_slice($ids, 0, self::MAKS_PESERTA);

        $pesertas = $ids === []
            ? collect()
            : Peserta::with('kontingen')
                ->whereIn('id', $ids)
                ->orderBy('kategori')
                ->orderBy('juara')
                ->orderBy('nama')
                ->get();

        $blangko = Blangko::aktif(Blangko::JENIS_SERTIFIKAT);
        $jumlahCopy = LayananCetak::jumlahCopyLegalisir();
        $pesertaValid = $pesertas->filter(fn (Peserta $peserta): bool => $peserta->bolehCetakSertifikatPublik());
        $totalHalaman = $pesertaValid->count() * $jumlahCopy;

        return view('legalisir.index', [
            'pesertas' => $pesertas,
            'pesertaValid' => $pesertaValid,
            'stempels' => StempelLegalisir::query()->aktif()->orderBy('nama')->get(),
            'blangko' => $blangko,
            'pageWidth' => (int) ($blangko?->lebar_mm ?? 297),
            'pageHeight' => (int) ($blangko?->tinggi_mm ?? 210),
            'jumlahCopy' => $jumlahCopy,
            'totalHalaman' => $totalHalaman,
            'terlaluBanyak' => $terlaluBanyak,
            'terlaluBanyakHalaman' => $totalHalaman > self::MAKS_HALAMAN,
            'maksPeserta' => self::MAKS_PESERTA,
            'maksHalaman' => self::MAKS_HALAMAN,
        ]);
    }

    /**
     * Cetak PDF legalisir (hitam putih + stempel).
     */
    public function cetak(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAKS_PESERTA],
            'ids.*' => ['integer', 'exists:pesertas,id'],
            'stempel_id' => [
                'required',
                'integer',
                Rule::exists('stempel_legalisirs', 'id')->where('aktif', true),
            ],
        ], [
            'ids.required' => 'Pilih minimal satu peserta untuk dilegalisir.',
            'ids.max' => 'Maksimal '.self::MAKS_PESERTA.' peserta per PDF legalisir.',
            'ids.*.exists' => 'Ada peserta yang dipilih yang tidak ditemukan.',
            'stempel_id.required' => 'Pilih satu stempel legalisir.',
            'stempel_id.exists' => 'Stempel legalisir tidak ditemukan atau sudah tidak aktif.',
        ]);

        abort_if($validator->fails(), 422, $validator->errors()->first());

        $data = $validator->validated();
        $stempel = StempelLegalisir::findOrFail($data['stempel_id']);

        abort_if($stempel->pathGambar() === null, 422, 'Gambar stempel legalisir tidak ditemukan.');

        $ids = array_map('intval', $data['ids']);

        $pesertas = Peserta::with('kontingen')
            ->whereIn('id', $ids)
            ->orderBy('kategori')
            ->orderBy('juara')
            ->orderBy('nama')
            ->get();

        abort_if($pesertas->isEmpty(), 422, 'Tidak ada peserta terpilih untuk dilegalisir.');

        $tidakBisaCetak = $pesertas->reject(fn (Peserta $p): bool => $p->bolehCetakSertifikatPublik());

        if ($tidakBisaCetak->isNotEmpty()) {
            $nama = $tidakBisaCetak->pluck('nama')->implode(', ');

            abort_if(
                $pesertas->count() === $tidakBisaCetak->count(),
                422,
                "Peserta berikut belum memiliki keterangan juara sehingga belum bisa dilegalisir: {$nama}."
            );

            session()->flash('warning', 'Dilewati (belum memiliki keterangan juara): '.$nama.'.');
        }

        $pesertas = $pesertas->filter(fn (Peserta $p): bool => $p->bolehCetakSertifikatPublik())->values();

        $jumlahCopy = LayananCetak::jumlahCopyLegalisir();
        $totalHalaman = $pesertas->count() * $jumlahCopy;

        abort_if(
            $totalHalaman > self::MAKS_HALAMAN,
            422,
            'Total halaman legalisir melebihi batas '.self::MAKS_HALAMAN.' halaman. Kurangi jumlah peserta atau copy.'
        );

        NomorSertifikat::assign($pesertas);

        return $this->layanan->legalisir($pesertas, $stempel, auth()->id());
    }

    /**
     * Daftar stempel legalisir.
     */
    public function stempelIndex(): View
    {
        $page = StempelLegalisirRequest::dimensiHalaman();

        return view('legalisir.stempel.index', [
            'stempels' => StempelLegalisir::query()->orderBy('nama')->get(),
            'blangko' => Blangko::aktif(Blangko::JENIS_SERTIFIKAT),
            'pageWidth' => $page['lebar'],
            'pageHeight' => $page['tinggi'],
            'jumlahCopy' => LayananCetak::jumlahCopyLegalisir(),
        ]);
    }

    /**
     * Form tambah stempel.
     */
    public function stempelCreate(): View
    {
        return $this->formStempel(null);
    }

    /**
     * Simpan stempel baru.
     */
    public function stempelStore(StempelLegalisirRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['gambar'] = $request->file('gambar')->store('stempel-legalisir', 'public');
        $data['aktif'] = $request->boolean('aktif');

        StempelLegalisir::create($data);

        return redirect()
            ->route('legalisir.stempel.index')
            ->with('success', 'Stempel legalisir berhasil ditambahkan.');
    }

    /**
     * Form edit stempel.
     */
    public function stempelEdit(StempelLegalisir $stempel): View
    {
        return $this->formStempel($stempel);
    }

    /**
     * Perbarui stempel (gambar opsional).
     */
    public function stempelUpdate(StempelLegalisirRequest $request, StempelLegalisir $stempel): RedirectResponse
    {
        $data = $request->validated();
        unset($data['gambar']);

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('stempel-legalisir', 'public');

            if (filled($stempel->gambar)) {
                Storage::disk('public')->delete($stempel->gambar);
            }

            $data['gambar'] = $path;
        }

        $data['aktif'] = $request->boolean('aktif');

        $stempel->update($data);

        return redirect()
            ->route('legalisir.stempel.index')
            ->with('success', 'Stempel legalisir berhasil diperbarui.');
    }

    /**
     * Hapus stempel beserta gambarnya.
     */
    public function stempelDestroy(StempelLegalisir $stempel): RedirectResponse
    {
        if (filled($stempel->gambar)) {
            Storage::disk('public')->delete($stempel->gambar);
        }

        $stempel->delete();

        return redirect()
            ->route('legalisir.stempel.index')
            ->with('success', 'Stempel legalisir berhasil dihapus.');
    }

    private function formStempel(?StempelLegalisir $stempel): View
    {
        $page = StempelLegalisirRequest::dimensiHalaman();

        return view('legalisir.stempel.form', [
            'stempel' => $stempel,
            'blangko' => Blangko::aktif(Blangko::JENIS_SERTIFIKAT),
            'pageWidth' => $page['lebar'],
            'pageHeight' => $page['tinggi'],
            'defaultX' => max(0, $page['lebar'] - 60),
            'defaultY' => 20,
        ]);
    }
}
