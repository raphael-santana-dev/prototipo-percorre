<?php
namespace App\Modules\Teste\RDCrm\Domain\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class RdCrmDeal extends Model
{ 
    use RegistraAuditoria;
    protected $table = 'rd_crm_deals';
    protected $guarded = ['id'];
    
    protected $casts = [
        'campos_customizados' => 'array',
        'sincronizado' => 'boolean'
    ];

    public function contact()
    {
        return $this->belongsTo(RdCrmContact::class, 'rd_crm_contact_id');
    }
}