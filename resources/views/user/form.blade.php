@extends('layouts.app')

@php
    $isEdit = $user->exists;
    $action = $isEdit ? route('user.update', $user) : route('user.store');
@endphp

@section('title', $isEdit ? 'Ubah User' : 'Tambah User')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">{{ $isEdit ? 'Ubah User' : 'Tambah User' }}</h1>
    <p class="text-sm text-slate-500">Role Admin punya akses ke pengaturan, riwayat cetak, dan manajemen user.</p>
</div>

<div class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nama <span class="text-rose-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email <span class="text-rose-500">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <div>
            <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Peran <span class="text-rose-500">*</span></label>
            <select id="role" name="role" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                <option value="operator" @selected(old('role', $user->role) === 'operator')>Operator — Kelola data & cetak</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin — termasuk pengaturan & user</option>
            </select>
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                Password {!! $isEdit ? '' : '<span class="text-rose-500">*</span>' !!}
            </label>
            <input type="password" id="password" name="password" @if (! $isEdit) required @endif autocomplete="new-password"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            <p class="mt-1 text-xs text-slate-400">
                {{ $isEdit ? 'Kosongkan bila password tidak diubah. Minimal 8 karakter.' : 'Minimal 8 karakter.' }}
            </p>
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Ulangi Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
        </div>

        <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan User' }}
            </button>
            <a href="{{ route('user.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
        </div>
    </form>
</div>
@endsection
