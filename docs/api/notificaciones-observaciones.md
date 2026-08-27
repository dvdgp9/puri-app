# Notificaciones de observaciones

## Comportamiento

Al guardar asistencia, una observación no vacía nueva o cuyo contenido haya cambiado se añade a una outbox. Reenviar el mismo texto normalizado no crea otra notificación y vaciar una observación no envía correo.

Los destinatarios se obtienen únicamente de `admin_asignaciones` para el centro de la actividad. Cada cuenta necesita un email válido en Admin. Las direcciones se normalizan a minúsculas y se deduplican por evento. Un superadmin no recibe mensajes solo por su rol: debe existir una asignación explícita al centro.

El correo de texto plano contiene:

- observación completa;
- centro e instalación;
- actividad y grupo;
- días, horario y período de la actividad;
- fecha de la observación y momento de registro.

La asistencia y la observación se confirman antes de intentar SMTP. Un fallo de entrega no revierte esos datos: la dirección queda en estado `fallido`, con número de intentos, siguiente reintento y un error técnico limitado. Los logs no incluyen el texto de la observación ni direcciones de correo.

Si no hay destinatarios válidos, el evento se marca como `sin_destinatarios` (sin destinatarios); no se intenta ninguna entrega.

## Configuración SMTP

Las credenciales no se guardan en el repositorio. Variables de entorno:

| Variable | Obligatoria | Ejemplo no real |
|---|---:|---|
| `PURI_SMTP_HOST` | Sí | `smtp.example.org` |
| `PURI_SMTP_PORT` | No | `587` |
| `PURI_SMTP_USERNAME` | Según servidor | `usuario-smtp` |
| `PURI_SMTP_PASSWORD` | Si hay usuario | Configuración externa/secret manager |
| `PURI_SMTP_ENCRYPTION` | No | `tls`, `ssl` o `none`; por defecto `tls` |
| `PURI_SMTP_FROM_EMAIL` | Sí | `puri@example.org` |
| `PURI_SMTP_FROM_NAME` | No | `Puri` |
| `PURI_SMTP_TIMEOUT` | No | `15` segundos |

El transporte usa PHPMailer 6 mediante Composer. Con SMTP incompleto, el guardado sigue funcionando y la outbox queda pendiente.

## Deduplicación

La comparación normaliza finales de línea, espacios finales por línea y espacios exteriores. Se encola cuando:

1. el contenido nuevo normalizado no está vacío; y
2. difiere del contenido anterior normalizado.

Cada evento contiene un hash SHA-256 para diagnóstico y una instantánea del texto/contexto. La clave única `(evento_id, destinatario_email)` garantiza como máximo una entrega por dirección dentro del mismo cambio. Si el texto cambia de A a B y posteriormente vuelve a A, se considera un cambio nuevo y genera otro evento.

## Reintento

El intento inmediato ocurre después del commit. Para reprocesar pendientes/fallos desde cron:

```bash
php scripts/process_observation_notifications.php 25
```

El parámetro opcional `limit` admite entre 1 y 100 eventos. Cada dirección tiene un máximo de cinco intentos y espera progresiva antes del siguiente reintento. La salida solo muestra recuentos; los detalles técnicos se escriben en el log del servidor.

## Estados

- Evento: `pendiente`, `enviado`, `parcial`, `fallido`, `sin_destinatarios`.
- Destinatario: `pendiente`, `enviado`, `fallido`.

## Despliegue y prueba controlada

1. Hacer copia de seguridad y aplicar `20260812_create_observation_notifications.up.sql` en pruebas.
2. Completar los emails de las cuentas coordinadoras en Admin.
3. Configurar SMTP mediante variables de entorno y reiniciar PHP/servidor si corresponde.
4. Crear una observación de prueba con dos coordinadores asignados, incluida una dirección repetida en dos cuentas si se quiere verificar deduplicación.
5. Confirmar correo, estado de outbox y logs; reenviar el mismo texto y comprobar que no aparece un evento nuevo.
6. Simular un fallo temporal de transporte y comprobar que asistencia/observación permanecen guardadas y el worker puede reintentar.
