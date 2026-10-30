@extends('layouts.app')
@section('titulo', 'Ingreso')
@section('contenido')
<div class="sv-login-page">
    <div class="sv-login-intro">
        <span class="sv-login-mark">S</span>
        <p class="sv-kicker">SISTEMA DE VENTAS E INVENTARIO</p>
        <h1>Todo el control<br><em>en un solo lugar.</em></h1>
        <p>Gestiona ventas, existencias y clientes de Ferretería El Constructor con información clara para cada rol.</p>
    </div>
    <div class="sv-login-card">
        <div class="sv-login-card-header">
            <span class="sv-login-eyebrow">Acceso al sistema</span>
            <h2>Iniciar sesión</h2>
            <p>Ingresa tus credenciales para continuar.</p>
        </div>
        <form method="post" action="{{ url('/login') }}" id="f-login" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label" for="usuario">Usuario</label>
                <input id="usuario" name="usuario" class="form-control @error('usuario') is-invalid @enderror"
                       value="{{ old('usuario') }}" autofocus autocomplete="username">
                @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary w-100" id="b-login">Iniciar sesión</button>
        </form>
        <p class="sv-login-help">¿Problemas para entrar? Contacta al administrador del sistema.</p>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('f-login').addEventListener('submit', function (e) {
    var u = this.usuario, p = this.password, ok = true;
    [u, p].forEach(function (c) { c.classList.remove('is-invalid'); });
    if (!u.value.trim()) { u.classList.add('is-invalid'); ok = false; }
    if (!p.value) { p.classList.add('is-invalid'); ok = false; }
    if (!ok) { e.preventDefault(); return; }
    var b = document.getElementById('b-login');
    b.disabled = true;
    b.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Verificando…';
});
</script>
@endpush
