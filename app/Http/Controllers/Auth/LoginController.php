<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function createRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role'     => ['required', 'string', 'in:admin,bodeguero,vendedor,Administrador,Bodeguero,Vendedor'],
            'phone'    => ['nullable', 'string', 'max:30'],
        ]);

        $role = strtolower($validated['role']);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'role'     => $role,
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        \App\Services\DebugLogService::log('USUARIO_REGISTRADO_WEB', [
            'id'    => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
        ]);

        return redirect()
            ->route('login')
            ->with('success', "Registro exitoso con perfil '{$user->role}'. ¡Inicia sesión con tu nueva cuenta!");
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'Las credenciales proporcionadas no son válidas.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // Si usas route(), pasa el NOMBRE de la ruta (ej: 'dashboard'), no la URL '/dashboard'
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}