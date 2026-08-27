<?php

/**
 * Outbox and SMTP delivery for attendance observations.
 *
 * Queueing is designed to run inside the attendance transaction. Delivery must
 * run only after commit so an SMTP failure can never undo saved attendance.
 */

function observationNotificationNormalize($text)
{
    $normalized = str_replace(["\r\n", "\r"], "\n", (string) $text);
    $lines = explode("\n", trim($normalized));
    $lines = array_map(static function ($line) {
        return rtrim($line);
    }, $lines);
    return implode("\n", $lines);
}

function observationNotificationShouldQueue($previous, $current)
{
    $previousNormalized = observationNotificationNormalize($previous);
    $currentNormalized = observationNotificationNormalize($current);
    return $currentNormalized !== '' && $previousNormalized !== $currentNormalized;
}

function observationNotificationEnv($name, $default = null)
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

function observationNotificationSmtpConfig()
{
    $encryption = strtolower(trim((string) observationNotificationEnv('PURI_SMTP_ENCRYPTION', 'tls')));
    if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
        $encryption = 'tls';
    }
    $port = filter_var(observationNotificationEnv('PURI_SMTP_PORT', '587'), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]);
    $timeout = filter_var(observationNotificationEnv('PURI_SMTP_TIMEOUT', '15'), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 120],
    ]);

    return [
        'host' => trim((string) observationNotificationEnv('PURI_SMTP_HOST', '')),
        'port' => $port === false ? 587 : (int) $port,
        'username' => (string) observationNotificationEnv('PURI_SMTP_USERNAME', ''),
        'password' => (string) observationNotificationEnv('PURI_SMTP_PASSWORD', ''),
        'encryption' => $encryption,
        'from_email' => trim((string) observationNotificationEnv('PURI_SMTP_FROM_EMAIL', '')),
        'from_name' => trim((string) observationNotificationEnv('PURI_SMTP_FROM_NAME', 'Puri')),
        'timeout' => $timeout === false ? 15 : (int) $timeout,
    ];
}

function observationNotificationSmtpIsConfigured(?array $config = null)
{
    $config = $config ?: observationNotificationSmtpConfig();
    if ($config['host'] === '' || !filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return $config['username'] === '' || $config['password'] !== '';
}

function observationNotificationFetchContext(PDO $pdo, $activityId)
{
    $stmt = $pdo->prepare(
        'SELECT a.id AS actividad_id, a.nombre AS actividad_nombre, a.grupo AS actividad_grupo,
                a.dias_semana, a.hora_inicio, a.hora_fin,
                a.fecha_inicio AS actividad_fecha_inicio, a.fecha_fin AS actividad_fecha_fin,
                i.id AS instalacion_id, i.nombre AS instalacion_nombre,
                c.id AS centro_id, c.nombre AS centro_nombre
         FROM actividades a
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE a.id = ?'
    );
    $stmt->execute([(int) $activityId]);
    $context = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$context) {
        throw new RuntimeException('No se pudo resolver el contexto de la actividad.');
    }
    return $context;
}

function observationNotificationFetchRecipients(PDO $pdo, $centerId)
{
    $stmt = $pdo->prepare(
        "SELECT a.id, LOWER(TRIM(a.email)) AS email
         FROM admin_asignaciones aa
         INNER JOIN admins a ON a.id = aa.admin_id
         WHERE aa.centro_id = ? AND a.email IS NOT NULL AND TRIM(a.email) <> ''
         ORDER BY a.id ASC"
    );
    $stmt->execute([(int) $centerId]);
    $recipients = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $email = strtolower(trim((string) $row['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($recipients[$email])) {
            continue;
        }
        $recipients[$email] = [
            'admin_id' => (int) $row['id'],
            'email' => $email,
        ];
    }
    return array_values($recipients);
}

function observationNotificationSingleLine($value)
{
    return trim((string) preg_replace('/[\r\n\x00-\x1F\x7F]+/u', ' ', (string) $value));
}

function observationNotificationFormatDate($value)
{
    $parts = explode('-', (string) $value);
    return count($parts) === 3 ? $parts[2] . '/' . $parts[1] . '/' . $parts[0] : (string) $value;
}

function observationNotificationBuildMessage(array $context, $fechaObservacion, $observacion)
{
    $centerName = observationNotificationSingleLine($context['centro_nombre'] ?? 'Centro');
    $activityName = observationNotificationSingleLine($context['actividad_nombre'] ?? 'Actividad');
    $installationName = observationNotificationSingleLine($context['instalacion_nombre'] ?? '');
    $group = observationNotificationSingleLine($context['actividad_grupo'] ?? '');
    $days = observationNotificationSingleLine($context['dias_semana'] ?? '');
    $startTime = !empty($context['hora_inicio']) ? substr((string) $context['hora_inicio'], 0, 5) : '';
    $endTime = !empty($context['hora_fin']) ? substr((string) $context['hora_fin'], 0, 5) : '';
    $schedule = trim($startTime . ($endTime !== '' ? '–' . $endTime : ''));
    $activityStart = !empty($context['actividad_fecha_inicio'])
        ? observationNotificationFormatDate($context['actividad_fecha_inicio'])
        : '';
    $activityEnd = !empty($context['actividad_fecha_fin'])
        ? observationNotificationFormatDate($context['actividad_fecha_fin'])
        : '';
    $period = trim($activityStart . ($activityEnd !== '' ? ' → ' . $activityEnd : ''));
    $subject = observationNotificationSingleLine(
        '[Puri] Observación · ' . $centerName . ' · ' . $activityName . ' · ' . observationNotificationFormatDate($fechaObservacion)
    );

    $lines = [
        'Se ha registrado o actualizado una observación de actividad en Puri.',
        '',
        'Centro: ' . $centerName,
        'Instalación: ' . $installationName,
        'Actividad: ' . $activityName,
    ];
    if ($group !== '') $lines[] = 'Grupo: ' . $group;
    if ($days !== '') $lines[] = 'Días: ' . $days;
    if ($schedule !== '') $lines[] = 'Horario: ' . $schedule;
    if ($period !== '') $lines[] = 'Período de la actividad: ' . $period;
    $lines[] = 'Fecha de la observación: ' . observationNotificationFormatDate($fechaObservacion);
    $lines[] = 'Registrada en el sistema: ' . date('d/m/Y H:i');
    $lines[] = '';
    $lines[] = 'Observación completa:';
    $lines[] = observationNotificationNormalize($observacion);
    $lines[] = '';
    $lines[] = 'Este mensaje se ha enviado a las personas coordinadoras asignadas explícitamente al centro.';

    return ['subject' => $subject, 'body' => implode("\n", $lines)];
}

function observationNotificationQueue(PDO $pdo, $activityId, $observationId, $fechaObservacion, $previous, $current)
{
    if (!observationNotificationShouldQueue($previous, $current)) {
        return null;
    }

    $normalized = observationNotificationNormalize($current);
    $context = observationNotificationFetchContext($pdo, $activityId);
    $recipients = observationNotificationFetchRecipients($pdo, (int) $context['centro_id']);
    $message = observationNotificationBuildMessage($context, $fechaObservacion, $normalized);
    $eventState = empty($recipients) ? 'sin_destinatarios' : 'pendiente';

    $stmt = $pdo->prepare(
        'INSERT INTO notificacion_observacion_eventos
            (observacion_id, actividad_id, centro_id, fecha_observacion, contenido_hash,
             observacion_snapshot, asunto, cuerpo_texto, destinatarios_total, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $observationId ? (int) $observationId : null,
        (int) $activityId,
        (int) $context['centro_id'],
        $fechaObservacion,
        hash('sha256', $normalized),
        $normalized,
        $message['subject'],
        $message['body'],
        count($recipients),
        $eventState,
    ]);
    $eventId = (int) $pdo->lastInsertId();

    if (!empty($recipients)) {
        $recipientStmt = $pdo->prepare(
            'INSERT INTO notificacion_observacion_destinatarios
                (evento_id, admin_id, destinatario_email)
             VALUES (?, ?, ?)'
        );
        foreach ($recipients as $recipient) {
            $recipientStmt->execute([$eventId, $recipient['admin_id'], $recipient['email']]);
        }
    } else {
        error_log(sprintf(
            'Puri observacion sin destinatarios: evento_id=%d actividad_id=%d centro_id=%d',
            $eventId,
            (int) $activityId,
            (int) $context['centro_id']
        ));
    }

    return $eventId;
}

function observationNotificationDefaultSender($recipientEmail, $subject, $body)
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Dependencias de correo no instaladas.');
    }
    require_once $autoload;

    $config = observationNotificationSmtpConfig();
    if (!observationNotificationSmtpIsConfigured($config)) {
        throw new RuntimeException('SMTP no configurado.');
    }

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $config['host'];
    $mailer->Port = $config['port'];
    $mailer->Timeout = $config['timeout'];
    $mailer->SMTPDebug = 0;
    $mailer->SMTPAuth = $config['username'] !== '';
    if ($mailer->SMTPAuth) {
        $mailer->Username = $config['username'];
        $mailer->Password = $config['password'];
    }
    if ($config['encryption'] === 'tls') {
        $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($config['encryption'] === 'ssl') {
        $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mailer->SMTPSecure = '';
        $mailer->SMTPAutoTLS = false;
    }
    $mailer->CharSet = 'UTF-8';
    $mailer->Encoding = 'base64';
    $mailer->setFrom($config['from_email'], $config['from_name']);
    $mailer->addAddress($recipientEmail);
    $mailer->Subject = $subject;
    $mailer->Body = $body;
    $mailer->isHTML(false);
    $mailer->send();
}

function observationNotificationSafeError(Throwable $exception)
{
    $message = observationNotificationSingleLine($exception->getMessage());
    if ($message === '') $message = get_class($exception);
    return function_exists('mb_substr') ? mb_substr($message, 0, 500, 'UTF-8') : substr($message, 0, 500);
}

function observationNotificationRefreshEventStatus(PDO $pdo, $eventId)
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) AS enviados,
                SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) AS fallidos
         FROM notificacion_observacion_destinatarios
         WHERE evento_id = ?"
    );
    $stmt->execute([(int) $eventId]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int) ($counts['total'] ?? 0);
    $sent = (int) ($counts['enviados'] ?? 0);
    $failed = (int) ($counts['fallidos'] ?? 0);
    if ($total === 0) $state = 'sin_destinatarios';
    elseif ($sent === $total) $state = 'enviado';
    elseif ($sent > 0) $state = 'parcial';
    elseif ($failed === $total) $state = 'fallido';
    else $state = 'pendiente';

    $update = $pdo->prepare('UPDATE notificacion_observacion_eventos SET estado = ? WHERE id = ?');
    $update->execute([$state, (int) $eventId]);
    return ['estado' => $state, 'total' => $total, 'enviados' => $sent, 'fallidos' => $failed];
}

function observationNotificationDispatchEvent(PDO $pdo, $eventId, ?callable $sender = null)
{
    $config = observationNotificationSmtpConfig();
    if ($sender === null && !observationNotificationSmtpIsConfigured($config)) {
        error_log(sprintf('Puri SMTP pendiente de configurar: evento_id=%d', (int) $eventId));
        return ['estado' => 'pendiente_configuracion', 'procesados' => 0];
    }
    $sender = $sender ?: 'observationNotificationDefaultSender';

    $stmt = $pdo->prepare(
        "SELECT d.id, d.destinatario_email, d.intentos, e.asunto, e.cuerpo_texto
         FROM notificacion_observacion_destinatarios d
         INNER JOIN notificacion_observacion_eventos e ON e.id = d.evento_id
         WHERE d.evento_id = ?
           AND d.estado IN ('pendiente', 'fallido')
           AND d.intentos < 5
           AND (d.proximo_intento_at IS NULL OR d.proximo_intento_at <= NOW())
         ORDER BY d.id ASC"
    );
    $stmt->execute([(int) $eventId]);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($deliveries as $delivery) {
        try {
            $sender($delivery['destinatario_email'], $delivery['asunto'], $delivery['cuerpo_texto']);
            $update = $pdo->prepare(
                "UPDATE notificacion_observacion_destinatarios
                 SET estado = 'enviado', intentos = intentos + 1, enviado_at = NOW(),
                     proximo_intento_at = NULL, ultimo_error = NULL
                 WHERE id = ?"
            );
            $update->execute([(int) $delivery['id']]);
        } catch (Throwable $exception) {
            $attempt = (int) $delivery['intentos'] + 1;
            $delayMinutes = min(60, 2 ** min($attempt, 5));
            $update = $pdo->prepare(
                "UPDATE notificacion_observacion_destinatarios
                 SET estado = 'fallido', intentos = intentos + 1,
                     proximo_intento_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), ultimo_error = ?
                 WHERE id = ?"
            );
            $update->execute([$delayMinutes, observationNotificationSafeError($exception), (int) $delivery['id']]);
            error_log(sprintf(
                'Puri fallo SMTP: evento_id=%d destinatario_id=%d intento=%d',
                (int) $eventId,
                (int) $delivery['id'],
                $attempt
            ));
        }
    }

    $status = observationNotificationRefreshEventStatus($pdo, $eventId);
    $status['procesados'] = count($deliveries);
    return $status;
}

function observationNotificationProcessPending(PDO $pdo, $limit = 25, ?callable $sender = null)
{
    $limit = max(1, min(100, (int) $limit));
    $stmt = $pdo->prepare(
        "SELECT DISTINCT evento_id
         FROM notificacion_observacion_destinatarios
         WHERE estado IN ('pendiente', 'fallido')
           AND intentos < 5
           AND (proximo_intento_at IS NULL OR proximo_intento_at <= NOW())
         ORDER BY evento_id ASC
         LIMIT ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $eventIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $processed = 0;
    foreach ($eventIds as $eventId) {
        $result = observationNotificationDispatchEvent($pdo, (int) $eventId, $sender);
        $processed += (int) ($result['procesados'] ?? 0);
    }
    return ['eventos' => count($eventIds), 'entregas_procesadas' => $processed];
}
