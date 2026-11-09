@extends('errors.layout')

@section('title', 'Muitas Solicitações')
@section('accent_bar', 'bg-amber-500')
@section('icon_wrapper', 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800')
@section('icon', 'ph-fill ph-hand-palm')
@section('badge_classes', 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800')
@section('badge_label', 'Proteção de Tráfego')

@section('heading', 'Muitas ações em poucos segundos')

@section('message')
    Identificamos um volume muito alto de solicitações seguidas a partir da sua conexão. Aguarde alguns instantes antes de tentar novamente.
@endsection