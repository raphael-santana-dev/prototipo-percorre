<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Layout('components.layouts.app')]
#[Title('Perfil do Usuário - Administrativo')]
class UserDetails extends Component
{
    public User $user;
    public array $combinacoesVinculadas = [];

    public function mount(int $id)
    {
        $this->user = User::with(['roles', 'permissions', 'unidades'])->findOrFail($id);

        if (Schema::hasTable('user_vinculos_academicos')) {
            $rows = DB::table('user_vinculos_academicos as uv')
                ->join('unidades as u', 'u.id', '=', 'uv.unidade_id')
                ->join('cursos as c', 'c.id', '=', 'uv.curso_id')
                ->join('turnos as t', 't.id', '=', 'uv.turno_id')
                ->where('uv.user_id', $this->user->id)
                ->select('u.nome as unidade_nome', 'c.nome as curso_nome', 't.nome as turno_nome', 'uv.expires_at')
                ->orderBy('u.nome')
                ->orderBy('c.nome')
                ->get();

            foreach ($rows as $r) {
                $expirado = $r->expires_at && \Carbon\Carbon::parse($r->expires_at)->endOfDay()->isPast();
                $this->combinacoesVinculadas[] = [
                    'unidade'    => $r->unidade_nome,
                    'curso'      => $r->curso_nome,
                    'turno'      => $r->turno_nome,
                    'expires_at' => $r->expires_at ? \Carbon\Carbon::parse($r->expires_at)->format('d/m/Y') : null,
                    'expirado'   => $expirado,
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.corporate.user-details');
    }
}