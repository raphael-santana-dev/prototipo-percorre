<?php

namespace App\Modules\Teste\RDCrm\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class RdCrmPipeline extends Model
{
    protected $table = 'rd_crm_pipelines';
    protected $guarded = ['id'];

    public function stages()
    {
        return $this->hasMany(RdCrmDealStage::class, 'rd_crm_pipeline_id')->orderBy('ordem', 'asc');
    }
}