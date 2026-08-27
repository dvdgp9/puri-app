# API de evaluaciones

Estado: contrato e implementación del MVP completados en M2–M4. La aplicación de migraciones y la prueba funcional con datos siguen pendientes.

## Objetivo y alcance

Admin planifica una evaluación para una actividad dentro de un período inclusivo. El monitor del centro puede realizarla una sola vez, en una fecha elegida dentro de ese período y nunca futura. La primera interfaz crea un campo, pero el contrato devuelve una colección de campos para admitir más variables posteriormente.

Una planificación y su realización son objetos distintos:

- `evaluacion`: define actividad, nombre, instrucciones, período y campos.
- `sesion`: registra la fecha real y el estado de la realización.
- `resultado`: guarda el valor o el estado `sin_evaluar`, una referencia estable (`participante_ref`) y una instantánea del nombre. Si después se elimina la inscripción, el historial se conserva y `inscrito_id` pasa a `null`.

## Convenciones

- Codificación: UTF-8.
- Entrada: JSON, salvo los parámetros de endpoints `GET`.
- Salida: JSON con `Content-Type: application/json`.
- Fechas: `YYYY-MM-DD`.
- Períodos: `fecha_inicio` y `fecha_fin` son inclusivos.
- Los IDs son enteros positivos.
- Una respuesta correcta usa `{ "success": true, "data": ... }`.
- Una respuesta de error usa `{ "success": false, "error": { "code": "CODIGO", "message": "Mensaje seguro", "fields": {} } }`.
- Los errores internos se registran con contexto técnico, pero la respuesta nunca expone SQL, rutas locales ni credenciales.

## Autorización

### Admin

- Todos los endpoints `/admin/api/evaluaciones/*` requieren la sesión de Admin existente.
- Un `superadmin` puede operar sobre cualquier centro.
- Un `admin` solo puede operar si el centro de la actividad aparece en `admin_asignaciones` para su cuenta.
- La actividad, la evaluación, la sesión y el participante se resuelven siempre en servidor. Nunca se acepta un `centro_id` del cliente como prueba de autorización.
- Un recurso de otro centro devuelve `403 CENTRO_NO_ASIGNADO`.

### Monitor

- Todos los endpoints `/api/evaluaciones/*` requieren `$_SESSION['centro_id']`.
- La actividad de la evaluación debe pertenecer al mismo centro de la sesión.
- Un participante debe pertenecer a la misma actividad que la evaluación.
- Una sesión finalizada es de solo lectura para el monitor y cualquier escritura devuelve `409 SESION_YA_FINALIZADA`.

## Estados derivados

La tabla `evaluaciones` no almacena un estado redundante. El servidor lo deriva usando el período, el archivado y la sesión:

- `programada`: hoy es anterior a `fecha_inicio`.
- `pendiente`: hoy está dentro del período y no existe sesión.
- `en_curso`: existe una sesión con estado `en_curso`.
- `finalizada`: existe una sesión finalizada.
- `fuera_de_plazo`: terminó el período y no se inició la evaluación.
- `archivada`: `archivada_at` tiene valor; no aparece al monitor.

Una sesión en curso no desaparece al terminar el período. Se devuelve como `en_curso_fuera_de_plazo`, pero el monitor no puede seguir modificándola hasta que Admin amplíe el período. Reabrir una finalizada fuera del período también requiere ampliar el período para habilitar edición del monitor.

## Formato del campo

`tipo_dato` admite:

- `entero`: valor numérico sin decimales. Se admite cero y valores negativos porque algunas mediciones de flexibilidad pueden necesitarlos.
- `decimal`: valor numérico con un máximo de tres decimales.
- `duracion`: segundos, con un máximo de tres decimales; debe ser mayor o igual que cero. La interfaz puede presentarlo como minutos y segundos.
- `texto_corto`: texto normalizado de hasta 255 caracteres.

`unidad` es visible para el monitor, por ejemplo `repeticiones`, `vueltas`, `cm` o `segundos`.

Para números acotados se admite `calificador`: `exacto`, `mayor_que` o `menor_que`. Por ejemplo, `>30 s` se guarda como `valor_numero: 30`, `calificador: "mayor_que"`.

Un resultado `sin_evaluar` lleva ambos valores y el calificador a `null`; se diferencia así de un cero medido.

## Endpoints Admin

### GET /admin/api/evaluaciones/list_by_activity.php

Lista las evaluaciones de una actividad, incluidas las finalizadas y archivadas si se solicita.

Parámetros:

- `actividad_id` obligatorio.
- `include_archived=1` opcional, solo para Admin.

Respuesta `200`:

```json
{
  "success": true,
  "data": {
    "actividad": { "id": 31, "nombre": "HIIT", "centro_id": 4 },
    "evaluaciones": [
      {
        "id": 8,
        "nombre": "Burpees en 1 minuto",
        "fecha_inicio": "2026-10-01",
        "fecha_fin": "2026-10-31",
        "estado": "pendiente",
        "campos": [
          { "id": 11, "nombre": "Burpees", "tipo_dato": "entero", "unidad": "repeticiones", "orden": 1 }
        ],
        "sesion": null,
        "cobertura": { "medidos": 0, "sin_evaluar": 0, "total_participantes": 22 }
      }
    ]
  }
}
```

Errores: `400 ID_INVALIDO`, `403 CENTRO_NO_ASIGNADO`, `404 ACTIVIDAD_NO_ENCONTRADA`.

### GET /admin/api/evaluaciones/detail.php

Devuelve una evaluación por `evaluacion_id` con sus campos, sesión, cobertura y contexto de actividad, instalación y centro. Está pensado para precargar la edición y la futura vista Admin de resultados sin confiar en el contexto enviado por la interfaz.

Respuesta `200`: `{ "success": true, "data": { "evaluacion": {}, "contexto": {} } }`.

Errores: `400 ID_INVALIDO`, `403 CENTRO_NO_ASIGNADO`, `404 EVALUACION_NO_ENCONTRADA`.

### POST /admin/api/evaluaciones/create.php

Crea una planificación y su único campo inicial en una transacción.

Entrada:

```json
{
  "actividad_id": 31,
  "nombre": "Burpees en 1 minuto",
  "instrucciones": "Realizar el máximo número con técnica segura.",
  "fecha_inicio": "2026-10-01",
  "fecha_fin": "2026-10-31",
  "campo": {
    "nombre": "Burpees",
    "tipo_dato": "entero",
    "unidad": "repeticiones",
    "configuracion": null
  }
}
```

Validaciones:

- El período es obligatorio y `fecha_fin >= fecha_inicio`.
- Nombre de evaluación y campo: 1–150 caracteres.
- Unidad: máximo 50 caracteres.
- Solo se admiten los cuatro tipos documentados; otro valor devuelve `422 TIPO_DATO_INVALIDO`.
- Períodos solapados están permitidos y no constituyen duplicado.

Respuesta: `201` con evaluación creada.

Errores: `400 JSON_INVALIDO`, `403 CENTRO_NO_ASIGNADO`, `404 ACTIVIDAD_NO_ENCONTRADA`, `422 VALIDACION_FALLIDA`, `422 TIPO_DATO_INVALIDO`.

### POST /admin/api/evaluaciones/update.php

Actualiza nombre, instrucciones, período y definición del campo.

Reglas:

- Si existe una sesión, el nuevo período debe contener `fecha_realizacion`.
- Si existen resultados medidos, cambiar `tipo_dato` devuelve `409 RESULTADOS_EXISTENTES` para evitar reinterpretar datos.
- El campo `actividad_id` no se puede cambiar.
- La unidad puede corregirse desde Admin y queda registrada mediante `updated_by_admin_id`.

Respuesta: `200` con la evaluación actualizada.

### POST /admin/api/evaluaciones/archive.php

Entrada: `{ "evaluacion_id": 8 }`.

Archiva sin eliminar resultados. Una evaluación archivada desaparece de la vista del monitor. Repetir la operación devuelve la misma representación y no crea efectos adicionales.

Respuesta: `200`.

### POST /admin/api/evaluaciones/reopen.php

Entrada: `{ "sesion_id": 15 }`.

Convierte una sesión finalizada a `en_curso`, vacía `finalizada_at` y registra `reopened_at` y `reopened_by_admin_id`. No elimina resultados. Si la fecha actual está fuera del período, Admin también debe ampliar el período antes de que el monitor pueda editar.

Respuesta: `200`.

Errores: `403 CENTRO_NO_ASIGNADO`, `404 SESION_NO_ENCONTRADA`, `409 SESION_YA_EN_CURSO`.

### POST /admin/api/evaluaciones/update_result.php

Permite corregir un resultado aunque la sesión esté finalizada.

Entrada:

```json
{
  "sesion_id": 15,
  "campo_id": 11,
  "inscrito_id": 442,
  "estado": "medido",
  "valor_numero": 27,
  "valor_texto": null,
  "calificador": "exacto"
}
```

Aplica las mismas validaciones de tipo que el guardado del monitor y registra `updated_by_admin_id`.

Respuesta: `200` con el resultado normalizado.

## Endpoints del monitor

### GET /api/evaluaciones/listar.php

Parámetro obligatorio: `actividad_id`.

Devuelve únicamente evaluaciones no archivadas que sean `pendiente`, `en_curso` o `en_curso_fuera_de_plazo`. Las programadas y finalizadas pueden incluirse en una colección secundaria de solo lectura si la UI la necesita, pero no deben añadir acciones primarias.

Respuesta `200`:

```json
{
  "success": true,
  "data": {
    "pendientes": [
      {
        "id": 8,
        "nombre": "Burpees en 1 minuto",
        "fecha_inicio": "2026-10-01",
        "fecha_fin": "2026-10-31",
        "estado": "pendiente",
        "accion": "realizar"
      }
    ],
    "en_curso": []
  }
}
```

Errores: `401 SESION_CENTRO_REQUERIDA`, `403 CENTRO_INCORRECTO`, `404 ACTIVIDAD_NO_ENCONTRADA`.

### POST /api/evaluaciones/iniciar.php

Entrada: `{ "evaluacion_id": 8, "fecha_realizacion": "2026-10-14" }`.

Reglas:

- La fecha debe estar dentro del período inclusivo o devuelve `422 FECHA_FUERA_DE_PERIODO`.
- Una fecha posterior a hoy devuelve `422 FECHA_FUTURA`.
- El centro de sesión debe coincidir con la actividad.
- El MVP permite una realización por planificación y usa `numero_intento = 1`.
- Si ya hay una sesión en curso, devuelve esa misma sesión con `200` para que `Realizar` y `Continuar` sean idempotentes.
- Si ya está finalizada, devuelve `409 EVALUACION_YA_REALIZADA`.
- Una carrera al insertar el mismo intento se traduce a `409 INTENTO_YA_EXISTE`; el cliente debe recargar y continuar la sesión existente.

Respuesta: `201` al crear o `200` al recuperar una sesión existente. La creación genera registros `sin_evaluar` para los participantes actuales de la actividad y copia su nombre y apellidos en cada resultado para preservar el histórico.

### GET /api/evaluaciones/detalle.php

Parámetro obligatorio: `sesion_id`.

Devuelve evaluación, campo, fecha real, estado, participantes y resultados. La lista usa la instantánea de nombre y apellidos guardada al iniciar la sesión, incluso si una inscripción fue eliminada posteriormente.

Respuesta `200`:

```json
{
  "success": true,
  "data": {
    "sesion": { "id": 15, "fecha_realizacion": "2026-10-14", "estado": "en_curso" },
    "evaluacion": { "id": 8, "nombre": "Burpees en 1 minuto", "instrucciones": "..." },
    "campos": [
      { "id": 11, "nombre": "Burpees", "tipo_dato": "entero", "unidad": "repeticiones" }
    ],
    "participantes": [
      {
        "id": 442,
        "nombre": "Lucía",
        "apellidos": "Álvarez García",
        "resultados": [
          { "campo_id": 11, "estado": "sin_evaluar", "valor_numero": null, "valor_texto": null, "calificador": null }
        ]
      }
    ],
    "cobertura": { "medidos": 0, "sin_evaluar": 1, "total": 1 }
  }
}
```

### POST /api/evaluaciones/guardar_resultado.php

Guarda una fila de manera independiente.

Entrada medida:

```json
{
  "sesion_id": 15,
  "campo_id": 11,
  "inscrito_id": 442,
  "estado": "medido",
  "valor_numero": 0,
  "valor_texto": null,
  "calificador": "exacto"
}
```

Entrada sin evaluar:

```json
{
  "sesion_id": 15,
  "campo_id": 11,
  "inscrito_id": 442,
  "estado": "sin_evaluar",
  "valor_numero": null,
  "valor_texto": null,
  "calificador": null
}
```

Validaciones:

- Sesión, campo y participante deben pertenecer a la misma evaluación/actividad.
- `entero` rechaza decimales.
- `decimal` acepta como máximo tres decimales.
- `duracion` exige un valor mayor o igual que cero.
- `texto_corto` exige `valor_texto` y rechaza más de 255 caracteres.
- El tipo de payload incompatible devuelve `422 TIPO_DATO_INVALIDO`.
- `sin_evaluar` exige valores y calificador nulos.

Respuesta: `200` con resultado y cobertura actualizados.

#### Idempotencia

La combinación `(evaluacion_sesion_id, evaluacion_campo_id, participante_ref)` es única. Para participantes activos se usa `participante_ref = "inscrito:{id}"`. Guardar la misma fila actualiza el registro existente; nunca crea duplicados, incluso si posteriormente `inscrito_id` pasa a `null`. El valor numérico cero se conserva y no se interpreta como vacío.

### POST /api/evaluaciones/finalizar.php

Entrada normal: `{ "sesion_id": 15, "confirmar_pendientes": false }`.

- Si no quedan pendientes, cambia a `finalizada` y fija `finalizada_at`.
- Si quedan participantes `sin_evaluar` y no se confirma, devuelve `409 HAY_RESULTADOS_PENDIENTES` con el recuento.
- El monitor puede repetir con `confirmar_pendientes: true`; los pendientes permanecen explícitamente como `sin_evaluar`.
- Repetir la finalización de la misma sesión devuelve la sesión finalizada con `200`.

Respuesta: `200` con cobertura final.

## Catálogo mínimo de errores

| HTTP | Código | Significado |
|---|---|---|
| 400 | `JSON_INVALIDO` | El cuerpo no es JSON válido. |
| 400 | `ID_INVALIDO` | Falta un ID entero positivo. |
| 401 | `SESION_CENTRO_REQUERIDA` | No existe sesión operativa del centro. |
| 403 | `CENTRO_NO_ASIGNADO` | El Admin no tiene asignado el centro. |
| 403 | `CENTRO_INCORRECTO` | La sesión del monitor pertenece a otro centro. |
| 404 | `ACTIVIDAD_NO_ENCONTRADA` | La actividad no existe o no es visible. |
| 404 | `EVALUACION_NO_ENCONTRADA` | La evaluación no existe o no es visible. |
| 404 | `SESION_NO_ENCONTRADA` | La realización no existe o no es visible. |
| 409 | `SESION_YA_FINALIZADA` | El monitor intentó modificar una sesión cerrada. |
| 409 | `INTENTO_YA_EXISTE` | Una operación concurrente creó el intento. |
| 409 | `HAY_RESULTADOS_PENDIENTES` | Finalización pendiente de confirmación. |
| 422 | `FECHA_FUERA_DE_PERIODO` | La fecha real no pertenece a la ventana. |
| 422 | `FECHA_FUTURA` | No se permiten realizaciones futuras. |
| 422 | `TIPO_DATO_INVALIDO` | Tipo o valor incompatible con el campo. |

## Transacciones y concurrencia

- Crear evaluación y campo es una única transacción.
- Iniciar sesión bloquea la planificación durante la comprobación, usa `numero_intento = 1` y se apoya en la clave única como última defensa.
- Guardar resultado es un `upsert` sobre la clave sesión-campo-participante.
- Finalizar bloquea la sesión mientras calcula pendientes y cambia el estado.
- No se confía en recuentos enviados por el cliente.

## Casos de contrato que deben automatizar M2/M4

1. Admin asignado crea y lista; Admin de otro centro obtiene `403 CENTRO_NO_ASIGNADO`.
2. Período invertido falla y períodos solapados se aceptan.
3. Monitor del centro correcto inicia; otro centro recibe `403 CENTRO_INCORRECTO`.
4. Fecha anterior/posterior al período devuelve `422 FECHA_FUERA_DE_PERIODO`.
5. Fecha futura devuelve `422 FECHA_FUTURA` aunque esté dentro del período.
6. Dos inicios recuperan la misma sesión; la base impide duplicar el intento.
7. Cero medido se conserva; vacío se representa con `sin_evaluar`.
8. Entero, decimal, duración y texto corto validan payloads compatibles e incompatibles.
9. Participante de otra actividad no puede recibir resultados.
10. Guardar dos veces actualiza una sola fila.
11. Finalizar con pendientes requiere confirmación.
12. Una sesión finalizada bloquea al monitor y permite corrección/reapertura desde Admin.

La regla del MVP es **una realización por planificación**. Para repetir una prueba en octubre, enero y mayo, Admin duplica la planificación y cambia el período.
