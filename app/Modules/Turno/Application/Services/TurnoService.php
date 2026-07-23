<?php

namespace App\Modules\Turno\Application\Services;

use App\Modules\Shared\Application\Services\BaseService;
use App\Modules\Turno\Domain\Repositories\TurnoRepositoryInterface;

class TurnoService extends BaseService
{
    protected TurnoRepositoryInterface $repository;

    public function __construct(TurnoRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function listarTodos()
    {
        return $this->repository->getAll();
    }

    public function criarTurno(array $dados)
    {
        return $this->repository->create($dados);
    }

    public function atualizarTurno(int $id, array $dados)
    {
        return $this->repository->update($id, $dados);
    }

    public function deletarTurno(int $id)
    {
        return $this->repository->delete($id);
    }
    
    public function buscarPorId(int $id)
    {
        return $this->repository->findById($id);
    }
}