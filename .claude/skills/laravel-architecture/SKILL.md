name: laravel-architecture description: Diseñar e implementar una arquitectura mantenible con Laravel 12, PHP, Blade, Livewire, Tailwind CSS y Alpine.js.

Laravel Architecture — Arquitectura y desarrollo
1. Rol

Actúa como arquitecto de software y desarrollador senior especializado en Laravel.

Construye una aplicación modular, mantenible, segura y eficiente para la Convención MMM Huaraz.

2. Stack tecnológico
Laravel 12.
PHP 8.3 o versión compatible.
MySQL.
Blade.
Livewire 3.
Tailwind CSS.
Alpine.js.
Vite.
Eloquent ORM.
PHPUnit o Pest.

Antes de instalar o actualizar dependencias, comprueba las versiones reales del proyecto.

No fuerces una actualización de Tailwind, Livewire o Laravel sin evaluar su impacto.

3. Principios arquitectónicos
Preferir un monolito Laravel bien organizado.
Usar Blade para la estructura y presentación.
Usar Livewire para interacciones que requieren comunicación con el servidor.
Usar Alpine.js para interacciones pequeñas del navegador.
Mantener las reglas de negocio fuera de las vistas.
Usar Eloquent para operaciones relacionales.
Utilizar transacciones para operaciones críticas.
Evitar duplicación de código.
Mantener componentes pequeños y enfocados.
No crear capas de abstracción innecesarias.
No construir una SPA completa si Blade y Livewire son suficientes.
4. Organización del código

Distribuir las responsabilidades de forma coherente:

app/Models: entidades y relaciones.
app/Livewire: componentes interactivos.
app/Http/Controllers: controladores HTTP cuando sean apropiados.
app/Http/Requests: validaciones reutilizables.
app/Policies: autorización.
app/Services: lógica de negocio que justifique una clase independiente.
app/Enums: estados definidos cuando aporten claridad.
database/migrations: estructura versionada.
database/seeders: datos iniciales.
resources/views: plantillas Blade.
routes/web.php: rutas web.
tests: pruebas.

Adapta esta organización a la estructura existente; no crees clases por obligación.

5. Livewire
Validar todas las entradas.
No confiar en propiedades públicas como fuente de autorización.
Volver a consultar y autorizar registros sensibles en el servidor.
Evitar componentes excesivamente grandes.
Aplicar paginación en listados extensos.
Evitar consultas innecesarias durante cada actualización.
Proteger operaciones de pago y cambios de estado.
Mostrar mensajes claros de validación y resultado.
6. Configuración y dependencias
Usar variables de entorno para secretos y configuraciones específicas.
Mantener composer.lock y el archivo de bloqueo de npm.
No añadir paquetes sin una necesidad real.
Verificar mantenimiento, licencia y compatibilidad de cada dependencia nueva.
No modificar archivos de dependencias manualmente para ocultar incompatibilidades.
7. Calidad del código
Aplicar convenciones de Laravel y PSR-12.
Utilizar nombres descriptivos.
Evitar métodos demasiado extensos.
Evitar controladores gigantes.
Eliminar código muerto cuando sea seguro.
Documentar decisiones arquitectónicas relevantes.
No introducir patrones de diseño innecesarios.
8. Desarrollo por fases

Implementar en entregas pequeñas:

Inspección del proyecto.
Sistema visual y páginas públicas.
Administración.
Modelo de datos.
Reservas y QR.
Donaciones.
Hospedaje y turismo.
Seguridad y auditoría.
Pruebas y despliegue.

Si ya existe una funcionalidad, auditarla y reutilizarla antes de crear otra.

9. Criterios de aceptación

Cada funcionalidad debe incluir validación, autorización cuando corresponda, estados de interfaz, tratamiento de errores y pruebas pertinentes.

Nunca afirmar que un comando o prueba se ejecutó si no se ejecutó realmente.