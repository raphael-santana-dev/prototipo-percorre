<?php

namespace App\Modules\Turno\Infrastructure\Persistence;

use App\Modules\Shared\Infrastructure\Persistence\EloquentBaseRepository;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Turno\Domain\Repositories\TurnoRepositoryInterface;

class EloquentTurnoRepository extends EloquentBaseRepository implements TurnoRepositoryInterface
{
    // O construtor injeta o model específico do Turno na classe pai (BaseRepository)
    public function __construct(Turno $model)
    {
        parent::__construct($model);
    }
    
    // Métodos específicos de consulta iriam aqui.
    // Os métodos básicos (create, update, delete, getAll) já foram herdados da classe pai!
}