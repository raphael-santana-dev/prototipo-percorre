@extends('errors.layout')

@section('title', 'Página Não Encontrada')
@section('accent_bar', 'bg-indigo-500')
@section('icon_wrapper', 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800')
@section('icon', 'ph-fill ph-compass')
@section('badge_classes', 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800')
@section('badge_label', 'Conteúdo Não Localizado')

@section('heading', 'Não encontramos o que você procurava')

@section('message')
    O endereço acessado pode ter sido alterado, digitado incorretamente ou o registro que você tentou abrir não está mais disponível no sistema.
@endsection