<?php
namespace App\Modules\Unidade\Application\Services;
use App\Modules\Shared\Application\Services\BaseService;
use App\Modules\Unidade\Domain\Repositories\UnidadeRepositoryInterface;

class UnidadeService extends BaseService
{
    protected UnidadeRepositoryInterface $repository;

    public function __construct(UnidadeRepositoryInterface $repository) {
        $this->repository = $repository;
    }
    public function listarTodos() { return $this->repository->getAll(); }
    public function criarUnidade(array $dados) { return $this->repository->create($dados); }
    public function atualizarUnidade(int $id, array $dados) { return $this->repository->update($id, $dados); }
    public function deletarUnidade(int $id) { return $this->repository->delete($id); }
    public function buscarPorId(int $id) { return $this->repository->findById($id); }
}