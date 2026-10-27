<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SHIFT_HOURS = 7;

    /**
     * Aplikasi sebelumnya memakai timezone UTC. Baris log lama masih tersimpan
     * dalam UTC, sedangkan log baru akan disimpan dalam Asia/Jakarta (WIB).
     * Geser log lama +7 jam agar riwayat cetak konsisten.
     */
    public function up(): void
    {
        $this->shift(self::SHIFT_HOURS);
    }

    public function down(): void
    {
        $this->shift(-self::SHIFT_HOURS);
    }

    private function shift(int $hours): void
    {
        if (DB::getDriverName() === 'mysql') {
            $expression = $hours >= 0
                ? 'DATE_ADD(created_at, INTERVAL '.$hours.' HOUR)'
                : 'DATE_SUB(created_at, INTERVAL '.abs($hours).' HOUR)';

            DB::table('print_logs')
                ->whereNotNull('created_at')
                ->update(['created_at' => DB::raw($expression)]);

            return;
        }

        DB::table('print_logs')
            ->whereNotNull('created_at')
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($hours): void {
                foreach ($logs as $log) {
                    DB::table('print_logs')->where('id', $log->id)->update([
                        'created_at' => Carbon::parse($log->created_at, 'UTC')->addHours($hours),
                    ]);
                }
            });
    }
};
