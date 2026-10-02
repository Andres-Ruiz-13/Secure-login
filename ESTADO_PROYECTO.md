# ESTADO DEL PROYECTO — INBOT SYSTEM & CMS MODULAR

## Entorno
- **Laravel:** 12.68.0
- **PHP:** 8.2.12 (cli) ZTS Visual C++ 2019 x64
- **Base de datos:** MySQL / MariaDB 10.4.32 (XAMPP - puerto 3306, base `inbot_system`, utf8mb4)
- **Frontend:** Blade + Tailwind CSS + Bootstrap Icons + Vite
- **Autenticación:** Laravel Sanctum (Tokens Bearer) + Sesiones Web seguras

---

## Módulos del Sistema y Estado de Cierre

- [x] **Fase 0 — Base:** Arquitectura inicial, Laravel 12 configurado, conexión a MariaDB XAMPP verificada.
- [x] **Fase 0.1 — Login Seguro:** Rate limiting (5 intentos/minuto por email+IP), sesiones protegidas (HttpOnly, SameSite), protección contra enumeración de usuarios.
- [x] **Módulo 1 — Banners / Hero:** Tabla `banners`, modelo, scope `activeVisible`, frontend y control de orden.
- [x] **Módulo 2 — Servicios:** Tabla `services`, modelo, slug único, estado activo y precios en decimal.
- [x] **Módulo 3 — Nosotros / Institucional:** Tabla `about_settings`, misión, visión, historia y valores dinámicos.
- [x] **Módulo 4 — Categorías:** Tabla `categories`, modelo, validación de slug único y relación segura con productos.
- [x] **Módulo 5 — Publicaciones / Noticias:** Tabla `news`, relación con multimedia, estados editoriales y frontend público.
- [x] **Módulo 6 — Multimedia / Galería:** Tabla `media`, validación de tipo MIME real, tamaño máx 5MB, almacenamiento en `storage/app/public`, reemplazo eliminando archivo físico anterior.
- [x] **Módulo 7 — Videos:** Tabla `videos`, incrustación segura para YouTube y Vimeo sin iframes arbitrarios.
- [x] **Módulo 8 — Equipo:** Tabla `team_members`, administración de cargos y biografías.
- [x] **Módulo 9 — Testimonios:** Tabla `testimonials`, calificaciones de 1 a 5 y control de visualización activa.
- [x] **Módulo 10 — Contacto:** Tabla `contact_messages`, formulario web y API `/contacto`, mailable `ContactMessage`, plantilla Blade `#022766`, envío a admin y confirmación a usuario.
- [x] **Módulo 11 — Redes Sociales:** Tabla `social_links`, URLs validadas y ordenables.
- [x] **Módulo 12 — SEO:** Configuración general con metadatos y OpenGraph.
- [x] **Módulo 13 & 14 — Usuarios, Roles y Permisos (RBAC):** Roles `admin`, `bodeguero`, `vendedor`. Middleware `role`, Policies (`ProductoPolicy`), restricción estricta de 403 Forbidden para Vendedor en creación/edición de productos.
- [x] **Módulo 15 — Configuración General:** Tabla `site_settings`, pares clave-valor sin exponer secretos de infraestructura.
- [x] **Módulo 16 — SMTP Administrable:** Tabla `mail_settings`, contraseñas cifradas con `Laravel Crypt`, servicio `MailConfigurationService` con fallback seguro a `.env`.
- [x] **Módulo 17 — Recuperación de Contraseña por OTP:** Tabla `password_reset_codes`, hash del código (nunca texto plano), expiración de 10 min y límite de intentos.
- [x] **Módulo 18 — Auditoría:** Tabla `audit_logs`, servicio `AuditService`, registro de eventos sin filtrar contraseñas ni tokens.
- [x] **Módulo 19 — Dashboard y Estadísticas:** Panel central con métricas reales, conteo de stock bajo y actividad reciente.
- [x] **Módulo 20 — Hardening de Seguridad:** Middleware `SecurityHeaders` (nosniff, X-Frame-Options, CSP, Referrer-Policy, HSTS), prevención de mass assignment (`$fillable` explícito), prevención de consultas N+1 con `preventLazyLoading()`.
- [x] **Módulo 20.2 — Testing Final:** 23 pruebas automatizadas ejecutadas y aprobadas al 100%.
- [x] **Módulo A (INBOT) — Productos e Inventario:** Código de barras único, cálculo automático de precio de venta en backend con redondeo comercial configurable en `config/inbot.php`, endpoint `GET /api/stock_bajo`, paginación y filtros.
- [x] **Módulo B (INBOT) — Proveedores y Compras:** NIT único, compra cabecera + detalle, incremento atómico de existencias dentro de transacción DB.
- [x] **Módulo C (INBOT) — Ventas y Facturación:** Transacción con `lockForUpdate()`, verificación de existencias, cálculo estricto en backend, reversión total con 422 si falta stock, reportes diarios y mensuales y exportación CSV.
- [x] **Módulo D (INBOT) — Asistente Móvil y Chatbot:** Búsqueda por código de barras, nombre, reconocimiento visual por Average Hash (aHash de 64 bits con PHP GD nativo y distancia de Hamming <= 10) y chatbot inteligente en tiempo real.
- [x] **Módulo F (INBOT) — Base de Datos Híbrida:** MariaDB XAMPP local (`inbot_system`), PostgreSQL en la nube con `DB_URL`/`DATABASE_URL` y SQLite en memoria para tests.

---

## Módulo Actual
**CIERRE, AUDITORÍA TÉCNICA Y DOCUMENTACIÓN FINAL**

---

## Decisiones Técnicas Vigentes
1. **Precio de venta inmutable desde el frontend:** El precio de venta se calcula de forma obligatoria en el backend mediante `PricingService` aplicando la fórmula `precio_compra * (1 + ganancia / 100)` con redondeo comercial configurable (por defecto múltiplo de 50).
2. **Concurrencia en ventas:** Se implementó bloqueo pesimista de filas (`lockForUpdate()`) en cada producto dentro de `DB::transaction()` para garantizar que dos ventas simultáneas no generen stock negativo ni inconsistencias.
3. **Reconocimiento visual de imágenes:** Se implementó el algoritmo Average Hash (aHash) de 64 bits en PHP GD nativo sin depender de bibliotecas externas ni Python. El hash se precalcula e indexa en la base de datos al subir el producto, logrando consultas en milisegundos mediante distancia de Hamming.
4. **Seguridad y secretos:** Contraseñas SMTP cifradas bidireccionalmente con `Crypt::encryptString`; contraseñas de usuario hasheadas con `bcrypt`; códigos OTP protegidos con hash unidireccional.
5. **Cero exposición de datos sensibles en Auditoría:** `AuditService` filtra automáticamente claves sensibles (`password`, `token`, `otp`, `secret`, `api_key`).

---

## Rutas Importantes de la API
- `POST /api/register` — Registro de usuarios
- `POST /api/login` — Autenticación con token Sanctum (Limitado a 5 intentos/min)
- `GET /api/profile` — Información del usuario autenticado
- `POST /api/logout` — Revocación del token activo
- `GET /api/stock_bajo` — Lista de productos con stock crítico (stock_actual <= stock_minimo)
- `GET|POST /api/productos` — CRUD de productos (Admin/Bodeguero; Vendedor recibe 403 al crear)
- `GET|POST /api/compras` — Registro y consulta de compras a proveedores (suma stock)
- `GET|POST /api/ventas` — Facturación de ventas (descuenta stock con lockForUpdate, 422 si stock insuficiente)
- `GET /api/reportes/ventas-diarias` — Agregaciones de ventas por día
- `GET /api/reportes/ventas-mensuales` — Agregaciones de ventas por mes
- `GET /api/reportes/inventario` — Valoración económica y resumen de existencias
- `GET /api/reportes/exportar-ventas-csv` — Descarga de archivo CSV con historial de facturas
- `GET /api/aplicacion_movil/producto/codigo/{codigo}` — Búsqueda por código de barras
- `GET /api/aplicacion_movil/producto/nombre` — Búsqueda predictiva por nombre
- `POST /api/aplicacion_movil/producto/imagen` — Reconocimiento visual aHash
- `POST /api/aplicacion_movil/chatbot` — Asistente conversacional de existencias
- `POST /contacto` y `POST /api/contacto` — Formulario de contacto con doble correo (admin y usuario)

---

## Última Prueba Exitosa
- **Fecha:** 2026-10-01 18:48:11
- **Comando:** `php artisan test`
- **Resultado:** 23 pruebas pasadas, 0 fallidas (89 aserciones en 1.27 segundos).
