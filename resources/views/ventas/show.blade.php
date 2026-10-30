@extends('layouts.app')
@section('titulo', 'Venta ' . $v['id'])
@section('contenido')
@php $est = $v['estado']; $color = $est === 'CONFIRMADA' ? 'success' : ($est === 'ANULADA' ? 'danger' : 'secondary'); @endphp
<a href="{{ route('ventas.index') }}" class="btn btn-sm btn-link px-0">&laquo; Volver al listado</a>
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <h1 class="h4 mb-0">{{ $est === 'BORRADOR' ? 'Venta pendiente' : 'Comprobante interno' }} #{{ $v['id'] }} @if($v['numero'])<span class="text-muted">· N.° {{ str_pad($v['numero'], 6, '0', STR_PAD_LEFT) }}</span>@endif</h1>
        <span class="badge text-bg-{{ $color }} align-self-start" id="estado">{{ $est === 'ANULADA' ? 'ANULADA' : ucfirst(strtolower($est)) }}</span>
    </div>
    <p class="mt-2 mb-1">Cliente: <strong>{{ $v['cliente'] }}</strong> ({{ $v['cliente_nit'] }})</p>
    @if ($v['confirmado_por'])
        <p class="text-muted small">Confirmada por {{ $v['confirmado_por'] }} el {{ $v['fecha_confirmacion'] }}</p>
    @endif
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Código</th><th>Producto</th><th>Unidad</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($v['lineas'] as $l)
            <tr><td>{{ $l['codigo'] }}</td><td>{{ $l['nombre'] }}</td><td>{{ $l['unidad'] }}</td>
                <td class="text-end">{{ $l['cantidad'] }}</td><td class="text-end">{{ $l['precio'] }}</td><td class="text-end">{{ $l['subtotal'] }}</td></tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="text-end sv-total" id="total">Total Q {{ $v['total'] }}</div>
    <div class="text-end text-muted small">El servidor recalcula el total con los precios vigentes.</div>

    <div id="resultado" role="status" aria-live="polite"></div>

    @if ($est === 'BORRADOR')
        <form method="post" action="{{ route('ventas.confirmar', $v['id']) }}" id="f-conf" class="mt-3">
            @csrf
            <input type="hidden" name="token_confirmacion" value="{{ $v['token'] }}">
            <input type="hidden" name="total_visible" value="{{ $v['total'] }}">
            <button class="btn btn-success" id="b-conf">Confirmar venta</button>
        </form>
    @endif
    @if ($est !== 'BORRADOR')
        <button class="btn btn-outline-secondary mt-3" onclick="window.print()">Imprimir comprobante</button>
        @if (in_array(DB::table('roles')->where('id', auth()->user()->rol_id)->value('nombre'), ['Administrador', 'Gerente'], true))
            <div class="mt-4 pt-3 border-top">
                <h2 class="h6">Anular venta (demostración)</h2>
                <p class="text-muted small">La anulación no modifica la base de datos en este prototipo.</p>
                <form method="post" action="{{ route('ventas.anular-demo', $v['id']) }}" class="row g-2">
                    @csrf
                    <div class="col-12 col-md-8">
                        <label class="visually-hidden" for="motivo_anulacion">Motivo de anulación</label>
                        <input id="motivo_anulacion" name="motivo_anulacion" minlength="10" maxlength="500" required class="form-control" placeholder="Indica un motivo de al menos 10 caracteres">
                    </div>
                    <div class="col-12 col-md-4"><button class="btn btn-outline-danger">Registrar anulación</button></div>
                </form>
            </div>
        @endif
    @endif
</div></div>
@endsection
@push('scripts')
@if ($est === 'BORRADOR')
<script>
(function () {
    var f = document.getElementById('f-conf'), b = document.getElementById('b-conf'), r = document.getElementById('resultado');
    var etiqueta = b.innerHTML;
    function aviso(tipo, html) { r.innerHTML = '<div class="alert alert-' + tipo + ' mt-3" role="' + (tipo === 'danger' ? 'alert' : 'status') + '">' + html + '</div>'; }
    function libre() { b.disabled = false; b.innerHTML = etiqueta; }
    f.addEventListener('submit', function (e) {
        e.preventDefault();
        r.innerHTML = '';
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Confirmando…';
        fetch(f.action, {
            method: 'POST',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
            body: JSON.stringify({token_confirmacion: f.token_confirmacion.value, total_visible: f.total_visible.value})
        }).then(function (resp) {
            return resp.json().catch(function () { return {}; }).then(function (d) { return {s: resp.status, d: d}; });
        }).then(function (x) {
            if (x.s === 401 || x.s === 419) { window.location = '{{ route('login') }}'; return; }
            if (x.d.ok) {
                var c = x.d.comprobante || {};
                aviso('success', (c.repetido ? 'Esta venta ya estaba confirmada; no se duplicó nada.' : '<strong>Venta confirmada.</strong>') +
                    ' N.° ' + (c.numero || '') + ' · Total Q ' + (c.total || '') +
                    '<div class="mt-2"><button class="btn btn-success btn-sm" onclick="location.reload()">Ver comprobante</button></div>');
                f.remove();
                return;
            }
            libre();
            var m = x.d.mensaje || 'No se pudo confirmar la venta. Intente de nuevo.';
            var extra = (x.s === 409 && /total cambi/i.test(m)) ? '<div class="mt-2"><button class="btn btn-outline-danger btn-sm" onclick="location.reload()">Actualizar total</button></div>' : '';
            aviso('danger', m + extra);
        }).catch(function () {
            libre();
            aviso('danger', 'No hay conexión con el servidor. Revisa tu red e intenta de nuevo; la venta sigue pendiente.');
        });
    });
})();
</script>
@endif
@endpush
