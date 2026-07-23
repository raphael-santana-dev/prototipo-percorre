<?php

namespace App\Modules\Turno\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\BaseRepositoryInterface;

interface TurnoRepositoryInterface extends BaseRepositoryInterface
{
    // A interface base já garante getAll, findById, create, update e delete.
    // Você só adiciona métodos aqui se forem exclusivos de Turno, ex:
    // public function buscarTurnosDaManha();
}