<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function form()
    {
        return view('login');
    }

    public function login(Request $r)
    {
        $r->validate(['usuario' => 'required|string', 'password' => 'required|string']);

        $u = Usuario::where('usuario', $r->usuario)->first();

        if ($u && $u->bloqueado_hasta) {
            if (now()->lt($u->bloqueado_hasta)) {
                return back()->withErrors(['usuario' => 'Cuenta bloqueada temporalmente. Intente más tarde.']);
            }
            $u->update(['intentos_fallidos' => 0, 'bloqueado_hasta' => null]);
        }

        if ($u && $u->activo && Hash::check($r->password, $u->contrasena_hash)) {
            $u->update(['intentos_fallidos' => 0, 'bloqueado_hasta' => null]);
            Auth::login($u);
            $r->session()->regenerate();
            return redirect()->intended(route('ventas.index'));
        }

        if ($u) {
            $n = min(5, $u->intentos_fallidos + 1);
            $u->update([
                'intentos_fallidos' => $n,
                'bloqueado_hasta' => $n >= 5 ? now()->addMinutes(5) : null,
            ]);
        }
        return back()->withErrors(['usuario' => 'Usuario o contraseña incorrectos.'])->onlyInput('usuario');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
