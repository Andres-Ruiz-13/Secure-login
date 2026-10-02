<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determina si el usuario puede listar los productos.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'bodeguero', 'vendedor']);
    }

    /**
     * Determina si el usuario puede ver un producto específico.
     */
    public function view(User $user, Producto $producto): bool
    {
        return $user->hasRole(['admin', 'bodeguero', 'vendedor']);
    }

    /**
     * Determina si el usuario puede crear productos.
     * Vendedor NO puede (devuelve false -> 403 Forbidden).
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'bodeguero']);
    }

    /**
     * Determina si el usuario puede actualizar un producto.
     * Vendedor NO puede (devuelve false -> 403 Forbidden).
     */
    public function update(User $user, Producto $producto): bool
    {
        return $user->hasRole(['admin', 'bodeguero']);
    }

    /**
     * Determina si el usuario puede eliminar un producto.
     * Vendedor NO puede (devuelve false -> 403 Forbidden).
     */
    public function delete(User $user, Producto $producto): bool
    {
        return $user->hasRole(['admin', 'bodeguero']);
    }
}
