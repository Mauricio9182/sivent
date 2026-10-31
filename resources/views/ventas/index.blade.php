@extends('layouts.app')
@section('titulo', 'Ventas')
@section('contenido')
@php $borradores = collect($ventas)->where('estado', 'BORRADOR')->count(); @endphp
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2"><h1 class="h3">Ventas</h1>@if($puedeCrear)<a class="btn btn-primary" href="{{ route('ventas.create') }}">+ Nueva venta</a>@endif</div>
<p class="text-muted">{{ $borradores }} {{ $borradores === 1 ? 'venta pendiente' : 'ventas pendientes' }} de confirmar.</p>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>ID</th><th>N.°</th><th>Cliente</th><th>Estado</th><th class="text-end">Total</th><th></th></tr></thead>
    <tbody>
    @forelse ($ventas as $v)
        <tr>
            <td>{{ $v->id }}</td>
            <td>{{ $v->numero ?? '—' }}</td>
            <td>{{ $v->cliente ?? 'Consumidor Final' }}</td>
            <td><span class="badge text-bg-{{ $v->estado === 'CONFIRMADA' ? 'success' : ($v->estado === 'ANULADA' ? 'danger' : 'secondary') }}">{{ ucfirst(strtolower($v->estado)) }}</span></td>
            <td class="text-end">Q {{ number_format($v->total, 2) }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.show', $v->id) }}">{{ $v->estado === 'BORRADOR' ? 'Revisar y confirmar' : 'Ver comprobante' }}</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted p-4">Todavía no hay ventas registradas.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
