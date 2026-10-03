<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.entry', ['mode' => 'login']);
    }

    public function showRegister(): View
    {
        return view('auth.entry', ['mode' => 'register']);
    }

    public function register(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'rt_rw' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([...$attributes, 'registration_status' => 'menunggu']);

        return redirect()->route('login')->with('success', 'Pendaftaran berhasil dikirim. Akun dapat digunakan setelah dikonfirmasi admin.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($credentials)) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        if ($user->registration_status !== 'disetujui') {
            $message = $user->registration_status === 'ditolak'
                ? 'Pendaftaran akun Anda ditolak. Silakan hubungi admin lingkungan.'
                : 'Pendaftaran akun Anda masih menunggu konfirmasi admin.';

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($user->isAdmin() ? 'admin.dashboard' : 'portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}