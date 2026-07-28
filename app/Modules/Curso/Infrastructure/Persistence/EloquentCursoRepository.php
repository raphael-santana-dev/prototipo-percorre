<?php

namespace App\Modules\Curso\Infrastructure\Persistence;

use App\Modules\Shared\Infrastructure\Persistence\EloquentBaseRepository;
use App\Models\Curso; // Usando o Model consolidado que criamos
use App\Modules\Curso\Domain\Repositories\CursoRepositoryInterface;

class EloquentCursoRepository extends EloquentBaseRepository implements CursoRepositoryInterface
{
    public function __construct(Curso $model) 
    { 
        parent::__construct($model); 
    }
}