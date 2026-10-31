@extends('layouts.app')
@section('titulo', 'Clientes')
@section('contenido')
<div class="sv-page-heading"><div><p class="sv-kicker">MAESTROS / P-04</p><h1>Clientes</h1></div><button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#cliente-form">+ Nuevo cliente</button></div>
<div class="sv-card mb-4"><form class="row g-2 align-items-end"><div class="col-12 col-md-8"><label class="form-label" for="q">Buscar por NIT o nombre</label><input id="q" name="q" value="{{ $query }}" class="form-control" placeholder="Ej. 5487123-4 o Constructora"></div><div class="col-12 col-md-4 d-flex gap-2"><button class="btn btn-primary flex-fill">Buscar</button><a class="btn btn-outline-secondary" href="{{ route('clientes.index') }}">Limpiar</a></div></form></div>
<div class="collapse mb-4 {{ old('nombre') !== null ? 'show' : '' }}" id="cliente-form"><div class="sv-card"><h2 class="h5">Registrar cliente</h2>
<form class="row g-3" method="POST" action="{{ route('clientes.store') }}" onsubmit="var b=this.querySelector('[type=submit]');b.disabled=true;b.innerHTML='Guardando…';">@csrf
<div class="col-md-3"><label class="form-label" for="nit">NIT (vacío o CF = consumidor final)</label><input id="nit" name="nit" maxlength="25" value="{{ old('nit') }}" class="form-control @error('nit') is-invalid @enderror">@error('nit')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-5"><label class="form-label" for="nombre">Nombre</label><input required id="nombre" name="nombre" maxlength="150" value="{{ old('nombre') }}" class="form-control @error('nombre') is-invalid @enderror">@error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-4"><label class="form-label" for="telefono">Teléfono</label><input id="telefono" name="telefono" maxlength="25" value="{{ old('telefono') }}" class="form-control"></div>
<div class="col-12"><label class="form-label" for="direccion">Dirección</label><input id="direccion" name="direccion" maxlength="255" value="{{ old('direccion') }}" class="form-control"></div>
<div class="col-12"><button type="submit" class="btn btn-primary">Guardar cliente</button></div>
</form></div></div>
<div class="sv-card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>NIT</th><th>Nombre</th><th>Teléfono</th><th>Dirección</th></tr></thead><tbody>@forelse($clientes as $c)<tr><td>{{ $c->nit ?? 'CF' }}</td><td><strong>{{ $c->nombre }}</strong></td><td>{{ $c->telefono ?? '—' }}</td><td>{{ $c->direccion ?? '—' }}</td></tr>@empty<tr><td colspan="4"><x-empty-state title="Sin resultados" message="Prueba con otro NIT o nombre." /></td></tr>@endforelse</tbody></table></div></div>
@endsection
