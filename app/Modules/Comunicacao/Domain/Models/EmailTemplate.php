<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class EmailTemplate extends Model
{
    
    use RegistraAuditoria;
    protected $table = 'email_templates';
    protected $guarded = [];
}