name: audit-testing description: Auditar el código Laravel, verificar reglas de negocio y ejecutar pruebas automatizadas de seguridad, reservas y administración.

Audit Testing — Auditoría y calidad
1. Objetivo

Garantizar que la plataforma funciona correctamente y que sus operaciones críticas son verificables.

2. Tipos de pruebas

Utilizar PHPUnit o Pest según las dependencias existentes.

Implementar pruebas unitarias, de integración y funcionales según corresponda.

3. Pruebas obligatorias
Acceso y permisos
Visitantes sin autenticación.
Usuarios autenticados.
Administradores.
Roles con permisos limitados.
Acceso directo a registros protegidos.
Reservas
Creación válida.
Datos inválidos.
Cantidades no permitidas.
Disponibilidad insuficiente.
Solicitudes duplicadas.
Cancelaciones.
Cálculos de importes.
Solicitudes concurrentes.
Transiciones de estado.
QR y pagos
Token válido.
Token inválido.
Reserva cancelada.
Reserva ya entregada.
Acceso sin permisos.
Confirmación de pago.
Verificación de comprobantes.
Reutilización de solicitudes.
Administración
Creación y edición de contenido.
Validación de formularios.
Gestión de imágenes.
Cambios de permisos.
Registro de auditoría.
Acceso a archivos privados.
Interfaz y navegación
Rutas públicas.
Formularios.
Mensajes de validación.
Páginas no encontradas.
Enlaces de mapas.
4. Auditoría de código

Buscar:

Código duplicado.
Métodos excesivamente extensos.
Consultas N+1.
Autorización incompleta.
Validación insuficiente.
Estados contradictorios.
Manejo incorrecto de errores.
Dependencias innecesarias.
Datos sensibles en logs.
5. Clasificación de hallazgos
Crítico: permite comprometer el sistema o sus datos.
Alto: compromete una operación o información importante.
Medio: genera un riesgo relevante bajo determinadas condiciones.
Bajo: problema menor o de mantenimiento.

Justificar cada clasificación con evidencia.

6. Reglas
Ejecutar las pruebas realmente.
No inventar resultados.
No modificar datos de producción para probar.
Usar una base de datos de pruebas aislada.
No borrar datos ni ejecutar migraciones destructivas sin autorización.
Añadir pruebas de regresión después de corregir errores.
Distinguir fallos reales de riesgos hipotéticos.
7. Informe final

Documentar:

Hallazgos.
Gravedad.
Archivos afectados.
Correcciones.
Pruebas ejecutadas.
Resultados.
Problemas pendientes.

No declarar que la aplicación es completamente segura por haber superado un conjunto limitado de pruebas.