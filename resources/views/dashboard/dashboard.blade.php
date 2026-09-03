<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SecureApp</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
    <body>
        <main class="dashboard">
            <h1>Bienvenido, {{auth()->user()->name}}</h1>
            <p>El acceso a esta página requiere autenticación.</p>

            <form method="POST" action="{{ route('logout')}}">
                @csrf
                <button type="submit">Cerrar sesión</button>
            </form>
        </main>
    </body>
</html>