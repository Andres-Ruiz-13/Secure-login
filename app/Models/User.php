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
     * Check if the user is a vendedor.
     */
    public function isVendedor(): bool
    {
        return in_array($this->role, ['vendedor', 'Vendedor'], true);
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
