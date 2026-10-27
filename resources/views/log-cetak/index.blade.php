@extends('layouts.app')

@section('title', 'Riwayat Cetak')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Riwayat Cetak</h1>
    <p class="text-sm text-slate-500">Audit setiap aksi cetak dokumen. Baris log tidak dapat dihapus dari aplikasi.</p>
</div>

<form method="GET" action="{{ route('log-cetak.index') }}"
      class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
    <div>
        <label for="jenis" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Jenis Dokumen</label>
        <select id="jenis" name="jenis"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <option value="">Semua</option>
            <option value="sertifikat_peserta" @selected($jenis === 'sertifikat_peserta')>Sertifikat Peserta</option>
            <option value="sk_juara" @selected($jenis === 'sk_juara')>SK Juara</option>
            <option value="sertifikat_official" @selected($jenis === 'sertifikat_official')>Sertifikat Official</option>
            <option value="sertifikat_sekolah" @selected($jenis === 'sertifikat_sekolah')>Sertifikat Sekolah</option>
        </select>
    </div>

    <div>
        <label for="dari" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Dari Tanggal</label>
        <input type="date" id="dari" name="dari" value="{{ $dari }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
    </div>

    <div>
        <label for="sampai" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Sampai Tanggal</label>
        <input type="date" id="sampai" name="sampai" value="{{ $sampai }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
    </div>

    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Terapkan</button>
        <a href="{{ route('log-cetak.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>
        <span class="ml-auto text-xs text-slate-400">Total {{ $totalHalaman }} halaman tercetak sepanjang waktu</span>
    </div>
</form>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 font-semibold">Waktu</th>
                <th class="px-4 py-3 font-semibold">User</th>
                <th class="px-4 py-3 font-semibold">Jenis Dokumen</th>
                <th class="px-4 py-3 font-semibold">Nomor SK</th>
                <th class="px-4 py-3 text-center font-semibold">Jumlah</th>
                <th class="px-4 py-3 font-semibold">Rincian</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $log->created_at->format('d-m-Y H:i:s') }}</td>
                    <td class="px-4 py-3 text-slate-900">{{ $log->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ match ($log->jenis_dokumen) {
                            'sertifikat_peserta' => 'Sertifikat Peserta',
                            'sk_juara' => 'SK Juara',
                            'sertifikat_official' => 'Sertifikat Official',
                            'sertifikat_sekolah' => 'Sertifikat Sekolah',
                            default => $log->jenis_dokumen,
                        } }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $log->nomor_sk ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $log->jumlah_data }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $log->konteks ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada riwayat cetak.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
