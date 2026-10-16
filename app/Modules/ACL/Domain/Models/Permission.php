<?php

namespace App\Modules\ACL\Domain\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use App\Traits\RegistraAuditoria;

class Permission extends SpatiePermission
{
    use RegistraAuditoria;
    protected $fillable = [
        'name',
        'guard_name',
        'module',
        'submodule',
        'description',
        'updated_at',
        'created_at'
    ];
}