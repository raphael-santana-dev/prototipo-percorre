<?php

namespace App\Modules\FeatureToggle\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Feature extends Model
{
    use RegistraAuditoria;
    protected $fillable = [
        'module',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}