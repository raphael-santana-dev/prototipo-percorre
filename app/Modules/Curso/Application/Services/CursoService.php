<?php

namespace App\Modules\Curso\Application\Services;

use App\Modules\Shared\Application\Services\BaseService;
use App\Modules\Curso\Domain\Repositories\CursoRepositoryInterface;

class CursoService extends BaseService
{
    protected CursoRepositoryInterface $repository;

    public function __construct(CursoRepositoryInterface $repository) 
    {
        $this->repository = $repository;
    }
    
    public function listarTodos() { return $this->repository->getAll(); }
    public function criarCurso(array $dados) { return $this->repository->create($dados); }
    public function atualizarCurso(int $id, array $dados) { return $this->repository->update($id, $dados); }
    public function deletarCurso(int $id) { return $this->repository->delete($id); }
    public function buscarPorId(int $id) { return $this->repository->findById($id); }

    public function sincronizarRelacionamentos(int $cursoId, array $unidadesIds, array $turnosIds) 
    {
        $curso = $this->repository->findById($cursoId);
        $curso->unidades()->sync($unidadesIds);
        $curso->turnosVinculados()->sync($turnosIds);
    }
}