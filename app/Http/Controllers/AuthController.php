<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return Auth::user()->isSales()
                ? redirect()->route('rute.index')
                : redirect()->route('dashboard');
        }

        $demoUsers = User::orderBy('role', 'asc')->orderBy('id', 'asc')->get();

        return view('auth.login', compact('demoUsers'));
    }

    /**
     * Proses autentikasi login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Jika role sales, arahkan langsung ke agenda rute yang dibolehkan
            if ($user->isSales()) {
                // Bersihkan intended URL jika sebelumnya mengarah ke dashboard admin
                $intended = session()->get('url.intended');
                if ($intended && (str_contains($intended, 'dashboard') || str_contains($intended, 'sales') || str_contains($intended, 'stores/create'))) {
                    session()->forget('url.intended');
                }

                return redirect()->intended(route('rute.index'))
                    ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan kata sandi tidak cocok.',
        ])->onlyInput('email');
    }

    /**
     * Fitur Login Cepat (One-Click Demo Login untuk Evaluasi & Pengujian).
     */
    public function quickLogin($id)
    {
        $user = User::findOrFail($id);
        Auth::login($user);
        request()->session()->regenerate();

        if ($user->isSales()) {
            return redirect()->route('rute.index')
                ->with('success', "Beralih akun ke {$user->name} ({$user->role}) berhasil.");
        }

        return redirect()->route('dashboard')
            ->with('success', "Beralih akun ke {$user->name} ({$user->role}) berhasil.");
    }

    /**
     * Logout pengguna dari aplikasi.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
