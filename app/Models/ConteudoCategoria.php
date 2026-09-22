<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConteudoCategoria extends Model
{
    use SoftDeletes;

    protected $table = 'conteudo_categorias';
    protected $fillable = ['nome', 'slug', 'is_active'];

    public function conteudos()
    {
        return $this->hasMany(Conteudo::class, 'categoria_id');
    }
}