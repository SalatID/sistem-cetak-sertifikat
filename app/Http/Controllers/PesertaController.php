<?php

namespace App\Http\Controllers;

use App\Http\Requests\PesertaRequest;
use App\Models\Kontingen;
use App\Models\Peserta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesertaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'kontingen_id' => $request->integer('kontingen_id') ?: null,
            'kategori' => $request->string('kategori')->toString() ?: null,
            'kelompok_usia' => $request->string('kelompok_usia')->toString() ?: null,
            'juara' => $request->string('juara')->toString() ?: null,
        ];

        return view('peserta.index', [
            'peserta' => Peserta::with('kontingen')
                ->filter($filters)
                ->orderBy('nama')
                ->orderBy('kategori')
                ->orderBy('juara')
                ->paginate(25)
                ->withQueryString(),
            'kontingens' => Kontingen::orderBy('nama')->get(),
            'kategoriList' => Peserta::query()->distinct()->orderBy('kategori')->pluck('kategori'),
            'kelompokUsiaList' => Peserta::query()
                ->whereNotNull('kelompok_usia')
                ->where('kelompok_usia', '!=', '')
                ->distinct()
                ->orderBy('kelompok_usia')
                ->pluck('kelompok_usia'),
            'juaraList' => Peserta::query()->whereNotNull('juara')->distinct()->orderBy('juara')->pluck('juara'),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('peserta.form', [
            'peserta' => new Peserta,
            'kontingens' => Kontingen::orderBy('nama')->get(),
        ]);
    }

    public function store(PesertaRequest $request): RedirectResponse
    {
        Peserta::create($request->validated());

        return redirect()
            ->route('peserta.index')
            ->with('success', 'Peserta berhasil ditambahkan.');
    }

    public function edit(Peserta $peserta): View
    {
        return view('peserta.form', [
            'peserta' => $peserta,
            'kontingens' => Kontingen::orderBy('nama')->get(),
        ]);
    }

    public function update(PesertaRequest $request, Peserta $peserta): RedirectResponse
    {
        $peserta->update($request->validated());

        return redirect()
            ->route('peserta.index')
            ->with('success', "Data peserta \"{$peserta->nama}\" berhasil diperbarui.");
    }

    public function destroy(Peserta $peserta): RedirectResponse
    {
        $nama = $peserta->nama;
        $peserta->delete();

        return redirect()
            ->route('peserta.index')
            ->with('success', "Peserta \"{$nama}\" berhasil dihapus.");
    }
}
