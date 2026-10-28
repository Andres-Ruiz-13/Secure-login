<!doctype html>
<html lang="es" data-bs-theme="light">
    <head>
        <title>Crear Cuenta | INBOT SYSTEM</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
            crossorigin="anonymous"
        />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
        
        <style nonce="{{ $cspNonce ?? '' }}">
            body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; }
            .mono { font-family: 'JetBrains Mono', monospace; }
        </style>
    </head>

    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-4">

        <main class="w-100 px-3" style="max-width: 460px;">
            <div class="text-center mb-3">
                <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                    <span class="p-2 rounded-3 bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm">
                        <i class="bi bi-boxes"></i>
                    </span>
                    <span class="fs-4 fw-bold text-dark">INBOT <span class="text-primary fw-normal">SYSTEM</span></span>
                </a>
            </div>

            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-2 fw-bold text-dark">Crear Cuenta</h3>
                    <p class="text-center text-muted small mb-4">Selecciona tu perfil de acceso para asignar los permisos correspondientes en el sistema.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <div class="fw-bold small mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Corrige los siguientes errores:</div>
                            <ul class="mb-0 small ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold">Nombre Completo *</label>
                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                placeholder="Ej: Carlos Gómez"
                                value="{{ old('name') }}"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold">Correo Electrónico *</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="usuario@empresa.com"
                                value="{{ old('email') }}"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label small fw-semibold">Teléfono (Opcional)</label>
                            <input
                                type="text"
                                class="form-control"
                                id="phone"
                                name="phone"
                                placeholder="Ej: 3001234567"
                                value="{{ old('phone') }}"
                            />
                        </div>

                        <!-- Selección de Rol / Perfil con Explicación de Funciones -->
                        <div class="mb-3">
                            <label for="role" class="form-label small fw-bold">Perfil / Rol Asignado *</label>
                            <select class="form-select fw-semibold" id="role" name="role" required onchange="actualizarInfoRol(this.value)">
                                <option value="vendedor" {{ old('role') == 'vendedor' ? 'selected' : '' }}>Vendedor (Caja / Facturación de Ventas)</option>
                                <option value="bodeguero" {{ old('role') == 'bodeguero' ? 'selected' : '' }}>Bodeguero (Gestión de Inventario y Compras)</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador (Control Total del Sistema)</option>
                            </select>
                            
                            <div id="rolInfoBadge" class="mt-2 p-2 rounded-3 small border bg-success-subtle text-success-emphasis">
                                <i class="bi bi-shield-check me-1"></i>
                                <span id="rolInfoTexto"><b>Vendedor:</b> Facturar ventas con validación y descuento de stock, buscar por código de barras y consultar ventas del día. No crea ni edita productos.</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">Contraseña *</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Mínimo 8 caracteres"
                                minlength="8"
                                required
                            />
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label small fw-semibold">Confirmar Contraseña *</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Repite tu contraseña"
                                minlength="8"
                                required
                            />
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
                            <i class="bi bi-person-check-fill me-1"></i> Registrarse en el Sistema
                        </button>

                        <div class="text-center">
                            <small class="text-muted">¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="text-primary fw-semibold text-decoration-none">Inicia sesión aquí</a></small>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"
        ></script>
        <script nonce="{{ $cspNonce ?? '' }}">
            function actualizarInfoRol(rol) {
                const badge = document.getElementById('rolInfoBadge');
                const texto = document.getElementById('rolInfoTexto');

                badge.className = 'mt-2 p-2 rounded-3 small border ';

                if (rol === 'vendedor') {
                    badge.className += 'bg-success-subtle text-success-emphasis border-success-subtle';
                    texto.innerHTML = '<b>Vendedor:</b> Facturar ventas con validación y descuento de stock, buscar por código de barras y consultar ventas del día. No crea ni edita catálogo ni compras.';
                } else if (rol === 'bodeguero') {
                    badge.className += 'bg-primary-subtle text-primary-emphasis border-primary-subtle';
                    texto.innerHTML = '<b>Bodeguero:</b> Crear y editar categorías, proveedores y productos; registrar facturas de compra y consultar reportes de inventario. No factura ventas.';
                } else if (rol === 'admin') {
                    badge.className += 'bg-danger-subtle text-danger-emphasis border-danger-subtle';
                    texto.innerHTML = '<b>Administrador:</b> Acceso total a todas las operaciones, gestión de usuarios y roles, reportes contables, inventario, compras y ventas.';
                }
            }

            // Inicializar según el valor seleccionado
            document.addEventListener('DOMContentLoaded', () => {
                actualizarInfoRol(document.getElementById('role').value);
            });
        </script>
    </body>
</html>
