<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject; // Importar la interfaz
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject 
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'device_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function dives()
    {
        return $this->hasMany(Dive::class);
    }

    /**
     * Obtener el identificador único del usuario para el JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();  // Esto devuelve el ID del usuario
    }

    /**
     * Obtener las claims personalizadas para el JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];  // Aquí puedes añadir datos extra si lo necesitas (por ejemplo, roles, permisos)
    }
}
