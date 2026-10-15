name: event-reservations-qr description: Implementar catálogo de platos, reservas seguras, disponibilidad, códigos QR y verificación de pagos y entregas.

Event Reservations QR — Reservas y alimentación
1. Objetivo

Implementar un sistema fiable para gestionar las comidas que ofrece la iglesia con fines de recaudación.

2. Catálogo

Cada plato debe permitir:

Nombre.
Descripción.
Imagen.
Precio.
Disponibilidad.
Fecha y horario.
Cantidad disponible.
Estado de publicación.
Información relevante de ingredientes o alérgenos cuando esté disponible.

Todos los campos variables deben poder administrarse desde el panel.

3. Flujo de reserva
El visitante selecciona un plato.
Indica la cantidad.
Selecciona el horario disponible.
Introduce los datos mínimos de contacto.
El servidor valida la información.
El servidor verifica la disponibilidad.
Se calcula el importe.
Se registra la reserva.
Se genera un código único.
Se presenta la confirmación y el QR.
4. Integridad
Usar transacciones.
Verificar disponibilidad dentro de la transacción.
Bloquear las filas pertinentes cuando sea necesario.
Evitar sobreventa.
Validar cantidades positivas.
Calcular importes en el servidor.
Guardar el precio aplicado en cada elemento reservado.
Definir reglas de cancelación.
Gestionar solicitudes duplicadas cuando corresponda.
Definir cuánto tiempo se retiene una reserva pendiente.
5. Estados

Modelar por separado los estados de reserva, pago y entrega cuando sea necesario.

Ejemplos de estados de reserva:

Pendiente.
Confirmada.
Cancelada.

Ejemplos de estados de pago:

Pendiente.
Verificado.
Rechazado.
Reembolsado, únicamente si el proceso lo contempla.

Ejemplos de entrega:

Pendiente.
Entregada.

Las transiciones deben estar definidas y validadas en el servidor.

No permitir que una reserva cancelada sea entregada ni que una entrega se registre dos veces sin una operación autorizada.

6. Código QR
Generar un token aleatorio y suficientemente impredecible.
No codificar datos personales sensibles.
No confiar en el contenido del QR como prueba de pago.
Resolver el token en el servidor.
Comprobar la reserva actual.
Exigir autorización para cambiar su estado.
Evitar la enumeración de códigos.
Registrar confirmaciones y entregas.

El QR puede identificar la reserva; el servidor determina si puede pagarse, confirmarse o entregarse.

7. Comprobantes
Permitir archivos únicamente si el flujo los necesita.
Validar formato y tamaño.
Almacenar los archivos de forma privada.
Exigir autorización para consultarlos.
No marcar pagos automáticamente al subir una imagen.
Registrar quién verifica el pago.
8. Interfaz

Incluir:

Catálogo claro.
Precio visible.
Cantidad disponible.
Formulario simple.
Confirmación con código.
QR descargable.
Búsqueda administrativa.
Vista de pedidos pendientes.
Validación de entrega.
9. Pruebas

Comprobar:

Cantidad insuficiente.
Dos solicitudes concurrentes para las últimas unidades.
Precio modificado después de una reserva.
Código QR inválido.
Reserva cancelada.
Entrega repetida.
Usuario sin permisos.
Comprobante no autorizado.
10. Criterios de aceptación

No debe existir sobreventa en las operaciones cubiertas por la lógica transaccional. Las reservas y sus importes deben conservar integridad, y los cambios de estado deben ser verificables.