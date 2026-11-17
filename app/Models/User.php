<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    /**
     * Check if the user has administrator privileges.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin', 'Administrador'], true);
    }

    /**
     * Check if the user is a bodeguero.
     */
    public function isBodeguero(): bool
    {
        return in_array($this->role, ['bodeguero', 'Bodeguero'], true);
    }

    /**
     * Check if the user is a vendedor o cajero.
     */
    public function isVendedor(): bool
    {
        return in_array($this->role, ['vendedor', 'Vendedor', 'cajero', 'Cajero'], true);
    }

    /**
     * Check if the user is a cajero (alias de vendedor).
     */
    public function isCajero(): bool
    {
        return $this->isVendedor();
    }

    /**
     * Nombre formal y legible del rol.
     */
    public function getRoleTitleAttribute(): string
    {
        return match (strtolower($this->role ?? 'customer')) {
            'admin', 'superadmin', 'administrador' => 'Administrador',
            'bodeguero' => 'Bodeguero',
            'cajero', 'vendedor' => 'Cajero / Punto de Venta',
            default => 'Cliente / Usuario',
        };
    }

    /**
     * Color característico de la interfaz según el rol.
     */
    public function getRoleColorAttribute(): string
    {
        return match (strtolower($this->role ?? 'customer')) {
            'admin', 'superadmin', 'administrador' => '#dc2626', // Rojo Carmesí
            'bodeguero' => '#2563eb',                           // Azul Océano
            'cajero', 'vendedor' => '#059669',                   // Verde Esmeralda
            default => '#64748b',                                // Gris pizarra
        };
    }

    /**
     * Clase CSS de badge para el rol.
     */
    public function getRoleBadgeClassAttribute(): string
    {
        return match (strtolower($this->role ?? 'customer')) {
            'admin', 'superadmin', 'administrador' => 'bg-danger text-white shadow-sm',
            'bodeguero' => 'bg-primary text-white shadow-sm',
            'cajero', 'vendedor' => 'bg-success text-white shadow-sm',
            default => 'bg-secondary text-white shadow-sm',
        };
    }

    /**
     * Icono representativo del rol.
     */
    public function getRoleIconAttribute(): string
    {
        return match (strtolower($this->role ?? 'customer')) {
            'admin', 'superadmin', 'administrador' => 'bi-shield-lock-fill',
            'bodeguero' => 'bi-boxes',
            'cajero', 'vendedor' => 'bi-cash-coin',
            default => 'bi-person',
        };
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_users', 'user_id', 'warehouse_id')
            ->withTimestamps();
    }

    public function cashSessions()
    {
        return $this->hasMany(CashSession::class, 'user_id');
    }

    public function currentCashSession()
    {
        return $this->hasOne(CashSession::class, 'user_id')
            ->where('estado', 'abierta')
            ->latest('opened_at');
    }

    public function newsReads()
    {
        return $this->hasMany(NewsRead::class, 'user_id');
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        // Admin has superuser access
        if ($this->isAdmin() && in_array('admin', $roles, true)) {
            return true;
        }

        $normalizedUserRole = strtolower($this->role ?? 'user');
        $normalizedRoles = array_map('strtolower', $roles);

        return in_array($normalizedUserRole, $normalizedRoles, true);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Bookings made by this user.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
