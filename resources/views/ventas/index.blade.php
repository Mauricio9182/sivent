@extends('layouts.app')
@section('titulo', 'Ventas')
@section('contenido')
<h4>Ventas</h4>
<table class="table table-sm table-striped bg-white">
    <thead><tr><th>ID</th><th>No.</th><th>Cliente</th><th>Estado</th><th class="text-end">Total</th><th></th></tr></thead>
    <tbody>
    @foreach ($ventas as $v)
        <tr>
            <td>{{ $v->id }}</td><td>{{ $v->numero ?? '—' }}</td><td>{{ $v->cliente ?? 'Consumidor Final' }}</td>
            <td><span class="badge text-bg-{{ $v->estado === 'CONFIRMADA' ? 'success' : ($v->estado === 'ANULADA' ? 'danger' : 'secondary') }}">{{ $v->estado }}</span></td>
            <td class="text-end">{{ $v->total }}</td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.show', $v->id) }}">Ver</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
