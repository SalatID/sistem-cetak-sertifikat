<?php

namespace App\Services;

use App\Models\Peserta;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran sertifikat "lazy assignment".
 *
 * - Hanya peserta Juara 1/2/3 dan Pesilat Terbaik yang berhak mendapat nomor.
 * - Peserta yang belum punya `no_sertifikat` mendapat nomor saat pertama kali dicetak
 *   oleh admin (MAX(no_sertifikat) + 1, batch diberi berurutan sesuai urutan pilihan).
 *   Cetak tamu di `/cetak` juga memanggil service ini untuk Juara 1/2/3 & Pesilat Terbaik.
 * - Peserta yang sudah punya nomor memakai ulang nomornya (cetak ulang tidak mengubah nomor).
 * - Named lock MySQL (GET_LOCK) mencegah dua operator mendapat nomor yang sama.
 */
class NomorSertifikat
{
    private const LOCK_NAME = 'scs_no_sertifikat';

    private const LOCK_TIMEOUT = 10;

    /**
     * @param  iterable<Peserta>  $pesertas
     */
    public static function assign(iterable $pesertas): void
    {
        $daftar = collect($pesertas)
            ->filter(fn (Peserta $p) => $p->no_sertifikat === null && $p->berhakNomorSertifikat())
            ->values();

        if ($daftar->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($daftar): void {
            DB::select('SELECT GET_LOCK(?, ?) AS acquired', [self::LOCK_NAME, self::LOCK_TIMEOUT]);

            try {
                $berikutnya = (int) Peserta::query()->whereNotNull('no_sertifikat')->max('no_sertifikat');

                foreach ($daftar as $peserta) {
                    $peserta->no_sertifikat = ++$berikutnya;
                    $peserta->save();
                }
            } finally {
                DB::select('SELECT RELEASE_LOCK(?) AS released', [self::LOCK_NAME]);
            }
        });
    }
}
