@extends('layouts.app')
@section('titulo', 'Inventario')
@section('contenido')
@php
    $cant = fn ($n, $u = null) => in_array($u, ['UNIDAD', 'CAJA']) ? number_format($n, 0) : rtrim(rtrim(number_format($n, 2, '.', ','), '0'), '.');
    $tipos = ['ENTRADA' => 'Entrada', 'SALIDA_VENTA' => 'Salida por venta', 'ENTRADA_ANULACION' => 'Regreso por anulación'];
    $lineas = old('lineas', [['producto_id' => '', 'cantidad' => '']]);
@endphp
<div class="sv-page-heading"><div><p class="sv-kicker">EXISTENCIAS / P-05</p><h1>Entradas de inventario</h1></div></div>
@if($puedeRegistrar)
<div class="sv-card mb-4"><h2 class="h5">Nueva entrada</h2>
<form method="POST" action="{{ route('inventario.entrada') }}" id="f-entrada" onsubmit="var b=this.querySelector('[type=submit]');b.disabled=true;b.innerHTML='Registrando…';">@csrf
<input type="hidden" name="token" value="{{ old('token', $token) }}">
<div class="row g-3">
<div class="col-12 col-md-3"><label class="form-label" for="motivo">Motivo</label><select id="motivo" name="motivo" class="form-select">@foreach(['COMPRA' => 'Compra a proveedor', 'DEVOLUCION' => 'Devolución', 'AJUSTE' => 'Ajuste'] as $k => $t)<option value="{{ $k }}" @selected(old('motivo') === $k)>{{ $t }}</option>@endforeach</select></div>
<div class="col-12 col-md-5"><label class="form-label" for="proveedor">Proveedor</label><input id="proveedor" name="proveedor" maxlength="150" value="{{ old('proveedor') }}" class="form-control"></div>
<div class="col-12 col-md-4"><label class="form-label" for="documento">Documento (factura, nota)</label><input id="documento" name="documento" maxlength="100" value="{{ old('documento') }}" class="form-control"></div>
</div>
<div id="lineas" class="mt-3">
@foreach($lineas as $i => $l)
<div class="row g-2 align-items-end sv-linea mb-2">
<div class="col-12 col-md-7"><label class="form-label small">Producto</label><select required name="lineas[{{ $i }}][producto_id]" class="form-select"><option value="">Selecciona un producto</option>@foreach($productos as $p)<option value="{{ $p->id }}" @selected(($l['producto_id'] ?? '') == $p->id)>{{ $p->codigo }} · {{ $p->nombre }} ({{ $p->unidad_venta }}, hay {{ $cant($p->existencia, $p->unidad_venta) }}){{ $p->activo ? '' : ' — inactivo' }}</option>@endforeach</select></div>
<div class="col-8 col-md-3"><label class="form-label small">Cantidad</label><input required min="0.01" step="0.01" type="number" name="lineas[{{ $i }}][cantidad]" value="{{ $l['cantidad'] ?? '' }}" class="form-control"></div>
<div class="col-4 col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="quitarLinea(this)">Quitar</button></div>
</div>
@endforeach
</div>
<div class="d-flex flex-wrap gap-2 mt-2"><button type="button" class="btn btn-outline-primary" onclick="agregarLinea()">+ Otro producto</button><button type="submit" class="btn btn-primary">Registrar entrada</button></div>
</form></div>
@endif
<div class="sv-card"><div class="sv-card-header"><h2 class="h5 mb-0">Historial de movimientos</h2><span class="text-muted small">Últimos {{ $movimientos->count() }}</span></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Queda</th><th>Usuario</th><th>Origen</th></tr></thead><tbody>
@forelse($movimientos as $m)
<tr><td>{{ \Illuminate\Support\Carbon::parse($m->fecha)->format('d/m/Y H:i') }}</td><td>{{ $tipos[$m->tipo] ?? $m->tipo }}</td><td><strong>{{ $m->codigo }}</strong> {{ $m->producto }}</td><td class="text-end {{ $m->tipo === 'SALIDA_VENTA' ? 'text-danger' : 'text-success' }}">{{ $m->tipo === 'SALIDA_VENTA' ? '−' : '+' }}{{ $cant($m->cantidad) }}</td><td class="text-end">{{ $cant($m->existencia_resultante) }}</td><td>{{ $m->usuario }}</td><td>@if($m->venta_id)<a href="{{ route('ventas.show', $m->venta_id) }}">Venta N.° {{ $m->venta_numero }}</a>@else Entrada N.° {{ $m->entrada_numero }} · {{ ucfirst(strtolower($m->entrada_motivo)) }}@endif</td></tr>
@empty
<tr><td colspan="7"><x-empty-state title="Sin movimientos" message="Todavía no hay movimientos de inventario." /></td></tr>
@endforelse
</tbody></table></div></div>
@endsection
@push('scripts')<script>
function agregarLinea(){var c=document.getElementById('lineas'),f=c.querySelector('.sv-linea').cloneNode(true),n=c.querySelectorAll('.sv-linea').length+Date.now()%1000;f.querySelectorAll('select,input').forEach(function(e){e.name=e.name.replace(/lineas\[\d+\]/,'lineas['+n+']');e.value='';});c.appendChild(f);}
function quitarLinea(b){var c=document.getElementById('lineas');if(c.querySelectorAll('.sv-linea').length>1){b.closest('.sv-linea').remove();}}
</script>@endpush
