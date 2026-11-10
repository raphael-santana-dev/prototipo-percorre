<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Student\Domain\Models\Student;

class SuriContato extends Model
{
    protected $table = 'suri_contatos';

    protected $fillable = [
        'suri_user_id',
        'inscricao_id',
        'student_id',
        'nome',
        'telefone',
        'email',
        'documento',
        'channel_id',
        'channel_type',
        'queue_status',
        'attendant_id',
        'attendant_name',
        'attendant_email',
        'department_id',
        'tags',
        'metadata',
        'ultima_interacao_em',
    ];

    protected $casts = [
        'tags'                => 'array',
        'metadata'            => 'array',
        'ultima_interacao_em' => 'datetime',
    ];

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class, 'inscricao_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function mensagens()
    {
        return $this->hasMany(SuriMensagem::class, 'suri_contato_id');
    }

    public function atendimentos()
    {
        return $this->hasMany(SuriAtendimento::class, 'suri_contato_id');
    }
}