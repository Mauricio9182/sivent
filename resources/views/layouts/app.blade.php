<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIVENT - @yield('titulo')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('ventas.index') }}">SIVENT</a>
        @auth
            <form method="post" action="{{ route('logout') }}" class="d-flex align-items-center gap-2">
                @csrf
                <span class="text-white-50 small">{{ auth()->user()->nombre }}</span>
                <button class="btn btn-sm btn-outline-light">Salir</button>
            </form>
        @endauth
    </div>
</nav>
<main class="container">
    @if (session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif
    @yield('contenido')
</main>
</body>
</html>
