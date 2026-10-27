@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen User</h1>
        <p class="text-sm text-slate-500">Akun panitia yang bisa mengakses sistem.</p>
    </div>
    <a href="{{ route('user.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        + Tambah User
    </a>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 font-semibold">Nama</th>
                <th class="px-4 py-3 font-semibold">Email</th>
                <th class="px-4 py-3 font-semibold">Peran</th>
                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-900">
                        {{ $user->name }}
                        @if ($user->id === auth()->id())
                            <span class="ml-1 text-xs text-slate-400">(sedang digunakan)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $user->isAdmin() ? 'bg-indigo-100 text-indigo-800 ring-indigo-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                            {{ $user->isAdmin() ? 'Admin' : 'Operator' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('user.edit', $user) }}"
                               class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Ubah
                            </a>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('user.destroy', $user) }}"
                                      data-confirm="Hapus akun &quot;{{ $user->name }}&quot;?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
