<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * POST /api/register
     * T01 -> 201 Created | T02, T03, T04, T05 -> 422 Unprocessable Entity
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role'     => ['nullable', 'string', 'in:admin,bodeguero,vendedor,Administrador,Bodeguero,Vendedor'],
            'phone'    => ['nullable', 'string', 'max:30'],
        ]);

        $role = strtolower($validated['role'] ?? 'vendedor');

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'role'     => $role,
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        \App\Services\DebugLogService::log('USUARIO_REGISTRADO_API', [
            'id'    => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
        ]);

        // Nunca devolver el password ni el hash en la respuesta.
        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], 201);
    }

    /**
     * POST /api/login
     * T06 -> 200 OK | T07, T08 -> 401 Unauthorized (mismo mensaje en ambos casos,
     * para no permitir enumeración de usuarios).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message'    => 'Inicio de sesión exitoso.',
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * GET /api/profile (protegido con auth:sanctum)
     * T09, T10, T13 -> 401 Unauthorized (los maneja Sanctum automáticamente)
     * T11 -> 200 OK
     */
    public function profile(Request $request)
    {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
        ], 200);
    }

    /**
     * POST /api/logout (protegido con auth:sanctum)
     * T12 -> 200 OK. Revoca el token actual, de modo que T13 vuelva a dar 401.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }
}
