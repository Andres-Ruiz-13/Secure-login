# INFORME TÉCNICO DE AUDITORÍA Y PRUEBAS DE SEGURIDAD
## Aplicación Web y API REST: INBOT System & Secure CMS (Laravel 12 + MySQL)

---

### 1. Portada
- **Proyecto:** INBOT System & CMS Modular Seguro
- **Campo de Aprendizaje:** Seguridad en Aplicaciones (CADI - MEDIT)
- **Tecnologías:** Laravel 12.68.0, PHP 8.2.12, MySQL/MariaDB 10.4.32, Sanctum, Postman, PHPUnit
- **Fecha:** Octubre 2026
- **Entorno:** Local XAMPP (Windows) / Nube Híbrida (Render + PostgreSQL)

---

### 2. Introducción
El aseguramiento de software requiere verificar la efectividad real de los controles de seguridad en capas: red, transporte, aplicación, base de datos y lógica de negocio. Este informe documenta la auditoría técnica integral, análisis de vulnerabilidades, evaluación de dependencias y pruebas funcionales positivas y negativas aplicadas sobre la plataforma **INBOT System & Secure CMS**, implementada en Laravel 12.

---

### 3. Objetivos
- **Objetivo General:** Evaluar mediante pruebas técnicas y funcionales los controles de seguridad de la aplicación Laravel, registrando evidencias, códigos de respuesta HTTP, riesgos y mitigaciones.
- **Objetivos Específicos:**
  1. Auditar dependencias PHP y JavaScript mediante `composer audit` y `npm audit`.
  2. Verificar rutas, middleware de seguridad, rate limiting y autorización por roles (RBAC).
  3. Ejecutar pruebas de penetración y casos de prueba negativos para autenticación, autorización (401/403), validación (422) y saturación (429).
  4. Auditar el ciclo de vida seguro de archivos multimedia (validación MIME real, tamaño máx 5MB, almacenamiento no ejecutable y reemplazo seguro).
  5. Validar la integridad transaccional del módulo financiero de inventarios, compras y ventas con bloqueo a nivel de fila (`lockForUpdate`).

---

### 4. Descripción de la Aplicación
**INBOT System** es una plataforma integral para la gestión automatizada de inventarios, compras a proveedores, control de stock crítico, facturación de ventas y asistente móvil con reconocimiento de productos. Incluye un CMS modular con gestión multimedia, noticias, videos, equipo, testimonios, formulario de contacto con notificación por correo SMTP dinámico cifrado y sistema de auditoría continua.

---

### 5. Metodología
Se aplicó una metodología estructurada basada en los estándares **OWASP Top 10** y el marco de desarrollo seguro:
1. **Comprender:** Mapeo de superficies de ataque y flujo de datos.
2. **Explorar:** Ejecución de herramientas de análisis estático y dinámico (`artisan`, `composer audit`, `npm audit`, `postman`).
3. **Modelar:** Definición de la matriz general de pruebas (T01 a T25) con resultados esperados.
4. **Implementar y Probar:** Ejecución controlada de pruebas funcionales y de seguridad.
5. **Mitigar:** Aplicación de parches de código, middleware `SecurityHeaders`, políticas de autorización (`Policies`) y bloqueo pesimista en base de datos.

---

### 6. Herramientas y Comandos Utilizados
```bash
php artisan about
php -v
composer --version
composer audit
npm audit
php artisan route:list
php artisan migrate:status
php artisan test
php artisan storage:link
php artisan optimize:clear
```

---

### 7. Auditoría de Dependencias
- **`npm audit`:**
  - Resultado: **0 vulnerabilidades detectadas**. Ecosistema frontend limpio.
- **`composer audit`:**
  - Se identificaron 3 avisos en paquetes de desarrollo/framework:
    1. `laravel/framework` (Severidad: *Baja*, PKSA-d5tc-s1qs-h781): Posible XSS en página de debug de Laravel.
       - *Mitigación:* Se aseguró `APP_DEBUG=false` en el entorno de producción y se forzó manejo uniforme de excepciones JSON que oculta trazas internas.
    2. `league/commonmark` (Severidad: *Media* / *Alta*): Bypass de etiquetas HTML y DoS en tablas complejas.
       - *Mitigación:* Todo el contenido enriquecido se escapa mediante sintaxis nativa Blade `{{ }}` y sanitización en servidor antes de renderizado.

---

### 8. Auditoría de Rutas y Middleware
- Se auditaron **43 rutas registradas**:
  - **Públicas:** Home (`/`), Login (`/login`), Registro (`/api/register`), Contacto (`/contacto`, `/api/contacto`), Asistente Móvil (`/api/aplicacion_movil/*`).
  - **Protegidas por Rate Limiting:** `/login` y `/api/login` están bajo el limitador `throttle:login` (máximo 5 intentos/minuto por email + IP).
  - **Protegidas con Sanctum (`auth:sanctum`):** `/api/profile`, `/api/stock_bajo`, `/api/productos`, `/api/compras`, `/api/ventas`, `/api/reportes/*`.
  - **Protegidas con Middleware de Rol (`role:admin,bodeguero`):** Creación, modificación y eliminación de productos y compras.
- **Cabeceras de Seguridad:** El middleware `SecurityHeaders` inyecta automáticamente en todas las respuestas:
  - `X-Content-Type-Options: nosniff` (previene MIME sniffing).
  - `X-Frame-Options: SAMEORIGIN` (mitiga Clickjacking).
  - `Referrer-Policy: strict-origin-when-cross-origin`.
  - `Permissions-Policy: geolocation=(), camera=(), microphone=()`.
  - `Content-Security-Policy: default-src 'self' ...`.

---

### 9. Revisión de Configuración (.env)
- Se verificó que el archivo `.env` se encuentre listado en `.gitignore` para evitar filtraciones en el repositorio.
- El archivo `.env.example` contiene la plantilla completa sin contraseñas ni claves privadas (`APP_KEY=` vacía, credenciales SMTP genéricas).
- Se configuró la conexión a MariaDB XAMPP en `inbot_system` y se habilitó soporte híbrido para PostgreSQL mediante `DB_URL` y `DATABASE_URL`.

---

### 10. Pruebas de Autenticación y Autorización
- **Prueba positiva:** Login con credenciales válidas retorna token Bearer de Sanctum y código **200 OK**.
- **Prueba negativa (clave incorrecta o usuario inexistente):** Ambas devuelven el mensaje genérico *"Las credenciales proporcionadas son incorrectas"* con código **401 Unauthorized**, imposibilitando la enumeración de usuarios.
- **Acceso sin token:** Consultar `/api/productos` o `/api/profile` sin cabecera Authorization responde **401 Unauthorized**.
- **Prueba de rol restrictivo (Vendedor):** Cuando un usuario con rol `vendedor` envía `POST /api/productos`, la API responde **403 Forbidden** con el mensaje *"Acceso denegado: permisos insuficientes para esta operación."*

---

### 11. Pruebas de Validación y CSRF
- **Formularios Web:** Todas las solicitudes `POST`, `PUT` y `DELETE` en rutas web requieren la directiva `@csrf`. Las solicitudes con token ausente o alterado son rechazadas con **419 Page Expired**.
- **API Form Requests:**
  - `StoreProductRequest` valida unicidad de código de barras, números no negativos y tipos de datos.
  - Entradas inválidas responden **422 Unprocessable Entity** con detalle exacto del campo afectado.

---

### 12. Auditoría de Carga de Imágenes
- **Formatos permitidos:** JPG, JPEG, PNG, WEBP.
- **Rechazo de extensiones peligrosas:** Archivos `.php`, `.exe`, `.js` o `.html` son rechazados en validación con **422**.
- **Límite de tamaño:** Archivos superiores a 5 MB son rechazados inmediatamente en backend.
- **Almacenamiento seguro:** No se confía en el nombre original del archivo; se renombra mediante identificador criptográfico (`Str::random(32)`) y se almacena en `storage/app/public/`.
- **Eliminación y reemplazo físico:** Al actualizar o eliminar un registro multimedia o producto, el archivo anterior es borrado del disco mediante `Storage::disk('public')->delete()`, impidiendo almacenamiento huérfano.

---

### 13. Auditoría de API con Postman
Se estructuró la colección `postman/INBOT_SYSTEM_API.postman_collection.json` con 8 grupos de prueba cubriendo todos los endpoints y códigos HTTP esperados (200, 201, 401, 403, 422, 429).

---

### 14. Matriz General de Pruebas

| ID | Módulo / Prueba | Entrada / Condición | Resultado Esperado | Resultado Obtenido | HTTP | Estado |
| :---: | :--- | :--- | :--- | :--- | :---: | :---: |
| **T01** | Registro de Usuario | Datos válidos y confirmación | Usuario registrado, sin exponer hash | Usuario creado, token seguro | 201 | ✅ Aprobado |
| **T02** | Registro con Email Inválido | Formato de correo incorrecto | Validación rechazada | Mensaje de error en campo email | 422 | ✅ Aprobado |
| **T03** | Registro sin Campos | Payload vacío `{}` | Validación de obligatorios | Lista de campos requeridos | 422 | ✅ Aprobado |
| **T04** | Registro Contraseña Débil | Contraseña menor a 8 chars | Rechazo por complejidad | Exige longitud y caracteres | 422 | ✅ Aprobado |
| **T05** | Registro Correo Duplicado | Email ya existente | Rechazo por unicidad | Correo ya registrado | 422 | ✅ Aprobado |
| **T06** | Login Administrador | Credenciales válidas admin | Token Sanctum emitido | Token Bearer recibido | 200 | ✅ Aprobado |
| **T07** | Login Clave Inválida | Password erróneo | Mensaje genérico | Credenciales incorrectas | 401 | ✅ Aprobado |
| **T08** | Login Usuario Inexistente | Email no registrado | Mensaje genérico anti-enumeración | Mismo mensaje que T07 | 401 | ✅ Aprobado |
| **T09** | Perfil sin Token | Petición sin Header Auth | Bloqueo por middleware | No autenticado | 401 | ✅ Aprobado |
| **T10** | Perfil Token Inválido | Token malformado | Bloqueo por Sanctum | No autenticado | 401 | ✅ Aprobado |
| **T11** | Perfil Token Válido | Token activo en Header | Datos del perfil de usuario | ID, nombre y correo | 200 | ✅ Aprobado |
| **T12** | Logout de Sesión | Token activo | Token eliminado de BD | Sesión cerrada correctamente | 200 | ✅ Aprobado |
| **T13** | Perfil tras Logout | Reutilización de token revocado | Token ya no es válido | Acceso rechazado | 401 | ✅ Aprobado |
| **T14** | Rate Limiting Login | 6 intentos fallidos en 1 minuto | Bloqueo por throttle | Demasiadas solicitudes | 429 | ✅ Aprobado |
| **T15** | Cálculo Automático Precio | Compra: $20.000, Ganancia: 35% | Backend calcula $27.000 (redondeo 50) | $27.000,00 calculado en BD | 201 | ✅ Aprobado |
| **T16** | Permiso RBAC Vendedor | Vendedor intenta POST /productos | Bloqueo por Policy/Middleware | Permisos insuficientes | 403 | ✅ Aprobado |
| **T17** | Permiso RBAC Bodeguero | Bodeguero crea producto | Creación permitida | Producto creado | 201 | ✅ Aprobado |
| **T18** | Alerta de Stock Bajo | Consulta `/api/stock_bajo` | Solo productos con stock <= mín | Filtro exacto devuelto | 200 | ✅ Aprobado |
| **T19** | Compra Suma Stock | Compra de 10 unidades | Incremento atómico en BD | Stock sube de 10 a 20 | 201 | ✅ Aprobado |
| **T20** | Venta con Stock Suficiente | Venta de 2 unidades (stock 10) | Deducción atómica con lock | Stock baja a 8, total calculado | 201 | ✅ Aprobado |
| **T21** | Venta Stock Insuficiente | Venta de 50 uds (stock 8) | Rollback total y mensaje limpio | Error INSUFFICIENT_STOCK | 422 | ✅ Aprobado |
| **T22** | Búsqueda Móvil por Código | Código de barras existente | Producto localizado | Datos del producto | 200 | ✅ Aprobado |
| **T23** | Chatbot Asistente Stock | Pregunta de existencias | Respuesta conversacional con stock | Consulta real de existencias | 200 | ✅ Aprobado |
| **T24** | Subida Imagen No Válida | Archivo .php o script malicioso | Rechazo por MIME/extensión | Error de validación en file | 422 | ✅ Aprobado |
| **T25** | Formulario de Contacto | Envío de solicitud web | Notificación a admin + copia usuario | Correo procesado | 200 | ✅ Aprobado |

---

### 15. Matriz de Hallazgos y Mitigaciones

| ID | Hallazgo Detectado | Evidencia | Riesgo | Mitigación Aplicada |
| :---: | :--- | :--- | :--- | :--- |
| **H01** | Potencial condición de carrera en deducción simultánea de inventario | Dos ventas concurrentes del mismo producto podían vender stock inexistente | Crítico (Pérdidas económicas y stock negativo) | Implementación de bloqueo pesimista `lockForUpdate()` dentro de transacciones de base de datos atómicas. |
| **H02** | Dependencia del precio de venta desde el cliente | Riesgo de manipulación de precios si el frontend envía el subtotal | Alto (Fraude en facturación) | Cálculo obligatorio e inmutable del precio de venta y subtotales en el backend mediante `PricingService`. |
| **H03** | Falta de control granular para el rol Vendedor en API | Usuarios autenticados podían invocar endpoints de creación de catálogo | Alto (Modificación no autorizada de inventario) | Creación del middleware `role` y `ProductoPolicy` que retorna `403 Forbidden` a usuarios no autorizados. |
| **H04** | Falta de cabeceras de protección HTTP | Respuestas HTTP carecían de políticas contra Clickjacking y MIME sniffing | Medio (Ataques de framed content y XSS) | Registro del middleware global `SecurityHeaders` con CSP, nosniff, Referrer-Policy y HSTS. |
| **H05** | Credenciales de servidor SMTP almacenadas en texto plano | Configuración en base de datos vulnerable a exposición directa | Alto (Compromiso de cuenta de correo) | Cifrado obligatorio de credenciales SMTP utilizando `Crypt::encryptString` y sanitización estricta en logs. |

---

### 16. Resultados
- **Total de pruebas automatizadas ejecutadas:** 23
- **Pruebas aprobadas:** 23 (100% de éxito, 0 fallos).
- **Cobertura:** Autenticación Sanctum, Roles RBAC (401/403), Transacciones atómicas de compra y venta, Alertas de stock bajo, Algoritmo aHash y Chatbot.
- **Rendimiento:** Ejecución completa del test suite en 1.27 segundos.

---

### 17. Recomendaciones
1. **Ambiente de Producción:** Configurar `APP_ENV=production`, `APP_DEBUG=false` y ejecutar `php artisan optimize` para compilar rutas, configuraciones y vistas.
2. **Rotación de Tokens:** Establecer un tiempo de vida (TTL) para tokens Sanctum en `config/sanctum.php` (ej. 1440 minutos / 24 horas).
3. **Monitoreo de Logs:** Monitorear periódicamente la tabla `audit_logs` para identificar patrones anómalos o intentos de fuerza bruta recurrentes.

---

### 18. Preguntas de Reflexión (Sección Técnica Guiada)

1. **¿Por qué no debemos guardar directamente la imagen dentro de la base de datos?**  
   Almacenar archivos binarios grandes (BLOBs) en la base de datos degrada drásticamente el rendimiento de lectura/escritura, satura la memoria del motor SQL, incrementa el tamaño de los backups y bloquea el uso eficiente de cachés HTTP y CDNs.
2. **¿Qué función cumple `php artisan storage:link`?**  
   Crea un enlace simbólico entre `storage/app/public` y `public/storage`, permitiendo que los archivos cargados sean accesibles públicamente vía web sin exponer la raíz interna del servidor.
3. **¿Qué diferencia existe entre `storage/app/public` y `public/storage`?**  
   `storage/app/public` es el directorio real en el sistema de archivos donde se alojan los archivos de forma persistente. `public/storage` es el enlace simbólico expuesto al servidor web.
4. **¿Por qué debemos validar un archivo en el servidor?**  
   Las validaciones en el navegador (como `accept="image/*"`) son fácilmente eludibles con herramientas como Postman o cURL. La validación en el servidor con chequeo de MIME real y extensiones es la única que garantiza que el archivo no sea un script malicioso.
5. **¿Qué riesgo existe al permitir cualquier extensión?**  
   Un atacante podría subir archivos `.php`, `.phtml` o ejecutables que, al ser consultados mediante la URL pública, se ejecutarían en el servidor web otorgando control total (Remote Code Execution / RCE).
6. **¿Por qué guardamos la ruta del archivo?**  
   Guardar la ruta relativa (ej. `productos/xyz.jpg`) mantiene la base de datos liviana y desacoplada del sistema de almacenamiento físico (local, S3, etc.).
7. **¿Por qué las credenciales SMTP deben estar en `.env` o cifradas?**  
   Evita la filtración de credenciales en el control de versiones (Git/GitHub) y previene accesos no autorizados a servicios corporativos.
8. **¿Qué ocurre si `.env` se publica en GitHub?**  
   Bots automatizados escanean repositorios públicos en segundos, comprometiendo bases de datos, llaves de cifrado (`APP_KEY`) y cuentas de correo.
9. **¿Qué función cumple un Mailable?**  
   Encapsula la lógica de configuración, destinatarios, asunto y vista Blade del correo en una clase reutilizable y testeable.
10. **¿Cómo se relaciona esta actividad con el ciclo de vida de desarrollo seguro?**  
    Integra la seguridad desde la etapa de diseño (*Security by Design*): validación estricta de entradas, principio de mínimo privilegio, control de concurrencia y auditoría continua en lugar de intentar añadir parches tardíamente.
