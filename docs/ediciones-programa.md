# Renovación de programas por edición

## Preparación

Antes de publicar este código, aplicar `migrations/20261001_program_editions.up.sql` en la misma base de datos que usa la aplicación. Requiere la migración de participantes y vigencias `20260923_recurring_evaluations_and_rosters.up.sql`. Desplegar la migración y el código juntos: los listados nuevos consultan las columnas de edición.

La migración inicial reúne las actividades existentes de cada centro en una edición, conservando sus IDs, inscripciones, vigencias, asistencias y evaluaciones. No divide automáticamente el histórico por año natural, porque un curso puede abarcar dos años. Si un centro ya contiene varios cursos mezclados, ese histórico permanece agrupado. Las nuevas renovaciones quedan separadas desde esta funcionalidad. Las fechas antiguas desconocidas se conservan sin inventar fechas.

## Uso habitual

1. Abrir el centro y pulsar **Cargar programa**. También se puede acceder desde la subida en lote del escritorio.
2. Seleccionar el Excel `.xlsx` o CSV actualizado del curso. El centro ya está elegido cuando se accede desde su ficha.
3. Pulsar **Revisar listado**. Se muestra la edición detectada, su período, las instalaciones reutilizadas/nuevas, las clases y las inscripciones que continúan, se añaden, se recuperan o se retiran.
4. Pulsar **Guardar edición** para aplicar los cambios en una transacción.

El nombre y el período son automáticos: mínimo inicio y máximo fin de las actividades incluidas. Si alguna actividad no tiene fin, la edición tampoco tiene un fin definido. Los cambios manuales de fechas y las altas/borrados de actividades recalculan el período.

Se reutilizan las instalaciones por nombre normalizado dentro del centro. Una instalación que no participa en el nuevo curso permanece en el histórico y deja de aparecer al consultar la nueva edición. No se desactiva globalmente por faltar en un curso. Una instalación reutilizada que estaba inactiva se reactiva; la revisión lo indica.

Las actividades se identifican por instalación, nombre, grupo, tipo de control, fechas, días y horas. Una nueva edición recibe nuevas actividades e inscripciones. Dentro de una misma edición se reutilizan las coincidencias completas y se conservan sus IDs. Un cambio de horario o período crea una nueva clase y retira la anterior si se carga el listado completo; su histórico permanece disponible en informes. Las personas se comparan por nombre y apellidos dentro de cada actividad; esta función no crea una identidad personal compartida entre años.

## Listado completo y actualizaciones parciales

Por defecto se considera que el archivo es el **listado completo del curso**, tal como indica la pantalla. Al actualizar una edición existente:

- Las personas que siguen conservan su inscripción y sus datos anteriores.
- Las nuevas se inscriben; las dadas de baja se reincorporan con su mismo ID.
- Las personas que faltan se dan de baja cerrando su vigencia, sin borrar resultados ni asistencias.
- Las actividades ausentes se retiran de las vistas habituales de esa edición, sin borrarse.
- Las otras ediciones no se modifican.

La revisión detalla los totales y permite desplegar hasta ocho nombres de actividades y ocho personas que dejarán de aparecer. **Opciones de importación** permite desmarcar «listado completo» cuando el archivo solo contiene altas; en ese caso nadie ni ninguna clase se retira.

El modo habitual admite un listado mixto: las clases de aforo se marcan con `Tipo = A` o `aforo`, y no necesitan nombre/apellidos. El modo global **Clases de aforo** actualiza exclusivamente las clases de aforo de la edición; mantiene las clases con participantes aunque no figuren en ese archivo.

## Detección y navegación

Se busca primero una coincidencia de clases con las mismas fechas. Si no la hay, se comparan los períodos de las ediciones del centro. Un período que no coincide crea una nueva edición. Una edición antigua sin fin no absorbe indefinidamente las renovaciones posteriores. Si coinciden varias ediciones, la revisión exige elegir una. «Cambiar la edición detectada» permite corregir la propuesta o crear deliberadamente una nueva edición.

Las fichas de centro/instalación, la navegación de monitores y los informes solo muestran el selector **Edición** cuando el centro tiene más de una. Se consulta por defecto la edición vigente; si no hay una vigente, la próxima, y después la última. Preparar el próximo curso no oculta el actual mientras siga vigente. El escritorio calcula sus contadores con la edición predeterminada de cada centro. Los informes incluyen las clases retiradas de la edición elegida para permitir consultar sus datos anteriores.

## Archivos y validación

La hoja requiere encabezados Instalación, Actividad, Fecha inicio y Días; también reconoce Nombre, Apellidos, Grupo, Fecha fin, Hora inicio, Hora fin y Tipo. Se aceptan variantes habituales, columnas en otro orden, fechas/horas numéricas de Excel y hojas auxiliares. Si hay varios listados en el libro, se pide elegir la hoja. El centro indicado dentro del archivo se ignora y se utiliza el elegido en pantalla.

Se admiten los encabezados de la plantilla de Gijón, incluidas sus aclaraciones entre paréntesis. Esa plantilla repite «Hora Fin actividad» sobre una columna de fecha y otra de hora: el lector distingue la fecha fin por el formato de calendario de sus datos. Si no puede distinguirlas, solicita corregir los encabezados en vez de adivinar.

Límites: 5 MB por archivo, 20.000 filas y 50 columnas por hoja. Las fórmulas deben sustituirse por sus valores antes de importar. El lector no ejecuta fórmulas ni interpreta texto del libro como instrucciones. El pegado manual conserva los formatos anteriores de la subida en lote y queda dentro de una sección plegada.

Un listado vacío, una fecha/hora inválida, un fin anterior al inicio, un día desconocido o participantes duplicados en una clase impiden guardar todo el listado. No se aplica una importación parcial con las filas válidas. Las coincidencias ambiguas existentes en instalaciones/clases/personas también deben resolverse antes de guardar.

La revisión no escribe en la base de datos. Para guardar es obligatoria una huella del listado y del estado consultado. Si cambian los datos después de revisar, el servidor devuelve 409 y solicita una revisión nueva. La importación bloquea el centro, las clases y las inscripciones durante la transacción.

En una clase nueva, el listado anual empieza en su fecha de inicio. Las altas y reincorporaciones de una clase existente se hacen efectivas desde la carga, o desde el inicio de la clase si todavía no ha comenzado. Las bajas no crean intervalos invertidos en clases futuras.

## Comprobaciones

- `php tests/program_editions_test.php`: renovación, reimportación, listado completo/parcial, aforo, altas/bajas/reincorporaciones, conservación de datos, filtros, períodos y revisión obsoleta.
- `php tests/program_spreadsheet_test.php`: Excel/CSV, columnas reordenadas, fechas/horas, selección de hojas y fórmulas.
- `php tests/bulk_import_aforo_test.php`: horarios independientes, normalización, listados mixtos y aforo sin participantes.

La migración y los endpoints se comprobaron además con MariaDB aislado y datos sintéticos, sin modificar la base de datos habitual ni importar el Excel de referencia. Del archivo de referencia solo se revisaron sus encabezados y el formato de sus primeras celdas de fechas/horas.
