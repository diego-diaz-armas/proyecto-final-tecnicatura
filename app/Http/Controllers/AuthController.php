<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Usuarios;
use App\Models\Organizador;

class AuthController extends Controller
{
    public function validacion(Request $request)
    {
        // Validación
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'El correo es obligatorio',
            'email.email' => 'Ingrese un correo válido',
            'password.required' => 'Ingrese su contraseña',
        ]);

        // Buscar usuario
        $usuario = Usuarios::where('email', $request->email)->first();

        // Verificar si usuario existe y contraseña es correcta
        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            return back()->withErrors(['login' => 'Credenciales incorrectas'])->withInput();
        }

        // Loguear usuario
        Auth::login($usuario);

        // Regenerar sesión
        $request->session()->regenerate();

        // ⚡ Cargar organizador y eventos con relaciones para dashboard
        $usuario->load([
            'organizador.eventos' => function($query) {
                $query->with(['categorias', 'fechasHoras', 'imagen']);
            }
        ]);

        // Redirigir al dashboard
        return redirect()->route('dashboard')->with('success', 'Bienvenido ' . $usuario->nombre);
    }

    public function registro(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|confirmed|min:6',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El correo es obligatorio',
            'email.email' => 'Ingrese un correo válido',
            'email.unique' => 'Este correo ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'La confirmación no coincide',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        $usuario = Usuarios::create([
            'nombre' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($usuario);

        // ⚡ Cargar organizador y eventos (aunque recién registrado probablemente no tenga)
        $usuario->load([
            'organizador.eventos' => function($query) {
                $query->with(['categorias', 'fechasHoras', 'imagen']);
            }
        ]);

        return redirect()->route('dashboard')->with('success', 'Cuenta creada correctamente. Bienvenido ' . $usuario->nombre);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'Has cerrado sesión.');
    }
}
