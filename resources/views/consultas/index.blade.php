@extends('layouts.app')
@section('titulo', 'Consultas')
@section('contenido')
@php $cant = fn ($n, $u) => in_array($u, ['UNIDAD', 'CAJA']) ? number_format($n, 0) : number_format($n, 2); @endphp
<div class="sv-page-heading"><div><p class="sv-kicker">REPORTES / P-08</p><h1>Consultas</h1><p class="text-muted">Las ventas anuladas quedan excluidas de los totales.</p></div></div>
<div class="sv-card mb-4"><h2 class="h5">Ventas por período</h2>
<form class="row g-3" method="GET" action="{{ route('consultas.index') }}"><div class="col-6 col-md-3"><label class="form-label" for="desde">Desde</label><input id="desde" name="desde" required type="date" class="form-control" value="{{ $desde }}"></div><div class="col-6 col-md-3"><label class="form-label" for="hasta">Hasta</label><input id="hasta" name="hasta" required type="date" class="form-control" value="{{ $hasta }}"></div><div class="col-12 col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100">Consultar</button></div></form>
<div class="row g-3 mt-2">
<div class="col-6 col-md-3"><div class="sv-mini-stat"><span>Ventas confirmadas</span><strong>{{ $resumen['cantidad'] }}</strong></div></div>
<div class="col-6 col-md-3"><div class="sv-mini-stat"><span>Total vendido</span><strong>Q {{ number_format($resumen['total'], 2) }}</strong></div></div>
<div class="col-6 col-md-3"><div class="sv-mini-stat"><span>Promedio por venta</span><strong>Q {{ number_format($resumen['promedio'], 2) }}</strong></div></div>
<div class="col-6 col-md-3"><div class="sv-mini-stat"><span>Anuladas en el período</span><strong>{{ $resumen['anuladas'] }}</strong></div></div>
</div>
@if($resumen['ventas']->isNotEmpty())
<div class="table-responsive mt-3"><table class="table table-sm align-middle"><thead><tr><th>N.°</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="text-end">Total</th></tr></thead><tbody>
@foreach($resumen['ventas'] as $v)<tr><td><a href="{{ route('ventas.show', $v->id) }}">{{ str_pad($v->numero, 6, '0', STR_PAD_LEFT) }}</a></td><td>{{ \Illuminate\Support\Carbon::parse($v->fecha_confirmacion)->format('d/m/Y H:i') }}</td><td>{{ $v->cliente ?? 'Consumidor Final' }}</td><td>{{ $v->usuario }}</td><td class="text-end">Q {{ number_format($v->total, 2) }}</td></tr>@endforeach
</tbody></table></div>
@endif
</div>
<div class="sv-card"><div class="sv-card-header"><h2 class="h5 mb-0">Existencias bajas</h2><span class="text-muted small">{{ $bajas->count() }} {{ $bajas->count() === 1 ? 'producto' : 'productos' }}</span></div><div class="table-responsive"><table class="table"><thead><tr><th>Código</th><th>Producto</th><th class="text-end">Existencia</th><th class="text-end">Mínimo</th></tr></thead><tbody>
@forelse($bajas as $p)<tr><td>{{ $p->codigo }}</td><td>{{ $p->nombre }}</td><td class="text-end text-danger fw-bold">{{ $cant($p->existencia, $p->unidad_venta) }}</td><td class="text-end">{{ $cant($p->existencia_minima, $p->unidad_venta) }}</td></tr>@empty<tr><td colspan="4"><x-empty-state title="Inventario saludable" message="Ningún producto activo está por debajo del mínimo." /></td></tr>@endforelse
</tbody></table></div></div>
@endsection
