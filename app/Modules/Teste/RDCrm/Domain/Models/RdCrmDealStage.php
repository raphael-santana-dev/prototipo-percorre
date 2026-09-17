<?php

namespace App\Modules\Teste\RDCrm\Domain\Models;
use App\Traits\RegistraAuditoria;

use Illuminate\Database\Eloquent\Model;

class RdCrmDealStage extends Model
{
    use RegistraAuditoria;
    protected $table = 'rd_crm_deal_stages';
    protected $guarded = ['id'];

    public function pipeline()
    {
        return $this->belongsTo(RdCrmPipeline::class, 'rd_crm_pipeline_id');
    }
}