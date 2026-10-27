@extends('layouts.app')

@section('title', 'Kontingen')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kontingen</h1>
        <p class="text-sm text-slate-500">Daftar kontingen / tim peserta.</p>
    </div>
    <a href="{{ route('kontingen.create') }}"
       class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        + Tambah Kontingen
    </a>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 font-semibold">No</th>
                <th class="px-4 py-3 font-semibold">Nama Kontingen</th>
                <th class="px-4 py-3 font-semibold">Kota / Daerah</th>
                <th class="px-4 py-3 text-center font-semibold">Jumlah Peserta</th>
                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($kontingens as $index => $kontingen)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-500">{{ $kontingens->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $kontingen->nama }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $kontingen->kota ?: '—' }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $kontingen->pesertas_count }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('kontingen.edit', $kontingen) }}"
                               class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Ubah
                            </a>
                            <form method="POST" action="{{ route('kontingen.destroy', $kontingen) }}"
                                  data-confirm="Hapus kontingen &quot;{{ $kontingen->nama }}&quot;? Semua peserta dan official pada kontingen ini juga ikut terhapus.">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                        Belum ada kontingen. Klik <span class="font-medium">Tambah Kontingen</span> untuk memulai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $kontingens->links() }}</div>
@endsection
