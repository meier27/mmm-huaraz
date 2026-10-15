name: deployment-operations description: Preparar despliegues seguros de Laravel, configurar producción, realizar copias de respaldo y verificar el funcionamiento de la plataforma.

Deployment Operations — Producción
1. Objetivo

Publicar y mantener la plataforma de la Convención MMM Huaraz con una configuración estable y segura.

2. Verificaciones previas
Versión de PHP compatible con Laravel.
Extensiones requeridas.
Versión y configuración de MySQL.
Dependencias bloqueadas.
Variables de entorno.
Configuración del correo.
Recursos compilados.
Migraciones revisadas.
Pruebas críticas ejecutadas.
3. Configuración segura
Desactivar APP_DEBUG en producción.
Configurar APP_KEY.
Usar HTTPS.
Mantener secretos fuera de Git.
Limitar permisos de archivos.
Configurar correctamente el directorio público.
No publicar comprobantes privados.
Configurar sesiones y cookies de forma segura.
Evitar credenciales por defecto.
Revisar CORS si realmente se necesita.
4. Base de datos
Realizar un respaldo antes de cambios importantes.
Verificar el respaldo.
Probar la restauración cuando sea posible.
Revisar migraciones.
Evitar operaciones destructivas sin autorización.
No utilizar datos reales de producción en pruebas.
Documentar los procedimientos de recuperación.
5. Despliegue

Preparar instrucciones reproducibles para:

Instalar dependencias.
Configurar variables de entorno.
Compilar recursos.
Ejecutar migraciones revisadas.
Aplicar permisos.
Configurar cachés de producción.
Verificar el correo.
Comprobar las rutas.
Probar las reservas.
Confirmar el funcionamiento de los mapas.
Revisar los logs.

Adaptar los comandos al hosting real. No asumir que todos los proveedores permiten acceso SSH o ejecutar procesos en segundo plano.

6. Monitorización
Revisar logs de errores.
Detectar errores HTTP.
Verificar el funcionamiento de formularios.
Revisar el estado de la base de datos.
Comprobar el envío de correos.
Controlar el espacio disponible.
Mantener copias de respaldo.

No registrar información sensible innecesaria.

7. Recuperación

Documentar:

Qué respaldar.
Dónde guardar los respaldos.
Cómo restaurarlos.
Cómo verificar la integridad de los datos.
Cómo volver a una versión estable.
Qué hacer si las reservas dejan de funcionar.
8. Criterios de aceptación

La aplicación debe desplegarse con configuración segura, contar con un procedimiento de recuperación y superar pruebas de funcionamiento después de su publicación.

No afirmar que el despliegue ha terminado si no se ha ejecutado y verificado.