<?php

namespace App\Modules\Teste\RDCrm\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class RdCrmDealStage extends Model
{
    protected $table = 'rd_crm_deal_stages';
    protected $guarded = ['id'];

    public function pipeline()
    {
        return $this->belongsTo(RdCrmPipeline::class, 'rd_crm_pipeline_id');
    }
}