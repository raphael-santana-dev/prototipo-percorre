<?php
namespace App\Modules\Teste\RDCrm\Domain\Models;
use Illuminate\Database\Eloquent\Model;

class RdCrmContact extends Model
{
    protected $table = 'rd_crm_contacts';
    protected $guarded = ['id'];

    public function deals()
    {
        return $this->hasMany(RdCrmDeal::class, 'rd_crm_contact_id');
    }
}