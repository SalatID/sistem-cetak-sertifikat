<?php

namespace App\Http\Controllers;

use App\Models\Kontingen;
use App\Models\Peserta;
use App\Models\PrintLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'totalKontingen' => Kontingen::count(),
            'totalPeserta' => Peserta::count(),
            'totalJuara' => Peserta::whereNotNull('juara')->count(),
            'totalBelumJuara' => Peserta::whereNull('juara')->count(),
            'juaraEmas' => Peserta::where('juara', '1')->count(),
            'juaraPerak' => Peserta::where('juara', '2')->count(),
            'juaraPerunggu' => Peserta::where('juara', '3')->count(),
            'jumlahPesertaLabel' => Peserta::whereRaw('LOWER(juara) = ?', ['peserta'])->count(),
            'jumlahPesilatTerbaik' => Peserta::whereRaw('LOWER(juara) = ?', ['pesilat terbaik'])->count(),
            'halamanHariIni' => PrintLog::whereDate('created_at', today())->sum('jumlah_data'),
            'totalHalaman' => PrintLog::sum('jumlah_data'),
        ]);
    }
}
