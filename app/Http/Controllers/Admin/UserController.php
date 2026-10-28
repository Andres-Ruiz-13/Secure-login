<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DebugLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Listado de usuarios (API y Web)
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('role', 'like', $term);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $perPage = min((int) ($request->get('per_page', 10)), 100);
        $users = $query->latest('id')->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Crear usuario y asignarlo a rol (Administrador, Bodeguero, Vendedor)
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', Rule::in(['admin', 'bodeguero', 'vendedor', 'Administrador', 'Bodeguero', 'Vendedor'])],
        ]);

        $role = strtolower($validated['role']);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role'     => $role,
        ]);

        DebugLogService::log('USUARIO_CREADO_ADMIN', [
            'admin_id' => $request->user()?->id,
            'user_id'  => $user->id,
            'email'    => $user->email,
            'role'     => $user->role,
        ]);

        AuditService::log('usuario_creado_admin', User::class, $user->id, [
            'email' => $user->email,
            'role'  => $user->role,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => "Usuario '{$user->name}' creado y asignado al perfil '{$user->role}' exitosamente.",
                'user'    => $user,
            ], 201);
        }

        return back()->with('success', "Usuario '{$user->name}' creado con rol '{$user->role}' correctamente.");
    }

    /**
     * Actualizar perfil / rol de usuario
     */
    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['nullable', 'string', 'max:120'],
            'email'    => ['nullable', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'phone'    => ['nullable', 'string', 'max:30'],
            'role'     => ['nullable', Rule::in(['admin', 'bodeguero', 'vendedor', 'Administrador', 'Bodeguero', 'Vendedor'])],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (! empty($validated['name'])) $user->name = $validated['name'];
        if (! empty($validated['email'])) $user->email = $validated['email'];
        if (array_key_exists('phone', $validated)) $user->phone = $validated['phone'];
        if (! empty($validated['role'])) $user->role = strtolower($validated['role']);
        if (! empty($validated['password'])) $user->password = Hash::make($validated['password']);

        $user->save();

        DebugLogService::log('USUARIO_ACTUALIZADO_ADMIN', ['user_id' => $user->id, 'role' => $user->role]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Usuario actualizado exitosamente.',
                'user'    => $user,
            ]);
        }

        return back()->with('success', "Usuario '{$user->name}' actualizado correctamente.");
    }

    /**
     * Eliminar usuario
     */
    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'No puedes eliminar tu propia cuenta de Administrador.'], 422);
            }
            return back()->with('error', 'No puedes eliminar tu propia cuenta de Administrador.');
        }

        $name = $user->name;
        $user->delete();

        DebugLogService::log('USUARIO_ELIMINADO_ADMIN', ['user_id' => $user->id, 'name' => $name]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => "Usuario '{$name}' eliminado correctamente."]);
        }

        return back()->with('success', "Usuario '{$name}' eliminado del sistema.");
    }
}

