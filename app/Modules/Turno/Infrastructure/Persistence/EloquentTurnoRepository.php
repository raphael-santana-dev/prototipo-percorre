<?php

namespace App\Modules\Turno\Infrastructure\Persistence;

use App\Modules\Shared\Infrastructure\Persistence\EloquentBaseRepository;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Turno\Domain\Repositories\TurnoRepositoryInterface;

class EloquentTurnoRepository extends EloquentBaseRepository implements TurnoRepositoryInterface
{
    public function __construct(Turno $model)
    {
        parent::__construct($model);
    }
}