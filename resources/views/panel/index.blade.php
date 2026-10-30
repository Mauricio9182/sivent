@extends('layouts.app')
@section('titulo', 'Panel principal')
@section('contenido')
<div class="sv-page-heading"><div><p class="sv-kicker">SIVENT / RESUMEN</p><h1>Panel principal</h1><p class="text-muted mb-0">Bienvenido, {{ auth()->user()->nombre }}. Resumen operativo para {{ $role }}.</p></div></div>
<div class="row g-3 mt-2">
@foreach($stats as $stat)<div class="col-12 col-md-4"><div class="sv-stat-card"><span class="sv-stat-icon">{{ $stat['icon'] }}</span><div><span>{{ $stat['label'] }}</span><strong>{{ $stat['value'] }}</strong></div></div></div>@endforeach
</div>
<div class="row g-4 mt-2">
<div class="col-12 col-lg-7"><div class="sv-card"><div class="sv-card-header"><h2 class="h5 mb-0">Existencias bajas</h2><a href="{{ route('inventario.index') }}">Ver inventario</a></div>
@forelse($lowStock as $product)<div class="sv-list-row"><div><strong>{{ $product['nombre'] }}</strong><small>{{ $product['codigo'] }} · {{ $product['unidad'] }}</small></div><span class="badge text-bg-warning">{{ $product['existencia'] }} {{ $product['existencia'] == 1 ? 'disponible' : 'disponibles' }}</span></div>@empty
<x-empty-state title="Inventario saludable" message="No hay productos por debajo del mínimo." />
@endforelse</div></div>
<div class="col-12 col-lg-5"><div class="sv-card"><div class="sv-card-header"><h2 class="h5 mb-0">Acciones rápidas</h2></div><div class="d-grid gap-2"><a class="btn btn-primary" href="{{ route('ventas.index') }}">Revisar ventas</a><a class="btn btn-outline-primary" href="{{ route('productos.index') }}">Consultar productos</a><a class="btn btn-outline-primary" href="{{ route('consultas.index') }}">Abrir consultas</a></div></div></div>
</div>
@endsection
