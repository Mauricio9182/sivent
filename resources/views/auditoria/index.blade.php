@extends('layouts.app')
@section('titulo', 'Auditoría')
@section('contenido')
<div class="sv-page-heading"><div><p class="sv-kicker">CONTROL / P-09</p><h1>Auditoría</h1><p class="text-muted">Registro de solo lectura de las operaciones del sistema.</p></div></div><x-demo-alert />
<div class="sv-card mb-4"><form class="row g-2 align-items-end"><div class="col-12 col-md-8"><label class="form-label">Filtrar por usuario u operación</label><input class="form-control" name="q" value="{{ $query }}" placeholder="Ej. vendedor1 o Confirmar venta"></div><div class="col-12 col-md-4 d-flex gap-2"><button class="btn btn-primary flex-fill">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('auditoria.index') }}">Limpiar</a></div></form></div>
<div class="sv-card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Fecha</th><th>Usuario</th><th>Operación</th><th>Entidad</th><th>Detalle</th></tr></thead><tbody>@forelse($registros as $r)<tr><td>{{ $r['fecha'] }}</td><td><strong>{{ $r['usuario'] }}</strong></td><td>{{ $r['operacion'] }}</td><td>{{ $r['entidad'] }}</td><td>{{ $r['detalle'] }}</td></tr>@empty<tr><td colspan="5"><x-empty-state title="Sin registros" message="No hay operaciones que coincidan con el filtro." /></td></tr>@endforelse</tbody></table></div></div>
@endsection
