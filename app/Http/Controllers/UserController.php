<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('user.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('user.form', [
            'user' => new User,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()
            ->route('user.index')
            ->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('user.form', [
            'user' => $user,
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except('password');

        if (filled($request->input('password'))) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return redirect()
            ->route('user.index')
            ->with('success', "Akun \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('user.index')
                ->with('error', 'Akun yang sedang digunakan tidak bisa dihapus.');
        }

        $nama = $user->name;
        $user->delete();

        return redirect()
            ->route('user.index')
            ->with('success', "Akun \"{$nama}\" berhasil dihapus.");
    }
}
