@extends('layouts.app')
@section('titulo', 'Productos')
@section('contenido')
@php $cant = fn ($n, $u) => in_array($u, ['UNIDAD', 'CAJA']) ? number_format($n, 0) : number_format($n, 2); @endphp
<div class="sv-page-heading"><div><p class="sv-kicker">CATÁLOGO / P-03</p><h1>Productos</h1><p class="text-muted mb-0">{{ $productos->count() }} productos registrados.</p></div>@if($puedeEditar)<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#producto-form">+ Nuevo producto</button>@endif</div>
@if($puedeEditar)
<div class="collapse mb-4 {{ old('codigo') !== null ? 'show' : '' }}" id="producto-form"><div class="sv-card"><h2 class="h5">Registrar producto</h2>
<form class="row g-3" method="POST" action="{{ route('productos.store') }}" onsubmit="var b=this.querySelector('[type=submit]');b.disabled=true;b.innerHTML='Guardando…';">
@csrf
<div class="col-12 col-md-3"><label class="form-label" for="codigo">Código único</label><input required maxlength="40" class="form-control @error('codigo') is-invalid @enderror" id="codigo" name="codigo" value="{{ old('codigo') }}" placeholder="HERR-010">@error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12 col-md-5"><label class="form-label" for="nombre">Nombre</label><input required maxlength="180" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}">@error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12 col-md-4"><label class="form-label" for="categoria_id">Categoría</label><select required class="form-select @error('categoria_id') is-invalid @enderror" id="categoria_id" name="categoria_id"><option value="">Selecciona…</option>@foreach($categorias as $c)<option value="{{ $c->id }}" @selected(old('categoria_id') == $c->id)>{{ $c->nombre }}</option>@endforeach</select>@error('categoria_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-6 col-md-3"><label class="form-label" for="precio">Precio (Q)</label><input required min="0.01" step="0.01" type="number" class="form-control @error('precio') is-invalid @enderror" id="precio" name="precio" value="{{ old('precio') }}">@error('precio')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-6 col-md-3"><label class="form-label" for="unidad_venta">Unidad</label><select class="form-select" id="unidad_venta" name="unidad_venta">@foreach(['UNIDAD','CAJA','LIBRA','GALON','METRO'] as $u)<option @selected(old('unidad_venta') === $u)>{{ $u }}</option>@endforeach</select></div>
<div class="col-6 col-md-3"><label class="form-label" for="existencia_minima">Existencia mínima</label><input min="0" step="0.01" type="number" class="form-control @error('existencia_minima') is-invalid @enderror" id="existencia_minima" name="existencia_minima" value="{{ old('existencia_minima', 0) }}">@error('existencia_minima')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><button type="submit" class="btn btn-primary">Guardar producto</button><span class="text-muted ms-2 small">La existencia inicia en 0 y sube con una entrada de inventario.</span></div>
</form></div></div>
@endif
<div class="sv-card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Unidad</th><th class="text-end">Precio</th><th class="text-end">Existencia</th><th class="text-end">Mínimo</th><th>Estado</th>@if($puedeEditar)<th></th>@endif</tr></thead><tbody>
@foreach($productos as $p)
<tr><td><strong>{{ $p->codigo }}</strong></td><td>{{ $p->nombre }}</td><td>{{ $p->categoria }}</td><td>{{ $p->unidad_venta }}</td><td class="text-end">Q {{ number_format($p->precio, 2) }}</td><td class="text-end {{ $p->activo && $p->existencia < $p->existencia_minima ? 'text-danger fw-bold' : '' }}">{{ $cant($p->existencia, $p->unidad_venta) }}</td><td class="text-end">{{ $cant($p->existencia_minima, $p->unidad_venta) }}</td><td><span class="badge text-bg-{{ $p->activo ? 'success' : 'secondary' }}">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span></td>
@if($puedeEditar)<td class="text-end"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editar-{{ $p->id }}">Editar</button></td>@endif</tr>
@if($puedeEditar)
<tr class="collapse" id="editar-{{ $p->id }}"><td colspan="9" class="bg-body-tertiary">
<form class="row g-2 align-items-end" method="POST" action="{{ route('productos.update', $p->id) }}">@csrf
<div class="col-6 col-md-3"><label class="form-label small" for="precio-{{ $p->id }}">Precio (Q)</label><input id="precio-{{ $p->id }}" required min="0.01" step="0.01" type="number" name="precio" value="{{ $p->precio }}" class="form-control form-control-sm"></div>
<div class="col-6 col-md-3"><label class="form-label small" for="minima-{{ $p->id }}">Existencia mínima</label><input id="minima-{{ $p->id }}" min="0" step="0.01" type="number" name="existencia_minima" value="{{ $p->existencia_minima }}" class="form-control form-control-sm"></div>
<div class="col-6 col-md-3"><input type="hidden" name="activo" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" name="activo" value="1" id="activo-{{ $p->id }}" @checked($p->activo)><label class="form-check-label" for="activo-{{ $p->id }}">Activo</label></div></div>
<div class="col-6 col-md-3"><button class="btn btn-sm btn-primary w-100">Guardar cambios</button></div>
</form></td></tr>
@endif
@endforeach
</tbody></table></div></div>
@endsection
