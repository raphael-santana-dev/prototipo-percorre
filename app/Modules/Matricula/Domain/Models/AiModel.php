<?php

namespace App\Modules\Matricula\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $table = 'ai_models';
    protected $guarded = [];

    public function provider()
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }
}