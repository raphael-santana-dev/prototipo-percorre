<?php

namespace App\Modules\Student\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\Tenantable;


class Student extends Authenticatable
{
    use HasFactory, Notifiable, Tenantable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'unidade_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
}