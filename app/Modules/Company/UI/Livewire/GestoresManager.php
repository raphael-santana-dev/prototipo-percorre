<?php

namespace App\Modules\Company\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Modules\Company\Domain\Models\CompanyUser;

use Illuminate\Validation\Rule;

#[Layout('components.layouts.company')]
#[Title('Gerenciar Equipe de Avaliadores')]
class GestoresManager extends Component
{
    use WithPagination;

    public $busca = '';
    public $modalAberto = false;
    
    // Campos do Formulário
    public $gestorId, $name, $email, $documento, $is_active = true;

    public function mount()
    {
        $usuario = Auth::guard('company')->user();
        
        // Proteção estrita: Apenas contatos principais podem acessar esta tela
        abort_if($usuario->tipo_acesso !== 'contato_principal', 403, 'Acesso restrito ao Contato Principal da empresa.');
    }

    public function updatingBusca()
    {
        $this->resetPage();
    }

    public function abrirModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['gestorId', 'name', 'email', 'documento', 'is_active']);

        if ($id) {
            $empresaUser = Auth::guard('company')->user();
            
            // Garante que só pode editar usuários da mesma empresa
            $gestor = CompanyUser::where('empresa_codigo', $empresaUser->empresa_codigo)
                ->where('tipo_acesso', 'gestor_avaliador')
                ->findOrFail($id);

            $this->gestorId = $gestor->id;
            $this->name = $gestor->name;
            $this->email = $gestor->email;
            $this->documento = $gestor->documento;
            $this->is_active = $gestor->is_active;
        }

        $this->modalAberto = true;
    }

    public function salvar()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                // O uso do Rule::unique resolve o bug do Postgres, pois ele ignora o ID se for nulo
                Rule::unique('company_users', 'email')->ignore($this->gestorId) 
            ],
            'documento' => [
                'required',
                'string',
                // Validação Matemática Real de CPF
                function ($attribute, $value, $fail) {
                    $c = preg_replace('/\D/', '', $value); // Remove tudo que não for número
                    
                    if (strlen($c) != 11 || preg_match("/^{$c[0]}{11}$/", $c)) {
                        return $fail('O CPF informado é inválido.');
                    }
                    for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
                    if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
                        return $fail('O CPF informado é inválido.');
                    }
                    for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
                    if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
                        return $fail('O CPF informado é inválido.');
                    }
                }
            ],
        ]);

        $usuario = Auth::guard('company')->user();

        $dados = [
            'name' => $this->name,
            'email' => $this->email,
            'documento' => preg_replace('/\D/', '', $this->documento),
            'empresa_codigo' => $usuario->empresa_codigo,
            'tipo_acesso' => 'gestor_avaliador',
            'is_active' => $this->is_active,
        ];

        // Se for cadastro novo, gera uma senha padrão
        if (!$this->gestorId) {
            $dados['password'] = \Illuminate\Support\Facades\Hash::make('mudar123');
        }

        CompanyUser::updateOrCreate(['id' => $this->gestorId], $dados);

        $this->modalAberto = false;
        session()->flash('sucesso', 'Gestor salvo com sucesso!');
    }

    public function excluir($id)
    {
        $usuario = Auth::guard('company')->user();
        $gestor = CompanyUser::where('empresa_codigo', $usuario->empresa_codigo)
            ->where('tipo_acesso', 'gestor_avaliador')
            ->findOrFail($id);
            
        $gestor->delete();
        session()->flash('sucesso', 'Gestor removido com sucesso.');
    }

    public function render()
    {
        $usuario = Auth::guard('company')->user();

        $gestores = CompanyUser::where('empresa_codigo', $usuario->empresa_codigo)
            ->where('tipo_acesso', 'gestor_avaliador')
            ->where(function($query) {
                $query->where('name', 'ilike', '%' . $this->busca . '%')
                      ->orWhere('email', 'ilike', '%' . $this->busca . '%')
                      ->orWhere('documento', 'like', '%' . preg_replace('/\D/', '', $this->busca) . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.company.gestores-manager', [
            'gestores' => $gestores
        ]);
    }
}