<?php

class EvaluacionesApiException extends RuntimeException
{
    public $httpStatus;
    public $apiCode;
    public $fields;

    public function __construct($httpStatus, $apiCode, $message, array $fields = [])
    {
        parent::__construct($message);
        $this->httpStatus = (int) $httpStatus;
        $this->apiCode = (string) $apiCode;
        $this->fields = $fields;
    }
}

function evaluacionesAdminFail($httpStatus, $code, $message, array $fields = [])
{
    throw new EvaluacionesApiException($httpStatus, $code, $message, $fields);
}

function evaluacionesAdminRespond($status, array $payload)
{
    http_response_code((int) $status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function evaluacionesAdminRespondSuccess($data, $status = 200)
{
    evaluacionesAdminRespond($status, ['success' => true, 'data' => $data]);
}

function evaluacionesAdminRespondException(EvaluacionesApiException $exception)
{
    $error = [
        'code' => $exception->apiCode,
        'message' => $exception->getMessage(),
    ];

    if (!empty($exception->fields)) {
        $error['fields'] = $exception->fields;
    }

    evaluacionesAdminRespond($exception->httpStatus, ['success' => false, 'error' => $error]);
}

function evaluacionesAdminRespondInternal(Throwable $exception, $context)
{
    error_log($context . ': ' . $exception->getMessage());
    evaluacionesAdminRespond(500, [
        'success' => false,
        'error' => [
            'code' => 'ERROR_INTERNO',
            'message' => 'Error interno del servidor.',
        ],
    ]);
}

function evaluacionesAdminRequireMethod($method)
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        evaluacionesAdminFail(405, 'METODO_NO_PERMITIDO', 'Método no permitido.');
    }
}

function evaluacionesAdminReadJson()
{
    $raw = file_get_contents('php://input');
    $input = json_decode((string) $raw, true);

    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        evaluacionesAdminFail(400, 'JSON_INVALIDO', 'El cuerpo debe ser un objeto JSON válido.');
    }

    return $input;
}

function evaluacionesAdminPositiveId($value, $field)
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        evaluacionesAdminFail(400, 'ID_INVALIDO', 'El identificador no es válido.', [$field => 'Debe ser un entero positivo.']);
    }
    return (int) $id;
}

function evaluacionesAdminCanAccessCenter(PDO $pdo, array $adminInfo, $centerId)
{
    if (($adminInfo['role'] ?? '') === 'superadmin') {
        return true;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ? LIMIT 1');
    $stmt->execute([(int) $adminInfo['id'], (int) $centerId]);
    return (bool) $stmt->fetchColumn();
}

function evaluacionesAdminRequireActivity(PDO $pdo, $activityId, array $adminInfo)
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.nombre, a.grupo, a.dias_semana, a.hora_inicio, a.hora_fin,
                a.fecha_inicio AS actividad_fecha_inicio, a.fecha_fin AS actividad_fecha_fin,
                i.id AS instalacion_id, i.nombre AS instalacion_nombre,
                c.id AS centro_id, c.nombre AS centro_nombre
         FROM actividades a
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE a.id = ?'
    );
    $stmt->execute([(int) $activityId]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        evaluacionesAdminFail(404, 'ACTIVIDAD_NO_ENCONTRADA', 'Actividad no encontrada.');
    }

    if (!evaluacionesAdminCanAccessCenter($pdo, $adminInfo, $activity['centro_id'])) {
        evaluacionesAdminFail(403, 'CENTRO_NO_ASIGNADO', 'No tienes acceso al centro de esta actividad.');
    }

    return $activity;
}

function evaluacionesAdminRequireEvaluation(PDO $pdo, $evaluationId, array $adminInfo)
{
    $stmt = $pdo->prepare(
        'SELECT e.*, i.id AS instalacion_id, i.nombre AS instalacion_nombre,
                c.id AS centro_id, c.nombre AS centro_nombre,
                a.nombre AS actividad_nombre, a.grupo AS actividad_grupo
         FROM evaluaciones e
         INNER JOIN actividades a ON a.id = e.actividad_id
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE e.id = ?'
    );
    $stmt->execute([(int) $evaluationId]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evaluation) {
        evaluacionesAdminFail(404, 'EVALUACION_NO_ENCONTRADA', 'Evaluación no encontrada.');
    }

    if (!evaluacionesAdminCanAccessCenter($pdo, $adminInfo, $evaluation['centro_id'])) {
        evaluacionesAdminFail(403, 'CENTRO_NO_ASIGNADO', 'No tienes acceso al centro de esta evaluación.');
    }

    return $evaluation;
}

function evaluacionesAdminRequireSession(PDO $pdo, $sessionId, array $adminInfo)
{
    $stmt = $pdo->prepare(
        'SELECT es.*, e.actividad_id, e.fecha_inicio, e.fecha_fin, e.archivada_at,
                i.id AS instalacion_id, i.nombre AS instalacion_nombre,
                c.id AS centro_id, c.nombre AS centro_nombre,
                a.nombre AS actividad_nombre, a.grupo AS actividad_grupo
         FROM evaluacion_sesiones es
         INNER JOIN evaluaciones e ON e.id = es.evaluacion_id
         INNER JOIN actividades a ON a.id = e.actividad_id
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE es.id = ?'
    );
    $stmt->execute([(int) $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        evaluacionesAdminFail(404, 'SESION_NO_ENCONTRADA', 'Realización no encontrada.');
    }

    if (!evaluacionesAdminCanAccessCenter($pdo, $adminInfo, $session['centro_id'])) {
        evaluacionesAdminFail(403, 'CENTRO_NO_ASIGNADO', 'No tienes acceso al centro de esta realización.');
    }

    return $session;
}

function evaluacionesAdminFetchCoverage(PDO $pdo, $sessionId, $activityId)
{
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM inscritos WHERE actividad_id = ?');
    $totalStmt->execute([(int) $activityId]);
    $total = (int) $totalStmt->fetchColumn();

    if (!$sessionId) {
        return ['medidos' => 0, 'sin_evaluar' => 0, 'total_participantes' => $total];
    }

    $stmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN er.estado = 'medido' THEN 1 ELSE 0 END) AS medidos,
            SUM(CASE WHEN er.estado = 'sin_evaluar' THEN 1 ELSE 0 END) AS sin_evaluar,
            COUNT(*) AS total_snapshot
         FROM evaluacion_resultados er
         INNER JOIN evaluacion_campos ec ON ec.id = er.evaluacion_campo_id
         WHERE er.evaluacion_sesion_id = ? AND ec.orden = 1"
    );
    $stmt->execute([(int) $sessionId]);
    $coverage = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'medidos' => (int) ($coverage['medidos'] ?? 0),
        'sin_evaluar' => (int) ($coverage['sin_evaluar'] ?? 0),
        'total_participantes' => (int) ($coverage['total_snapshot'] ?? 0),
    ];
}

function evaluacionesAdminFetchSessionResults(PDO $pdo, $evaluationId, $sessionId)
{
    if (!$sessionId) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT er.id AS resultado_id, er.inscrito_id, er.participante_ref,
                er.participante_nombre AS nombre, er.participante_apellidos AS apellidos,
                er.estado, er.valor_numero, er.valor_texto, er.calificador, er.updated_at,
                ec.id AS campo_id, ec.nombre AS campo_nombre, ec.tipo_dato, ec.unidad, ec.orden
         FROM evaluacion_resultados er
         INNER JOIN evaluacion_campos ec ON ec.id = er.evaluacion_campo_id
         WHERE er.evaluacion_sesion_id = ? AND ec.evaluacion_id = ?
         ORDER BY er.participante_apellidos ASC, er.participante_nombre ASC, ec.orden ASC'
    );
    $stmt->execute([(int) $sessionId, (int) $evaluationId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $participants = [];

    foreach ($rows as $row) {
        $reference = $row['participante_ref'];
        if (!isset($participants[$reference])) {
            $participants[$reference] = [
                'participante_ref' => $reference,
                'inscrito_id' => $row['inscrito_id'] !== null ? (int) $row['inscrito_id'] : null,
                'nombre' => $row['nombre'],
                'apellidos' => $row['apellidos'],
                'inscripcion_eliminada' => $row['inscrito_id'] === null,
                'resultados' => [],
            ];
        }

        $participants[$reference]['resultados'][] = [
            'id' => (int) $row['resultado_id'],
            'campo_id' => (int) $row['campo_id'],
            'campo_nombre' => $row['campo_nombre'],
            'tipo_dato' => $row['tipo_dato'],
            'unidad' => $row['unidad'],
            'orden' => (int) $row['orden'],
            'estado' => $row['estado'],
            'valor_numero' => $row['valor_numero'] !== null ? (float) $row['valor_numero'] : null,
            'valor_texto' => $row['valor_texto'],
            'calificador' => $row['calificador'],
            'updated_at' => $row['updated_at'],
        ];
    }

    return array_values($participants);
}

function evaluacionesAdminFetchEvaluation(PDO $pdo, $evaluationId)
{
    $stmt = $pdo->prepare(
        'SELECT e.*,
                es.id AS sesion_id, es.numero_intento, es.fecha_realizacion,
                es.estado AS sesion_estado, es.iniciada_at, es.finalizada_at,
                es.reopened_at
         FROM evaluaciones e
         LEFT JOIN evaluacion_sesiones es
           ON es.evaluacion_id = e.id AND es.numero_intento = 1
         WHERE e.id = ?'
    );
    $stmt->execute([(int) $evaluationId]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evaluation) {
        evaluacionesAdminFail(404, 'EVALUACION_NO_ENCONTRADA', 'Evaluación no encontrada.');
    }

    $fieldsStmt = $pdo->prepare(
        'SELECT id, nombre, tipo_dato, unidad, orden, configuracion_json
         FROM evaluacion_campos
         WHERE evaluacion_id = ?
         ORDER BY orden ASC, id ASC'
    );
    $fieldsStmt->execute([(int) $evaluationId]);
    $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fields as &$field) {
        $field['id'] = (int) $field['id'];
        $field['orden'] = (int) $field['orden'];
        $field['configuracion'] = $field['configuracion_json'] !== null
            ? json_decode($field['configuracion_json'], true)
            : null;
        unset($field['configuracion_json']);
    }
    unset($field);

    $sessionId = !empty($evaluation['sesion_id']) ? (int) $evaluation['sesion_id'] : null;
    $dto = [
        'id' => (int) $evaluation['id'],
        'actividad_id' => (int) $evaluation['actividad_id'],
        'nombre' => $evaluation['nombre'],
        'instrucciones' => $evaluation['instrucciones'],
        'fecha_inicio' => $evaluation['fecha_inicio'],
        'fecha_fin' => $evaluation['fecha_fin'],
        'archivada_at' => $evaluation['archivada_at'],
        'estado' => evaluacionesDeriveState($evaluation),
        'campos' => $fields,
        'sesion' => null,
        'cobertura' => evaluacionesAdminFetchCoverage($pdo, $sessionId, $evaluation['actividad_id']),
        'created_at' => $evaluation['created_at'],
        'updated_at' => $evaluation['updated_at'],
    ];

    if ($sessionId !== null) {
        $dto['sesion'] = [
            'id' => $sessionId,
            'numero_intento' => (int) $evaluation['numero_intento'],
            'fecha_realizacion' => $evaluation['fecha_realizacion'],
            'estado' => $evaluation['sesion_estado'],
            'iniciada_at' => $evaluation['iniciada_at'],
            'finalizada_at' => $evaluation['finalizada_at'],
            'reopened_at' => $evaluation['reopened_at'],
        ];
    }

    return $dto;
}

function evaluacionesAdminActivityDto(array $activity)
{
    return [
        'id' => (int) $activity['id'],
        'nombre' => $activity['nombre'],
        'grupo' => $activity['grupo'] ?? null,
        'centro' => [
            'id' => (int) $activity['centro_id'],
            'nombre' => $activity['centro_nombre'],
        ],
        'instalacion' => [
            'id' => (int) $activity['instalacion_id'],
            'nombre' => $activity['instalacion_nombre'],
        ],
    ];
}

function evaluacionesAdminRollback(PDO $pdo)
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
