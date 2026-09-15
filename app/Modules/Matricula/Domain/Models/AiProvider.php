<?php

namespace App\Modules\Matricula\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $table = 'ai_providers';
    protected $guarded = [];

    public function modelos()
    {
        return $this->hasMany(AiModel::class, 'ai_provider_id');
    }
}