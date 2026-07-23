<?php

namespace App\Modules\Unidade\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Unidade extends Model
{
    protected $fillable = ['nome', 'endereco', 'email', 'contatos', 'status'];
    protected $casts = ['status' => 'boolean'];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'unidade_id');
    }
}