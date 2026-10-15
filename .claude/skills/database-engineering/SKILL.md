name: database-engineering description: Diseñar, normalizar y auditar una base de datos MySQL con integridad referencial, índices adecuados y transacciones seguras.

Database Engineering — MySQL y normalización
1. Objetivo

Diseñar una base de datos fiable y eficiente para gestionar la convención, su contenido, las reservas, las donaciones y los visitantes.

2. Análisis previo

Antes de crear migraciones:

Identificar las entidades del dominio.
Identificar atributos y dependencias funcionales.
Determinar las relaciones.
Definir reglas de integridad.
Analizar las consultas esperadas.
Revisar las tablas existentes.

No crear una segunda estructura que duplique funcionalidades ya disponibles.

3. Normalización

Aplicar primera, segunda y tercera forma normal cuando corresponda.

Evitar:

Duplicación innecesaria de datos.
Columnas con listas separadas por comas.
Repetición de grupos de atributos.
Relaciones representadas como texto.
Claves foráneas inexistentes.
Tablas genéricas que mezclen entidades diferentes.

La desnormalización solo debe aplicarse cuando exista una justificación de rendimiento o de conservación histórica.

4. Entidades candidatas

Evaluar la necesidad de:

Users.
Roles y permisos.
EventSettings.
Venues.
ProgramDays.
ProgramItems.
Dishes.
DishAvailabilities.
Reservations.
ReservationItems.
DonationMethods.
DonationRecords.
Accommodations.
TouristPlaces.
Faqs.
ContactMessages.
MediaAssets.
AuditLogs.

No es obligatorio crear todas las tablas. Cada tabla debe responder a una necesidad real.

5. Relaciones
Utilizar claves foráneas.
Definir restricciones únicas donde corresponda.
Elegir correctamente las reglas de borrado.
Evitar eliminaciones en cascada que destruyan registros financieros o históricos.
Utilizar tablas intermedias para relaciones muchos a muchos.
Evitar campos polimórficos cuando una relación explícita resulte más segura y sencilla.
6. Importes y cantidades
Utilizar DECIMAL para importes monetarios.
No utilizar FLOAT o DOUBLE para cálculos de dinero.
Validar cantidades positivas.
Definir precisión monetaria apropiada.
Calcular totales en el servidor.
Guardar el precio aplicado a cada elemento de una reserva.
Definir las reglas de redondeo.
Conservar la trazabilidad de cambios de precio.
7. Reservas concurrentes

Para controlar la disponibilidad:

Usar transacciones.
Bloquear las filas relevantes cuando sea necesario.
Comprobar existencias dentro de la operación transaccional.
Evitar comprobar disponibilidad únicamente antes de guardar.
Diseñar mecanismos de idempotencia cuando sean útiles.
Añadir índices a los campos de búsqueda.
Definir la política para reservas pendientes y vencidas.
8. Índices

Evaluar índices para:

Códigos de reserva.
Fechas de disponibilidad.
Estados de pedidos.
Relaciones mediante claves foráneas.
Búsquedas administrativas frecuentes.

Evitar índices redundantes y comprobar las consultas antes de optimizar.

9. Migraciones
Mantener migraciones versionadas.
Usar nombres descriptivos.
No editar migraciones ya aplicadas sin analizar las consecuencias.
Preferir nuevas migraciones para cambios posteriores.
No borrar tablas o columnas de producción sin respaldo y revisión.
Preparar seeders reproducibles.
No introducir información ficticia como contenido oficial.
10. Auditoría de datos

Revisar:

Registros huérfanos.
Duplicados.
Restricciones faltantes.
Relaciones incorrectas.
Estados incompatibles.
Importes incoherentes.
Datos personales innecesarios.
11. Entregables

Documentar:

Diagrama de entidades y relaciones.
Justificación de la normalización.
Claves primarias y foráneas.
Restricciones e índices.
Estrategia transaccional.
Migraciones y pruebas relevantes.

No ejecutar migraciones destructivas sin autorización.