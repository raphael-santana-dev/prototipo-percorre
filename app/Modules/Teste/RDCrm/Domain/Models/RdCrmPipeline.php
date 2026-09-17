<?php

namespace App\Modules\Teste\RDCrm\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class RdCrmPipeline extends Model
{
    use RegistraAuditoria;
    protected $table = 'rd_crm_pipelines';
    protected $guarded = ['id'];

    public function stages()
    {
        return $this->hasMany(RdCrmDealStage::class, 'rd_crm_pipeline_id')->orderBy('ordem', 'asc');
    }
}