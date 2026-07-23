<?php

namespace App\Modules\ACL\Domain\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // Liberamos o preenchimento em massa para as nossas novas colunas
    protected $fillable = [
        'name',
        'guard_name',
        'module',
        'description',
        'updated_at',
        'created_at'
    ];
}