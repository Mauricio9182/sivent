@extends('layouts.app')
@section('titulo', 'Acceso denegado')
@section('contenido')
<div class="sv-empty-state text-center">
    <div class="display-4 text-danger">403</div>
    <h1 class="h3">Acceso denegado</h1>
    <p class="text-muted">Tu rol no tiene permiso para consultar esta opción. Usa una sección disponible en el menú.</p>
    <a class="btn btn-primary" href="{{ route('panel') }}">Volver al panel</a>
</div>
@endsection
