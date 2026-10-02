<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'is_active',
    ];

    protected $casts = [
        'port'      => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Cifra la contraseña antes de guardarla en la base de datos si no viene ya cifrada.
     */
    public function setPasswordAttribute(?string $value): void
    {
        if (! empty($value)) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }
}
