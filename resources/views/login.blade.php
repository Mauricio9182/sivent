@extends('layouts.app')
@section('titulo', 'Ingreso')
@section('contenido')
<div class="row justify-content-center"><div class="col-md-4">
    <div class="card"><div class="card-body">
        <h5 class="card-title">Iniciar sesión</h5>
        <form method="post" action="{{ url('/login') }}">
            @csrf
            <div class="mb-3"><label class="form-label">Usuario</label>
                <input name="usuario" class="form-control" value="{{ old('usuario') }}" autofocus></div>
            <div class="mb-3"><label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control"></div>
            <button class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div></div>
</div></div>
@endsection
