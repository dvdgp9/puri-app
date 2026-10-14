# Importación del PDF municipal de Sabadell

## Despliegue

Aplicar `migrations/20261013_sabadell_import.up.sql` junto con este código y las dependencias de `composer.lock` (`composer install --no-dev`). Requiere las migraciones de vigencias y ediciones del programa. El repositorio incluye `vendor`, igual que las dependencias existentes. No se ha aplicado la migración a la base habitual.

En **Administradores**, crear o editar la cuenta y marcar **Importar listados de Sabadell**. La cuenta mantiene su rol `admin` y solo puede utilizar sus centros asignados. Los superadmins pueden comprobar el flujo. El permiso se consulta en la base de datos al leer, revisar y guardar; no se guarda en la sesión de login. Quitar el permiso o la asignación al centro bloquea una carga pendiente.

## Uso

1. Crear el centro y asignarlo a la administradora.
2. Desde la ficha del centro, abrir **Cargar datos → Listado del Ayuntamiento de Sabadell**.
3. Seleccionar el curso de destino o crear una renovación explícita. Para la primera carga, introducir las fechas del curso.
4. Subir el PDF con texto del Ayuntamiento. No se admiten el XLS maquetado ni PDF escaneados. Límites: 5 MB, 100 páginas, 20.000 filas y una página por grupo.
5. Abrir cada grupo, comprobar los nombres y excluir expresamente las filas que no deben cargarse. Los dos primeros términos se proponen como apellidos para nombres nuevos; esa separación no es una identificación fiable. Cuando el nombre completo coincide inequívocamente dentro de la clase, se reutiliza la separación existente.
6. Confirmar la revisión de cada grupo y pulsar **Revisar cambios**. Comprobar altas, bajas, reincorporaciones, clases nuevas/sustituidas y exclusiones. Pulsar **Guardar listado**.

Cambiar el centro elimina el borrador visible. Cambiar el curso o elegir renovación vuelve a preparar los grupos desde el PDF y reinicia las correcciones/confirmaciones; elegir el destino antes de revisar. Cambiar cualquier dato después de una revisión invalida esa revisión.

## Actualización mensual

Se actualiza el listado completo de participantes de cada grupo incluido, usando sus fechas y datos existentes. Las personas que faltan en esos grupos se desactivan desde la fecha de carga (o el inicio si es futuro), sin borrar asistencias ni evaluaciones. Los grupos que no aparecen en el PDF se mantienen.

Los códigos de grupo se comparan dentro del curso seleccionado. Una diferencia de instalación, nombre, días o horario, o varias coincidencias, exige elegir la clase y resolverla. **Actualizar participantes y conservar la clase** restaura los datos existentes. **Sustituir la clase** crea otra con las fechas revisadas y retira la anterior, conservando su historial. El inicio propuesto para una sustitución es hoy o el inicio del curso si es futuro. Las fechas pueden revisarse antes de guardar. La revisión muestra las bajas y sustituciones resultantes.

Reimportar el mismo listado en el mismo curso no crea actividades ni participantes. Una renovación crea otra edición y no modifica las anteriores. No hay identidad personal común entre cursos, igual que en las importaciones generales de Puri.

## Procesamiento

El servidor usa `smalot/pdfparser`, instalado con Composer, y las posiciones del texto para reconocer el formato municipal. Se valida la cabecera, la numeración consecutiva y que no quede texto sin interpretar en la columna de participantes. Las páginas incompletas o los códigos repetidos bloquean todo el documento; no se importan solo las páginas válidas.

No se ejecutan instrucciones, scripts ni fórmulas del documento. No se incorporan teléfonos, nacimientos, profesores ni las casillas del papel. La fecha impresa se muestra como información, no define el período del curso.

El PDF se lee desde el archivo temporal de la subida; no se archiva. El borrador estructurado contiene los nombres y los datos necesarios, está vinculado a la cuenta/centro y caduca a la hora. Se elimina al completar la carga o reemplazar la subida; el archivo PDF nunca forma parte de la sesión. No se registran nombres ni contenido del documento en los logs de estos endpoints.

Los endpoints municipales requieren el permiso actual, acceso al centro activo y el token de la página. Guardar exige una revisión previa y su huella del listado y del estado de la base. Durante el guardado se bloquean el administrador, sus asignaciones, el centro y los registros del programa. Un cambio de datos, permiso, centro o borrador invalida el guardado. El guardado es transaccional.

El alcance interno `groups` de `programPlan` sincroniza participantes dentro de las clases incluidas sin retirar clases ausentes. El endpoint general conserva sus modos `append` y `complete`; el nuevo alcance se utiliza desde el importador municipal.

## Verificación

- `php tests/sabadell_import_test.php`: lector por posiciones, páginas incompletas, permisos/revocación, exclusiones, revisión, actualización parcial, sustituciones, renovaciones, historial y borradores.
- `php tests/program_editions_test.php`, `php tests/program_spreadsheet_test.php`, `php tests/bulk_import_aforo_test.php`, `php tests/recurring_roster_test.php`: regresiones del importador general.
- El PDF de referencia se utiliza solo para comprobar la extracción local, sin copiarlo al repositorio ni importar sus participantes en la base habitual. Los casos de pruebas persistidos son ficticios.
- El recorrido de subida/revisión/guardado se ha comprobado también en un servidor PHP y MariaDB aislados: 33 comprobaciones HTTP de los endpoints reales, incluyendo revocación del permiso/asignación, sesión de superadmin obsoleta, revisión modificada, cambios concurrentes y rechazo de archivos inválidos. La pantalla se comprobó con datos ficticios en escritorio y móvil.
