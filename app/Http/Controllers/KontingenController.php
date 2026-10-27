<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontingenRequest;
use App\Models\Kontingen;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KontingenController extends Controller
{
    public function index(): View
    {
        return view('kontingen.index', [
            'kontingens' => Kontingen::withCount('pesertas')->orderBy('nama')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('kontingen.form', [
            'kontingen' => new Kontingen,
        ]);
    }

    public function store(KontingenRequest $request): RedirectResponse
    {
        Kontingen::create($request->validated());

        return redirect()
            ->route('kontingen.index')
            ->with('success', 'Kontingen berhasil ditambahkan.');
    }

    public function edit(Kontingen $kontingen): View
    {
        return view('kontingen.form', [
            'kontingen' => $kontingen,
        ]);
    }

    public function update(KontingenRequest $request, Kontingen $kontingen): RedirectResponse
    {
        $kontingen->update($request->validated());

        return redirect()
            ->route('kontingen.index')
            ->with('success', "Kontingen \"{$kontingen->nama}\" berhasil diperbarui.");
    }

    public function destroy(Kontingen $kontingen): RedirectResponse
    {
        $jumlahPeserta = $kontingen->pesertas()->count();
        $jumlahOfficial = $kontingen->officials()->count();

        $kontingen->delete();

        $pesan = "Kontingen \"{$kontingen->nama}\" berhasil dihapus.";

        if ($jumlahPeserta > 0 || $jumlahOfficial > 0) {
            $pesan .= " Bersamaan dengan ini, {$jumlahPeserta} peserta dan {$jumlahOfficial} official ikut terhapus.";
        }

        return redirect()
            ->route('kontingen.index')
            ->with('success', $pesan);
    }
}
