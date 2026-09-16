<?php
namespace App\Modules\Teste\RDCrm\Domain\Models;
use Illuminate\Database\Eloquent\Model;

class RdCrmDeal extends Model
{
    protected $table = 'rd_crm_deals';
    protected $guarded = ['id'];
    
    // Converte automaticamente o JSON do banco para Array no PHP
    protected $casts = [
        'campos_customizados' => 'array',
        'sincronizado' => 'boolean'
    ];

    public function contact()
    {
        return $this->belongsTo(RdCrmContact::class, 'rd_crm_contact_id');
    }
}