@extends('layouts.app')
@section('titulo', 'Nueva venta')
@section('contenido')
@php $lineas = old('lineas', [['producto_id' => '', 'cantidad' => '1']]); @endphp
<a href="{{ route('ventas.index') }}" class="btn btn-sm btn-link px-0">&laquo; Volver al listado</a>
<div class="sv-card"><h1 class="h4">Nueva venta</h1>
<p class="text-muted">Se guarda como borrador; el inventario se descuenta al confirmarla.</p>
<form method="POST" action="{{ route('ventas.store') }}" onsubmit="var b=this.querySelector('[type=submit]');b.disabled=true;b.innerHTML='Guardando…';">@csrf
<div class="row g-3 mb-2"><div class="col-12 col-md-6"><label class="form-label" for="cliente_id">Cliente</label><select id="cliente_id" name="cliente_id" class="form-select"><option value="">Consumidor Final (CF)</option>@foreach($clientes as $c)<option value="{{ $c->id }}" @selected(old('cliente_id') == $c->id)>{{ $c->nombre }}{{ $c->nit ? ' · '.$c->nit : '' }}</option>@endforeach</select></div></div>
<div id="lineas">
@foreach($lineas as $i => $l)
<div class="row g-2 align-items-end sv-linea mb-2">
<div class="col-12 col-md-7"><label class="form-label small">Producto</label><select required name="lineas[{{ $i }}][producto_id]" class="form-select" onchange="calcular()"><option value="">Selecciona un producto</option>@foreach($productos as $p)<option value="{{ $p->id }}" data-precio="{{ $p->precio }}" @selected(($l['producto_id'] ?? '') == $p->id)>{{ $p->codigo }} · {{ $p->nombre }} · Q {{ number_format($p->precio, 2) }} / {{ $p->unidad_venta }} (hay {{ in_array($p->unidad_venta, ['UNIDAD','CAJA']) ? number_format($p->existencia, 0) : number_format($p->existencia, 2) }})</option>@endforeach</select></div>
<div class="col-8 col-md-3"><label class="form-label small">Cantidad</label><input required min="0.01" step="0.01" type="number" name="lineas[{{ $i }}][cantidad]" value="{{ $l['cantidad'] ?? '' }}" class="form-control" oninput="calcular()"></div>
<div class="col-4 col-md-2"><button type="button" class="btn btn-outline-danger w-100" onclick="quitarLinea(this)">Quitar</button></div>
</div>
@endforeach
</div>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><button type="button" class="btn btn-outline-primary" onclick="agregarLinea()">+ Otro producto</button><div class="sv-total" id="estimado">Total estimado Q 0.00</div></div>
<div class="text-end text-muted small">El servidor vuelve a calcular el total con los precios vigentes.</div>
<div class="mt-3"><button type="submit" class="btn btn-primary">Guardar borrador</button></div>
</form></div>
@endsection
@push('scripts')<script>
function calcular(){var t=0;document.querySelectorAll('#lineas .sv-linea').forEach(function(r){var s=r.querySelector('select'),q=parseFloat(r.querySelector('input').value)||0,o=s.options[s.selectedIndex],p=o?parseFloat(o.dataset.precio||0):0;t+=Math.round(q*p*100)/100;});document.getElementById('estimado').textContent='Total estimado Q '+t.toFixed(2);}
function agregarLinea(){var c=document.getElementById('lineas'),f=c.querySelector('.sv-linea').cloneNode(true),n=Date.now();f.querySelectorAll('select,input').forEach(function(e){e.name=e.name.replace(/lineas\[\d+\]/,'lineas['+n+']');e.value=e.tagName==='INPUT'?'1':'';});c.appendChild(f);calcular();}
function quitarLinea(b){var c=document.getElementById('lineas');if(c.querySelectorAll('.sv-linea').length>1){b.closest('.sv-linea').remove();calcular();}}
calcular();
</script>@endpush
