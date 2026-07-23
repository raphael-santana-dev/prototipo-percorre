<?php
namespace App\Modules\Unidade\Infrastructure\Persistence;
use App\Modules\Shared\Infrastructure\Persistence\EloquentBaseRepository;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Unidade\Domain\Repositories\UnidadeRepositoryInterface;

class EloquentUnidadeRepository extends EloquentBaseRepository implements UnidadeRepositoryInterface
{
    public function __construct(Unidade $model) { parent::__construct($model); }
}