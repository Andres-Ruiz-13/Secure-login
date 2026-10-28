<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | SecureApp</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Fuente Inter para tipografía moderna -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style nonce="{{ $cspNonce ?? '' }}">
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-badge {
            display: inline-block;
            background-color: #e0e7ff;
            color: #4338ca;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .login-header h1 {
            color: #0f172a;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Alertas */
        .alert-error {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .alert-error ul {
            margin-top: 0.5rem;
            padding-left: 1.25rem;
        }

        .alert-success {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            color: #166534;
            padding: 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        /* Formulario */
        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            color: #334155;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s ease;
            outline: none;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        /* Contraseña y botón toggle */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 4.5rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6366f1;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
        }

        .password-toggle:hover {
            color: #4338ca;
        }

        /* Checkbox */
        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #475569;
            cursor: pointer;
            margin-bottom: 1.5rem;
        }

        .remember input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: #6366f1;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Botón Submit */
        .button-primary {
            width: 100%;
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.85rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .button-primary:hover {
            background-color: #4338ca;
        }

        .button-primary:active {
            transform: scale(0.99);
        }

        /* Footer con enlace a registro */
        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 1.25rem;
        }

        .login-footer a {
            color: #4f46e5;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .login-footer a:hover {
            color: #3730a3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-card">
            <div class="login-header">
                <span class="login-badge">SecureApp</span>
                <h1>Iniciar sesión</h1>
                <p>Acceda utilizando sus credenciales institucionales.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <strong>No fue posible iniciar sesión:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input id="email" type="email" name="email"
                        value="{{ old('email') }}"
                        placeholder="ejemplo@dominio.com"
                        autocomplete="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-wrapper">
                        <input id="password" type="password" name="password"
                            placeholder="••••••••"
                            autocomplete="current-password" required>
                        <button id="togglePassword" type="button" class="password-toggle" aria-label="Mostrar contraseña">Mostrar</button>
                    </div>
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember" value="1">
                    Recordarme en este equipo
                </label>

                <button type="submit" class="button-primary">Ingresar</button>
            </form>

            <div class="login-footer">
                ¿No tienes una cuenta? <a href="{{ route('register') }}">Regístrate aquí</a>
            </div>
        </section>
    </main>

    <!-- Script funcional para el botón de mostrar/ocultar contraseña -->
    <script nonce="{{ $cspNonce ?? '' }}">
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const isPassword = passwordInput.type === 'password';
            
            passwordInput.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? 'Ocultar' : 'Mostrar';
        });
    </script>
</body>
</html>