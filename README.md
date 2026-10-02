# INBOT SYSTEM & SECURE CMS (LARAVEL 12)

Plataforma empresarial para la **gestión automatizada de inventarios, compras a proveedores, facturación de ventas en tiempo real, asistente móvil con reconocimiento visual por aHash y CMS modular seguro**.

Implementado bajo arquitectura **Laravel 12 + PHP 8.2+ + MySQL/MariaDB (XAMPP)**, con soporte para bases de datos híbridas (PostgreSQL en la nube y SQLite para pruebas) y cumplimiento de los estándares de seguridad **OWASP Top 10**.

---

## 🚀 Requisitos del Sistema
- **PHP:** 8.2 o superior (con extensiones `pdo_mysql`, `pdo_sqlite`, `gd`, `bcmath`, `mbstring`, `openssl`, `fileinfo`).
- **Composer:** 2.x o superior.
- **Node.js & NPM:** Node v18+ y NPM v9+.
- **Servidor de Base de Datos Local:** MySQL o MariaDB (incluido en XAMPP).
- **Servidor Web:** Apache (XAMPP) o servidor integrado `php artisan serve`.

---

## 🛠️ Instalación y Puesta en Marcha con XAMPP

### 1. Clonar el repositorio y situarse en la carpeta
```bash
cd c:\xampp\htdocs\secure-login
```

### 2. Instalar dependencias de PHP y Node
```bash
composer install
npm install
```

### 3. Configurar variables de entorno
Copia la plantilla `.env.example` si no tienes un archivo `.env`:
```bash
copy .env.example .env
```
Verifica que las credenciales de base de datos apunten a MariaDB de XAMPP:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inbot_system
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generar clave de aplicación y enlazar almacenamiento
```bash
php artisan key:generate
php artisan storage:link
```

### 5. Crear la base de datos y ejecutar migraciones con Seeders
Asegúrate de que el servicio MySQL de XAMPP esté iniciado y ejecuta:
```bash
php artisan migrate:fresh --seed
```

### 6. Iniciar los servidores de desarrollo
- **Backend Laravel:**
  ```bash
  php artisan serve
  ```
  Acceso en: `http://127.0.0.1:8000` (o `http://localhost/secure-login/public`).
- **Frontend Vite (opcional para desarrollo de vistas):**
  ```bash
  npm run dev
  ```

---

## 👥 Usuarios y Credenciales de Prueba

Todos los usuarios tienen la contraseña predeterminada: **`Segura123!`**

| Rol | Correo Electrónico | Alcance de Permisos |
| :--- | :--- | :--- |
| **Administrador** | `admin@inbot.com` | Control total del sistema, compras, inventario, reportes, auditoría y CMS. |
| **Bodeguero** | `bodeguero@inbot.com` | Gestión de compras a proveedores, altas/modificaciones de productos y stock. |
| **Vendedor** | `vendedor@inbot.com` | Consulta de existencias y registro de ventas. **Recibe 403 Forbidden al intentar crear/modificar productos**. |

---

## 📋 Documentación de Endpoints Principales

### 1. Autenticación (Sanctum)
- **POST `/api/register`**: Registro de usuarios con contraseña segura.
- **POST `/api/login`**: Inicia sesión y retorna Bearer Token. Limitado estrictamente a **5 intentos por minuto** (`throttle:login`, responde 429 tras exceso).
- **GET `/api/profile`**: Datos del usuario autenticado (`auth:sanctum`).
- **POST `/api/logout`**: Revoca el token actual.

### 2. Inventario y Productos (Módulo A)
- **GET `/api/productos`**: Listado paginado con búsqueda (`?search=`) y filtro de categoría (`?categoria_id=`).
- **GET `/api/stock_bajo`**: Lista únicamente los productos donde `stock_actual <= stock_minimo`.
- **POST `/api/productos`** *(Admin / Bodeguero)*: Crea un producto. **El backend calcula automáticamente el precio de venta** aplicando `precio_compra * (1 + ganancia / 100)` y redondeo comercial configurable en `config/inbot.php`. Precalcula el Average Hash (aHash) de la imagen si se suministra.

### 3. Compras a Proveedores (Módulo B)
- **POST `/api/compras`** *(Admin / Bodeguero)*:
  Registra compras con múltiples productos dentro de una transacción de base de datos atómica.
  **Incrementa automáticamente el stock disponible** de cada producto bloqueando la fila.

### 4. Ventas y Facturación (Módulo C)
- **POST `/api/ventas`** *(Admin / Vendedor / Bodeguero)*:
  Factura una venta con múltiples productos dentro de una transacción atómica con `lockForUpdate()`.
  - Verifica existencias en tiempo real.
  - Si falta stock en algún ítem, **aborta la transacción con rollback completo y responde 422 Unprocessable Entity**.
  - Si hay stock, **descuenta existencias** y calcula los subtotales/total en el servidor. Imposible generar stock negativo o condiciones de carrera.

### 5. Reportes Financieros y de Inventario (Módulo C)
- **GET `/api/reportes/ventas-diarias`**: Agrupación de ventas diarias con `SUM`, `COUNT` y ticket promedio.
- **GET `/api/reportes/ventas-mensuales`**: Agrupación mensual de facturación.
- **GET `/api/reportes/inventario`**: Valoración total del inventario a precio de costo y precio de venta comercial.
- **GET `/api/reportes/exportar-ventas-csv`**: Descarga directa de archivo CSV formateado con el historial de facturación.

### 6. Asistente Móvil y Chatbot con aHash (Módulo D)
- **GET `/api/aplicacion_movil/producto/codigo/{codigo}`**: Consulta rápida por código de barras.
- **GET `/api/aplicacion_movil/producto/nombre?q={termino}`**: Búsqueda por coincidencia de nombre.
- **POST `/api/aplicacion_movil/producto/imagen`**: Reconocimiento de producto mediante Average Hash (aHash de 64 bits con PHP GD nativo) comparando la distancia de Hamming contra los hashes indexados en BD.
- **POST `/api/aplicacion_movil/chatbot`**: Chatbot inteligente para clientes o dependientes que consulta existencias, precios y stock crítico en tiempo real.

### 7. Formulario de Contacto y Correos (PDF 1 - Reto C)
- **POST `/contacto`** (Web) o **POST `/api/contacto`** (API):
  Procesa solicitudes de contacto, almacena el mensaje en base de datos, envía notificación por correo con plantilla Blade profesional `#022766` a la administración y envía correo de confirmación al usuario.

---

## 🧪 Ejecución de Pruebas Automatizadas

El proyecto cuenta con una suite completa de pruebas unitarias y de integración que validan lógica financiera, concurrencia, permisos RBAC, manejo de errores y ciclo de vida de archivos.

Ejecutar todas las pruebas con:
```bash
php artisan test
```

Resultado verificado:
```text
  Tests:    23 passed (89 assertions)
  Duration: 1.27s
```

---

## ☁️ Guía de Despliegue en la Nube (Render con PostgreSQL)

El proyecto está diseñado para funcionar en arquitectura híbrida sin modificar código.

1. **Crear base de datos PostgreSQL en Render / Supabase / Neon:**
   Obtén la URL de conexión completa:
   `postgresql://usuario:password@host:port/database`
2. **Configurar Variables de Entorno en el Dashboard de Render:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:xxngi9Uxvm0+XofCQaCDFV3LKHFvZyVFxaBOm5ixva4=
   APP_URL=https://tu-servicio.onrender.com
   DB_CONNECTION=pgsql
   DB_URL=postgresql://usuario:password@host:port/database
   FILESYSTEM_DISK=public
   ```
3. **Comando de Build en Render:**
   ```bash
   composer install --no-dev --optimize-autoloader && php artisan storage:link && php artisan migrate --force && php artisan optimize
   ```
4. **Comando de Inicio (Start Command):**
   ```bash
   apache2-foreground
   # o para php built-in:
   php artisan serve --host=0.0.0.0 --port=$PORT
   ```

---

## 🔒 Checklist de Seguridad y Rendimiento

- [x] Autenticación mediante tokens Laravel Sanctum con revocación en logout.
- [x] Rate limiting en inicio de sesión (máx. 5 intentos/minuto) con respuesta 429.
- [x] Protección contra Mass Assignment (`$fillable` explícito en todos los modelos).
- [x] Transacciones con `lockForUpdate()` para prevenir condiciones de carrera y stock negativo.
- [x] Validación estricta de subida de imágenes (tipo MIME real, renombrado aleatorio con `Str::random(32)`, tamaño máx 5MB).
- [x] Middleware `SecurityHeaders` con CSP, nosniff, Referrer-Policy y Permissions-Policy.
- [x] Contraseñas protegidas con `bcrypt` y credenciales SMTP cifradas con `Laravel Crypt`.
- [x] Prevención de consultas N+1 con `preventLazyLoading()`.
- [x] Base de datos desacoplada y compatible con MariaDB, PostgreSQL y SQLite.
