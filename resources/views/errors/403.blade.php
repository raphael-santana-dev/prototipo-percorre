@extends('errors.layout')

@php
    $rawMessage = isset($exception) ? mb_strtolower((string) $exception->getMessage(), 'UTF-8') : '';

    $isFeatureToggle = str_contains($rawMessage, 'desativad')
        || str_contains($rawMessage, 'feature')
        || str_contains($rawMessage, 'módulo')
        || str_contains($rawMessage, 'modulo')
        || str_contains($rawMessage, 'temporariamente');

    $usuarioNome = null;
    $perfilAtual = null;

    if (auth('web')->check()) {
        $u = auth('web')->user();
        $usuarioNome = $u->name;
        $perfilAtual = method_exists($u, 'getRoleNames') ? ($u->getRoleNames()->first() ?: 'Colaborador') : 'Colaborador';
    } elseif (auth('student')->check()) {
        $usuarioNome = auth('student')->user()->name;
        $perfilAtual = 'Estudante';
    } elseif (auth('company')->check()) {
        $usuarioNome = auth('company')->user()->name;
        $perfilAtual = 'Empresa Parceira';
    }
@endphp

@section('title', $isFeatureToggle ? 'Funcionalidade Desativada' : 'Acesso Restrito')
@section('code', '403')
@section('badge_label', $isFeatureToggle ? 'Módulo desativado' : 'Acesso restrito')

@section('heading', $isFeatureToggle
    ? 'Esta funcionalidade está temporariamente desativada'
    : 'Você não tem permissão para acessar esta área')

@section('message')
    @if($isFeatureToggle)
        O recurso ou módulo solicitado está desligado no momento por definição da administração do sistema.
    @else
        O seu perfil de acesso atual não possui a liberação necessária para visualizar esta página ou executar esta ação.
    @endif
@endsection

@section('extra_content')
    <div class="space-y-3 text-xs">
        @if($usuarioNome)
            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500 dark:text-gray-400">Conta conectada</span>
                <div class="flex items-center gap-2 min-w-0">
                    <span class="font-medium text-gray-900 dark:text-white truncate">{{ $usuarioNome }}</span>
                    @if($perfilAtual)
                        <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-[10px] font-medium uppercase text-gray-600 dark:text-gray-300 shrink-0">
                            {{ $perfilAtual }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        <p class="text-gray-500 dark:text-gray-400 leading-relaxed">
            @if($isFeatureToggle)
                Caso precise utilizar este recurso agora, solicite ao administrador a ativação da chave correspondente em <strong class="text-gray-700 dark:text-gray-200 font-medium">Feature Toggles</strong>.
            @else
                Se você precisa deste acesso para suas atividades, solicite à coordenação ou ao administrador a liberação da permissão no seu perfil.
            @endif
        </p>
    </div>
@endsection