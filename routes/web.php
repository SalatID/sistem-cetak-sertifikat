<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BlangkoController;
use App\Http\Controllers\CetakController;
use App\Http\Controllers\CetakPublikController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KontingenController;
use App\Http\Controllers\LegalisirController;
use App\Http\Controllers\LogCetakController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'attempt'])->name('login.attempt');
});

/*
| Cetak tanpa login (tamu).
|   - 'throttle:publik'       -> 60 permintaan/menit per IP
|   - 'throttle:cetak-publik' -> 30 PDF/jam per IP
*/
Route::middleware('throttle:publik')->group(function (): void {
    Route::get('/cetak', [CetakPublikController::class, 'index'])->name('publik.index');
    Route::get('/cetak/official', [CetakPublikController::class, 'officialForm'])->name('publik.official');
});

Route::middleware('throttle:cetak-publik')->group(function (): void {
    Route::get('/cetak/peserta/{peserta}/sertifikat', [CetakPublikController::class, 'sertifikat'])->name('publik.sertifikat');
    Route::get('/cetak/peserta/{peserta}/sk', [CetakPublikController::class, 'sk'])->name('publik.sk');
    Route::post('/cetak/official', [CetakPublikController::class, 'officialPrint'])->name('publik.official.cetak');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.page');

    Route::resource('kontingen', KontingenController::class)->except(['show']);

    // Catatan: Str::singular('peserta') di Laravel menghasilkan 'pesertum' (aturan inflector -a -> -um),
    // sehingga parameter route harus dipaksa menjadi 'peserta' agar route model binding bekerja.
    Route::resource('peserta', PesertaController::class)
        ->parameters(['peserta' => 'peserta'])
        ->except(['show']);

    // P2 & P3 — pencetakan dokumen (satuan & bulk)
    Route::get('/cetak/sertifikat/peserta', [CetakController::class, 'sertifikatPeserta'])->name('cetak.peserta.sertifikat');
    Route::get('/cetak/sk', [CetakController::class, 'skJuara'])->name('cetak.peserta.sk');
    Route::get('/cetak/sertifikat/official/{official}', [CetakController::class, 'sertifikatOfficial'])->name('cetak.official.sertifikat');

    // P3 — official / coach
    Route::get('/official', [OfficialController::class, 'index'])->name('official.index');
    Route::post('/official', [OfficialController::class, 'store'])->name('official.store');
    Route::delete('/official/{official}', [OfficialController::class, 'destroy'])->name('official.destroy');

    // P4 — khusus admin
    Route::middleware('admin')->group(function (): void {
        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
        Route::get('/log-cetak', [LogCetakController::class, 'index'])->name('log-cetak.index');
        Route::resource('user', UserController::class)->except(['show']);

        // P6 — blangko (desainer penempatan teks di atas gambar)
        Route::get('/blangko', [BlangkoController::class, 'index'])->name('blangko.index');
        Route::get('/blangko/create', [BlangkoController::class, 'create'])->name('blangko.create');
        Route::post('/blangko', [BlangkoController::class, 'store'])->name('blangko.store');
        Route::get('/blangko/{blangko}/pengaturan', [BlangkoController::class, 'form'])->name('blangko.form');
        Route::get('/blangko/{blangko}', [BlangkoController::class, 'edit'])->name('blangko.edit');
        Route::put('/blangko/{blangko}', [BlangkoController::class, 'update'])->name('blangko.update');
        Route::delete('/blangko/{blangko}', [BlangkoController::class, 'destroy'])->name('blangko.destroy');
        Route::get('/blangko/{blangko}/preview', [BlangkoController::class, 'preview'])->name('blangko.preview');
        Route::post('/blangko/{blangko}/elemen', [BlangkoController::class, 'simpanElemen'])->name('blangko.elemen.simpan');
        Route::delete('/blangko/{blangko}/elemen/{elemen}', [BlangkoController::class, 'hapusElemen'])->name('blangko.elemen.hapus');
        Route::post('/blangko/{blangko}/varian', [BlangkoController::class, 'simpanVarian'])->name('blangko.varian.simpan');
        Route::delete('/blangko/{blangko}/varian/{varian}', [BlangkoController::class, 'hapusVarian'])->name('blangko.varian.hapus');

        // Sertifikat sekolah — daftar sekolah & cetak ucapan terima kasih (khusus admin).
        Route::get('/sekolah', [SekolahController::class, 'index'])->name('sekolah.index');
        Route::post('/sekolah', [SekolahController::class, 'store'])->name('sekolah.store');
        Route::get('/cetak/sertifikat/sekolah/{sekolah}', [CetakController::class, 'sertifikatSekolah'])->name('cetak.sekolah.sertifikat');

        // Legalisir sertifikat peserta — hitam putih + stempel (khusus admin).
        Route::get('/legalisir', [LegalisirController::class, 'index'])->name('legalisir.index');
        Route::get('/legalisir/cetak', [LegalisirController::class, 'cetak'])->name('legalisir.cetak');
        Route::get('/legalisir/stempel', [LegalisirController::class, 'stempelIndex'])->name('legalisir.stempel.index');
        Route::get('/legalisir/stempel/create', [LegalisirController::class, 'stempelCreate'])->name('legalisir.stempel.create');
        Route::post('/legalisir/stempel', [LegalisirController::class, 'stempelStore'])->name('legalisir.stempel.store');
        Route::get('/legalisir/stempel/{stempel}/edit', [LegalisirController::class, 'stempelEdit'])->name('legalisir.stempel.edit');
        Route::put('/legalisir/stempel/{stempel}', [LegalisirController::class, 'stempelUpdate'])->name('legalisir.stempel.update');
        Route::delete('/legalisir/stempel/{stempel}', [LegalisirController::class, 'stempelDestroy'])->name('legalisir.stempel.destroy');
    });
});
