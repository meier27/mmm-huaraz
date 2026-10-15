name: application-security description: Proteger la aplicación Laravel contra accesos indebidos, vulnerabilidades web, fraude en reservas y exposición de datos.

Application Security — Seguridad web
1. Objetivo

Proteger a visitantes, administradores, reservas y registros de aportes.

Aplicar el principio de mínimo privilegio en toda la aplicación.

2. Autenticación y autorización
Utilizar mecanismos compatibles con Laravel.
Proteger las rutas administrativas.
Aplicar Policies, Gates y middleware.
Verificar permisos en el servidor.
Prevenir accesos directos a registros ajenos.
Restringir cambios de roles.
No confiar en botones ocultos como mecanismo de seguridad.
Proteger las acciones de Livewire.
3. Validación
Validar todos los datos en el servidor.
Validar tipos, longitudes, formatos y rangos.
Utilizar consultas parametrizadas y Eloquent.
Escapar el contenido mostrado.
Evitar asignación masiva insegura.
Validar identificadores y estados.
No confiar en precios ni cantidades recibidos del cliente.
4. Protección web

Revisar:

CSRF.
XSS.
Inyección SQL.
Acceso horizontal indebido.
Escalada de privilegios.
Enumeración de identificadores.
Abuso de formularios.
Rate limiting.
Configuración de sesiones.
Configuración de cookies.
Manejo de errores.
Exposición de información interna.
5. Archivos y comprobantes
Validar tamaño y tipo real de archivo.
No confiar únicamente en la extensión.
Limitar formatos aceptados.
Almacenar documentos privados fuera del directorio público.
Aplicar autorización antes de descargar.
Generar nombres de archivo seguros.
Evitar ejecutar archivos subidos.
No mostrar comprobantes públicamente.
6. Reservas y QR
Calcular los importes en el servidor.
Verificar la disponibilidad dentro de transacciones.
Usar tokens QR impredecibles.
No incluir datos personales sensibles en el QR.
Verificar el estado de la reserva en cada operación.
Exigir permisos para confirmar pagos o entregas.
Prevenir la reutilización indebida de acciones.
Registrar cambios sensibles.
7. Donaciones
Utilizar exclusivamente métodos oficiales proporcionados por la organización.
No inventar cuentas ni códigos QR financieros.
No considerar una imagen como confirmación automática de pago.
Restringir el acceso a comprobantes.
Registrar quién verifica los aportes.
Separar el estado declarado del estado verificado.
8. Secretos y producción
Guardar secretos en variables de entorno.
No subir archivos .env a Git.
Desactivar APP_DEBUG en producción.
Usar HTTPS.
Configurar permisos mínimos.
Evitar exponer trazas y errores internos.
Revisar las dependencias.
No registrar contraseñas, tokens ni credenciales.
9. Método de auditoría

Para cada vulnerabilidad:

Identificar el recurso afectado.
Describir el riesgo.
Reproducir el problema cuando sea seguro.
Corregirlo.
Añadir una prueba de regresión.
Documentar el resultado.

Priorizar vulnerabilidades críticas y altas antes del despliegue.

10. Criterios de aceptación

Ninguna operación sensible debe depender exclusivamente de controles del navegador.

La autorización, validación y verificación de estados deben realizarse en el servidor.