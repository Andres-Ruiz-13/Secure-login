# ESTADO DEL PROYECTO — INBOT SYSTEM & CMS MODULAR

## Entorno
- **Laravel:** 12.68.0
- **PHP:** 8.2.12 (cli) ZTS Visual C++ 2019 x64
- **Python:** 3.14.7 + `python-docx 1.2.0`
- **Base de datos:** MySQL / MariaDB 10.4.32 (XAMPP - puerto 3306, base `inbot_system`, utf8mb4) / SQLite en memoria para tests
- **Frontend:** Blade + Bootstrap 5.3 + Tailwind CSS + Bootstrap Icons + Vite
- **Autenticación:** Laravel Sanctum (Tokens Bearer) + Sesiones Web seguras

---

## Modelos de Datos Implementados (7 Modelos Principales)

1. **`Categoria` (`Category`):**
   - Agrupa productos (ej. ASEO, FERRETERÍA).
   - Relación: Una categoría tiene muchos productos (`hasMany(Producto::class)`).
   - Atributos: `id`, `name`/`nombre`, `slug`, `description`/`descripcion`, `is_active`, timestamps.
2. **`Proveedor`:**
   - Empresa o distribuidor al que se le compra (tiene NIT único).
   - Relaciones: Un proveedor tiene muchas facturas de compra (`hasMany(Compra::class)` / `facturasCompra()`) y muchos productos.
   - Atributos: `id`, `nit`, `nombre`, `telefono`, `direccion`, `correo`, `is_active`, timestamps.
3. **`Producto`:**
   - Código de barras único, nombre, descripción, categoría, precio de compra, precio de venta (calculado automáticamente en backend), stock actual y stock mínimo.
   - Relaciones: `belongsTo(Categoria::class)`, `belongsTo(Proveedor::class)`, `hasMany(DetalleCompra::class)`, `hasMany(DetalleVenta::class)`.
   - Atributos: `id`, `codigo_barras`, `nombre`, `descripcion`, `categoria_id`, `proveedor_id`, `precio_compra`, `porcentaje_ganancia`, `precio_venta`, `stock_actual`, `stock_minimo`, `imagen`, `image_hash`, `is_active`, timestamps.
4. **`FacturaCompra` (`Compra`):**
   - Cabecera de compra a proveedor (número de factura único, proveedor, fecha, observaciones, total, usuario).
   - Relaciones: `belongsTo(Proveedor::class)`, `belongsTo(User::class)`, `hasMany(DetalleFacturaCompra::class)`.
5. **`DetalleFacturaCompra` (`DetalleCompra`):**
   - Cada línea de la factura de compra (producto, cantidad, precio unitario, subtotal).
   - Relaciones: `belongsTo(FacturaCompra::class)`, `belongsTo(Producto::class)`.
6. **`Venta`:**
   - Cabecera de venta a cliente (número de factura, método de pago, fecha, total, cliente, usuario/vendedor).
   - Relaciones: `belongsTo(User::class)`, `hasMany(DetalleVenta::class)`.
7. **`DetalleVenta`:**
   - Cada línea de una factura de venta (producto, cantidad, precio unitario, subtotal).
   - Relaciones: `belongsTo(Venta::class)`, `belongsTo(Producto::class)`.

---

## Matriz de Roles y Permisos (100% Funcional y Verificada)

| Acción | Administrador | Bodeguero | Vendedor |
| :--- | :---: | :---: | :---: |
| Iniciar y cerrar sesión (Web & API) | **Sí** | **Sí** | **Sí** |
| Consultar productos, categorías, proveedores (lectura) | **Sí** | **Sí** | **Sí** |
| Crear o editar productos, categorías y proveedores | **Sí** | **Sí** | **No** (403) |
| Registrar facturas de compra (multi-fila / producto nuevo) | **Sí** | **Sí** | **No** (403) |
| Registrar ventas (descuento atómico de stock) | **Sí** | **No** (403) | **Sí** |
| Consulta de ventas del día | **Sí** | **No** | **Sí** |
| Reportes de inventario y compras | **Sí** | **Sí** | **No** |
| Panel `/admin/` y gestión de usuarios (creación y asignación de rol) | **Sí** | **No** (403) | **No** (403) |

---

## Funcionalidades y Flujos Clave

### 1. Precios Automáticos
- Fórmula inmutable calculada en backend:
  $$\text{precio\_venta} = \text{precio\_compra} \times \left(1 + \frac{\text{porcentaje\_ganancia}}{100}\right)$$
- Redondeo comercial configurable en `config/inbot.php` (por defecto múltiplo comercial de 50 o 2 decimales).

### 2. Facturas de Compra en 1 Solo Paso (Flujo 9.1 & `/facturas-compra-lista/`)
- Registro unificado de cabecera: número de factura, proveedor, fecha y margen de ganancia global (por defecto 30%).
- Múltiples filas dinámicas donde se puede alternar entre:
  * **Producto existente:** Seleccionable del catálogo, actualiza último precio de compra y suma unidades al stock.
  * **Producto nuevo:** Código de barras (opcional o autogenerado), nombre, descripción opcional, categoría, cantidad y costo unitario. Al guardar, el sistema crea el producto en la base de datos, calcula su precio de venta, fija stock inicial y emite la factura con su detalle.

### 3. Facturación de Ventas en Tiempo Real (Flujo 9.2 & `/ventas-lista/`)
- Módulo de caja con búsqueda inmediata por código de barras (lector USB/cámara) o nombre de producto.
- Bloqueo pesimista de filas (`lockForUpdate()`) en base de datos.
- Validación obligatoria de existencias: si no hay suficiente stock disponible, rechaza la transacción con HTTP 422 (`INSUFFICIENT_STOCK`) y no altera el inventario.
- Deducción atómica e instantánea de existencias.
- Consulta de ventas del día en tiempo real.

### 4. Control de Stock y Alertas (Flujo 9.3)
- Consulta `/api/stock_bajo` y alias `/api/core-api/productos/stock_bajo/` para listar productos donde `stock_actual <= stock_minimo`.

### 5. Reportes Oficiales en Word (.docx) y CSV
- Script independiente `generate_report.py` con motor `python-docx` y servicio Laravel `ReportWordService`:
  * **Reporte de Inventario Completo:** Valoración al costo, valoración comercial, resumen de existencias y tabla detallada con alertas.
  * **Reporte de Ventas Diarias:** Facturación, ticket promedio y desglose de comprobantes.
  * **Reporte de Compras por Mes y Año:** Total invertido, proveedores abastecedores y desglose de facturas.
- Descargable desde la interfaz CMS (`/reportes/word?tipo=...`) y API (`/api/reportes/word?tipo=...`).
- Exportación contable en CSV (`/api/reportes/exportar-ventas-csv`).

### 6. Modificación de Detalles de Producto y Atribución de Imágenes
- **Edición Completa desde el CMS:** Botón "Editar" en la tabla de inventario que abre un modal dinámico para modificar: nombre, código de barras, descripción, categoría, proveedor, precio de compra, porcentaje de ganancia (con recálculo visual en vivo del precio de venta), stock actual, stock mínimo y estado activo.
- **Gestión de Imagen del Producto:**
  * Subida de nueva imagen con previsualización y cálculo de perceptual hash (aHash).
  * Selección y reutilización de imágenes existentes en la Biblioteca Multimedia.
  * Opción para remover/desvincular la imagen actual.
- **Atribución Directa desde Multimedia:**
  * Al subir una imagen a la Biblioteca Multimedia (`/admin/media`), permite atribuirla directamente a un producto específico del inventario.
  * Tabla multimedia muestra qué producto tiene asignada cada imagen con selector rápido de vinculación/desvinculación en tiempo real (`POST /admin/media/{id}/atribuir`).

### 7. Trazabilidad y Logs de Depuración
- Registro de eventos en `logs/debug.log` (`App\Services\DebugLogService`):
  * Compras registradas
  * Ventas facturadas
  * Productos actualizados y medios atribuidos
  * Categorías y proveedores creados
  * Usuarios administrados

---

## Rutas Importantes del Sistema

### Web (CMS y Vistas Dedicadas)
- `GET /` — Catálogo público y portal
- `GET /login` y `POST /login` — Autenticación web
- `GET /dashboard` — Panel CMS principal con métricas, tabs de inventario, compras, ventas, proveedores, categorías, usuarios y reportes
- `PUT /cms/productos/{id}` — Actualización de detalles de producto e imagen
- `POST /admin/media` — Subida de archivos multimedia con opción de atribuir a producto
- `POST /admin/media/{id}/atribuir` — Vinculación directa de medios existentes a productos
- `GET /facturas-compra-lista/` — Flujo 9.1: Abastecimiento y creación rápida multi-fila
- `GET /ventas-lista/` — Flujo 9.2: Módulo de caja, búsqueda por lector de código de barras y ventas del día
- `GET /reportes/word` — Generación de reportes ejecutivos Word (.docx)

### API RESTful (Tokens Sanctum)
- `POST /api/login` & `POST /api/logout`
- `GET|POST|PUT|DELETE /api/productos`
- `GET /api/stock_bajo` & `GET /api/core-api/productos/stock_bajo/`
- `GET|POST|PUT|DELETE /api/categorias`
- `GET|POST|PUT|DELETE /api/proveedores`
- `GET|POST /api/compras` (y alias `api/facturas-compra`)
- `GET|POST /api/ventas` & `GET /api/ventas/hoy`
- `GET /api/reportes/ventas-diarias`
- `GET /api/reportes/ventas-mensuales`
- `GET /api/reportes/inventario`
- `GET /api/reportes/compras`
- `GET /api/reportes/word`
- `GET|POST|PUT|DELETE /api/admin/users` (Gestión de usuarios Admin)

---

## Estado de Pruebas Automatizadas
- **Comando:** `php artisan test`
- **Resultado:** **36 pruebas pasadas al 100%, 0 fallidas (164 aserciones).**
