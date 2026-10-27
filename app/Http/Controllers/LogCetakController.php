<?php

namespace App\Http\Controllers;

use App\Models\PrintLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogCetakController extends Controller
{
    public function index(Request $request): View
    {
        $jenis = $request->string('jenis')->toString() ?: null;
        $dari = $request->date('dari')?->toDateString();
        $sampai = $request->date('sampai')?->toDateString();

        $logs = PrintLog::with(['user', 'peserta', 'official', 'kontingen'])
            ->when($jenis, fn ($q, $value) => $q->where('jenis_dokumen', $value))
            ->when($dari, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($sampai, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('log-cetak.index', [
            'logs' => $logs,
            'jenis' => $jenis,
            'dari' => $dari,
            'sampai' => $sampai,
            'totalHalaman' => PrintLog::sum('jumlah_data'),
        ]);
    }
}
