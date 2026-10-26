@extends('layouts.app')
@section('titulo', 'Venta ' . $v['id'])
@section('contenido')
<a href="{{ route('ventas.index') }}" class="btn btn-sm btn-link">&laquo; Volver</a>
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between">
        <h5>Venta #{{ $v['id'] }} @if($v['numero']) &middot; No. {{ $v['numero'] }} @endif</h5>
        <span class="badge text-bg-{{ $v['estado'] === 'CONFIRMADA' ? 'success' : ($v['estado'] === 'ANULADA' ? 'danger' : 'secondary') }} align-self-start">{{ $v['estado'] }}</span>
    </div>
    <p class="mb-1">Cliente: <strong>{{ $v['cliente'] }}</strong> ({{ $v['cliente_nit'] }})</p>
    @if ($v['confirmado_por'])
        <p class="text-muted small">Confirmada por {{ $v['confirmado_por'] }} el {{ $v['fecha_confirmacion'] }}</p>
    @endif

    <table class="table table-sm">
        <thead><tr><th>Código</th><th>Producto</th><th>Unidad</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($v['lineas'] as $l)
            <tr><td>{{ $l['codigo'] }}</td><td>{{ $l['nombre'] }}</td><td>{{ $l['unidad'] }}</td>
                <td class="text-end">{{ $l['cantidad'] }}</td><td class="text-end">{{ $l['precio'] }}</td>
                <td class="text-end">{{ $l['subtotal'] }}</td></tr>
        @endforeach
        </tbody>
        <tfoot><tr><th colspan="5" class="text-end">Total Q</th><th class="text-end">{{ $v['total'] }}</th></tr></tfoot>
    </table>

    @if ($v['estado'] === 'BORRADOR')
        <form method="post" action="{{ route('ventas.confirmar', $v['id']) }}"
              onsubmit="this.querySelector('button').disabled = true;">
            @csrf
            <input type="hidden" name="token_confirmacion" value="{{ $v['token'] }}">
            <input type="hidden" name="total_visible" value="{{ $v['total'] }}">
            <button class="btn btn-success">Confirmar venta</button>
        </form>
    @endif
</div></div>
@endsection
