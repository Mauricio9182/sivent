<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIVENT - @yield('titulo')</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/sivent.css') }}" rel="stylesheet">
</head>
<body>
@php
    $role = auth()->check() ? (string) \DB::table('roles')->where('id', auth()->user()->rol_id)->value('nombre') : null;
    $sections = \App\Support\Permisos::roles()[$role] ?? [];
    $links = [
        'panel' => ['Panel principal', 'panel'],
        'ventas' => ['Ventas', 'ventas.index'],
        'productos' => ['Productos', 'productos.index'],
        'clientes' => ['Clientes', 'clientes.index'],
        'inventario' => ['Inventario', 'inventario.index'],
        'consultas' => ['Consultas', 'consultas.index'],
        'auditoria' => ['Auditoría', 'auditoria.index'],
    ];
@endphp
<nav class="sv-topbar">
    <div class="sv-brand"><span class="sv-brand-mark">S</span><a href="{{ auth()->check() ? route('panel') : route('login') }}">SIVENT</a></div>
    @auth
        <button class="btn sv-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sv-sidebar">Menú</button>
        <div class="sv-user">
            <span>{{ auth()->user()->nombre }}</span><small>{{ $role }}</small>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light">Salir</button></form>
        </div>
    @endauth
</nav>
@auth
<aside class="sv-sidebar collapse show" id="sv-sidebar">
    <div class="sv-role">{{ $role }}</div>
    <nav aria-label="Navegación principal">
        @foreach($links as $key => [$label, $route])
            @if(in_array($key, $sections, true))
                <a class="{{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}"><span aria-hidden="true">{{ ['panel' => '⌂', 'ventas' => '↗', 'productos' => '▦', 'clientes' => '♙', 'inventario' => '◈', 'consultas' => '⌕', 'auditoria' => '▤'][$key] }}</span>{{ $label }}</a>
            @endif
        @endforeach
    </nav>
</aside>
@endauth
<main class="sv-main">
    @if (session('ok')) <div class="alert alert-success" role="status">{{ session('ok') }}</div> @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif
    @yield('contenido')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
