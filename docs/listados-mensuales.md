# Actualización mensual de participantes

Aplicar `migrations/20260923_recurring_evaluations_and_rosters.up.sql` antes de usar la nueva importación. La migración marca a todos los inscritos existentes como activos y crea una vigencia abierta para conservar sus asistencias anteriores.

En Admin, el CSV conserva las columnas `Nombre` y `Apellidos`. El modo **Actualizar listado mensual** compara cada fila con los registros de esa actividad. Primero muestra una vista previa de los recuentos y luego aplica la actualización en una transacción:

- Coincidencia con una persona activa: conserva su ID y su historial.
- Nombre nuevo: crea un inscrito activo.
- Coincidencia con una persona inactiva: reactiva su mismo ID.
- Persona activa ausente del CSV: cierra su vigencia y la desactiva; no borra asistencias ni evaluaciones.

La clave de comparación normaliza mayúsculas y espacios, pero no elimina tildes. Se limita a la actividad; dos clases pueden tener personas con el mismo nombre sin compartir historial. Un CSV vacío, una fila incompleta o nombres duplicados bloquean la operación. Si ya hay nombres duplicados en la actividad, Admin puede activar «Mostrar desactivados» y corregir los datos antes de sincronizar. Si dos registros representan realmente a la misma persona y ambos tienen historial, se necesita una revisión manual de esos datos.

Las vigencias usan `inicio` inclusivo y `fin` exclusivo. El paso de lista consulta las personas vigentes en la fecha elegida. Los informes siguen incluyendo a una persona desactivada si estuvo vigente o tiene asistencias en el período consultado. Las opciones de baja manual en Admin también desactivan sin borrar datos.
