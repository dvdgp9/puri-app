<?php

class EvaluacionesMonitorApiException extends RuntimeException
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

function evaluacionesMonitorFail($httpStatus, $code, $message, array $fields = [])
{
    throw new EvaluacionesMonitorApiException($httpStatus, $code, $message, $fields);
}

function evaluacionesMonitorRespond($status, array $payload)
{
    http_response_code((int) $status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function evaluacionesMonitorRespondSuccess($data, $status = 200)
{
    evaluacionesMonitorRespond($status, ['success' => true, 'data' => $data]);
}

function evaluacionesMonitorRespondException(EvaluacionesMonitorApiException $exception)
{
    $error = [
        'code' => $exception->apiCode,
        'message' => $exception->getMessage(),
    ];
    if (!empty($exception->fields)) {
        $error['fields'] = $exception->fields;
    }
    evaluacionesMonitorRespond($exception->httpStatus, ['success' => false, 'error' => $error]);
}

function evaluacionesMonitorRespondInternal(Throwable $exception, $context)
{
    error_log($context . ': ' . $exception->getMessage());
    evaluacionesMonitorRespond(500, [
        'success' => false,
        'error' => [
            'code' => 'ERROR_INTERNO',
            'message' => 'Error interno del servidor.',
        ],
    ]);
}

function evaluacionesMonitorRequireMethod($method)
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        evaluacionesMonitorFail(405, 'METODO_NO_PERMITIDO', 'Método no permitido.');
    }
}

function evaluacionesMonitorReadJson()
{
    $raw = file_get_contents('php://input');
    $input = json_decode((string) $raw, true);
    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        evaluacionesMonitorFail(400, 'JSON_INVALIDO', 'El cuerpo debe ser un objeto JSON válido.');
    }
    return $input;
}

function evaluacionesMonitorPositiveId($value, $field)
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        evaluacionesMonitorFail(400, 'ID_INVALIDO', 'El identificador no es válido.', [$field => 'Debe ser un entero positivo.']);
    }
    return (int) $id;
}

function evaluacionesMonitorRequireActivity(PDO $pdo, $activityId, $centerId)
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.nombre, a.grupo, a.fecha_inicio AS actividad_fecha_inicio,
                a.fecha_fin AS actividad_fecha_fin, i.id AS instalacion_id,
                i.nombre AS instalacion_nombre, i.centro_id, c.nombre AS centro_nombre
         FROM actividades a
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE a.id = ?'
    );
    $stmt->execute([(int) $activityId]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        evaluacionesMonitorFail(404, 'ACTIVIDAD_NO_ENCONTRADA', 'Actividad no encontrada.');
    }
    if ((int) $activity['centro_id'] !== (int) $centerId) {
        evaluacionesMonitorFail(403, 'CENTRO_INCORRECTO', 'La actividad pertenece a otro centro.');
    }
    return $activity;
}

function evaluacionesMonitorRequireEvaluation(PDO $pdo, $evaluationId, $centerId, $allowArchived = false)
{
    $stmt = $pdo->prepare(
        'SELECT e.*, a.nombre AS actividad_nombre, a.grupo AS actividad_grupo,
                i.id AS instalacion_id, i.nombre AS instalacion_nombre,
                i.centro_id, c.nombre AS centro_nombre
         FROM evaluaciones e
         INNER JOIN actividades a ON a.id = e.actividad_id
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE e.id = ?'
    );
    $stmt->execute([(int) $evaluationId]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evaluation || (!$allowArchived && $evaluation['archivada_at'] !== null)) {
        evaluacionesMonitorFail(404, 'EVALUACION_NO_ENCONTRADA', 'Evaluación no encontrada.');
    }
    if ((int) $evaluation['centro_id'] !== (int) $centerId) {
        evaluacionesMonitorFail(403, 'CENTRO_INCORRECTO', 'La evaluación pertenece a otro centro.');
    }
    return $evaluation;
}

function evaluacionesMonitorRequireSession(PDO $pdo, $sessionId, $centerId)
{
    $stmt = $pdo->prepare(
        'SELECT es.*, e.actividad_id, e.nombre AS evaluacion_nombre, e.instrucciones,
                e.fecha_inicio, e.fecha_fin, e.archivada_at,
                a.nombre AS actividad_nombre, a.grupo AS actividad_grupo,
                i.id AS instalacion_id, i.nombre AS instalacion_nombre,
                i.centro_id, c.nombre AS centro_nombre
         FROM evaluacion_sesiones es
         INNER JOIN evaluaciones e ON e.id = es.evaluacion_id
         INNER JOIN actividades a ON a.id = e.actividad_id
         INNER JOIN instalaciones i ON i.id = a.instalacion_id
         INNER JOIN centros c ON c.id = i.centro_id
         WHERE es.id = ?'
    );
    $stmt->execute([(int) $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session || $session['archivada_at'] !== null) {
        evaluacionesMonitorFail(404, 'SESION_NO_ENCONTRADA', 'Realización no encontrada.');
    }
    if ((int) $session['centro_id'] !== (int) $centerId) {
        evaluacionesMonitorFail(403, 'CENTRO_INCORRECTO', 'La realización pertenece a otro centro.');
    }
    return $session;
}

function evaluacionesMonitorRequireWritablePeriod(array $session, $today = null)
{
    $today = $today ?: date('Y-m-d');
    if (!evaluacionesDateInsidePeriod($today, $session['fecha_inicio'], $session['fecha_fin'])) {
        evaluacionesMonitorFail(
            409,
            'EVALUACION_FUERA_DE_PLAZO',
            'La evaluación está fuera de su período. Un administrador debe ampliar el período.'
        );
    }
}

function evaluacionesMonitorFetchCoverage(PDO $pdo, $sessionId)
{
    $stmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN er.estado = 'medido' THEN 1 ELSE 0 END) AS medidos,
            SUM(CASE WHEN er.estado = 'sin_evaluar' THEN 1 ELSE 0 END) AS sin_evaluar,
            COUNT(*) AS total
         FROM evaluacion_resultados er
         INNER JOIN evaluacion_campos ec ON ec.id = er.evaluacion_campo_id
         WHERE er.evaluacion_sesion_id = ? AND ec.orden = 1"
    );
    $stmt->execute([(int) $sessionId]);
    $coverage = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return [
        'medidos' => (int) ($coverage['medidos'] ?? 0),
        'sin_evaluar' => (int) ($coverage['sin_evaluar'] ?? 0),
        'total' => (int) ($coverage['total'] ?? 0),
    ];
}

function evaluacionesMonitorFetchFields(PDO $pdo, $evaluationId)
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, tipo_dato, unidad, orden
         FROM evaluacion_campos
         WHERE evaluacion_id = ?
         ORDER BY orden ASC, id ASC'
    );
    $stmt->execute([(int) $evaluationId]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fields as &$field) {
        $field['id'] = (int) $field['id'];
        $field['orden'] = (int) $field['orden'];
    }
    unset($field);
    return $fields;
}

function evaluacionesMonitorFetchEvaluationDto(PDO $pdo, $evaluationId)
{
    $stmt = $pdo->prepare(
        'SELECT e.*, es.id AS sesion_id, es.fecha_realizacion,
                es.estado AS sesion_estado, es.finalizada_at
         FROM evaluaciones e
         LEFT JOIN evaluacion_sesiones es
           ON es.evaluacion_id = e.id AND es.numero_intento = 1
         WHERE e.id = ?'
    );
    $stmt->execute([(int) $evaluationId]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$evaluation) {
        evaluacionesMonitorFail(404, 'EVALUACION_NO_ENCONTRADA', 'Evaluación no encontrada.');
    }

    $sessionId = !empty($evaluation['sesion_id']) ? (int) $evaluation['sesion_id'] : null;
    return [
        'id' => (int) $evaluation['id'],
        'actividad_id' => (int) $evaluation['actividad_id'],
        'nombre' => $evaluation['nombre'],
        'instrucciones' => $evaluation['instrucciones'],
        'fecha_inicio' => $evaluation['fecha_inicio'],
        'fecha_fin' => $evaluation['fecha_fin'],
        'estado' => evaluacionesDeriveState($evaluation),
        'campos' => evaluacionesMonitorFetchFields($pdo, $evaluationId),
        'sesion' => $sessionId ? [
            'id' => $sessionId,
            'fecha_realizacion' => $evaluation['fecha_realizacion'],
            'estado' => $evaluation['sesion_estado'],
            'finalizada_at' => $evaluation['finalizada_at'],
        ] : null,
        'cobertura' => $sessionId ? evaluacionesMonitorFetchCoverage($pdo, $sessionId) : [
            'medidos' => 0,
            'sin_evaluar' => 0,
            'total' => 0,
        ],
    ];
}

function evaluacionesMonitorFetchSessionDetail(PDO $pdo, $sessionId, $centerId)
{
    $session = evaluacionesMonitorRequireSession($pdo, $sessionId, $centerId);
    $fields = evaluacionesMonitorFetchFields($pdo, (int) $session['evaluacion_id']);
    $stmt = $pdo->prepare(
        'SELECT er.id AS resultado_id, er.inscrito_id, er.participante_ref,
                er.participante_nombre AS nombre, er.participante_apellidos AS apellidos,
                er.estado, er.valor_numero, er.valor_texto, er.calificador, er.updated_at,
                ec.id AS campo_id, ec.nombre AS campo_nombre, ec.tipo_dato, ec.unidad, ec.orden
         FROM evaluacion_resultados er
         INNER JOIN evaluacion_campos ec ON ec.id = er.evaluacion_campo_id
         WHERE er.evaluacion_sesion_id = ?
         ORDER BY er.participante_apellidos ASC, er.participante_nombre ASC, ec.orden ASC'
    );
    $stmt->execute([(int) $sessionId]);
    $participants = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $reference = $row['participante_ref'];
        if (!isset($participants[$reference])) {
            $participants[$reference] = [
                'id' => $row['inscrito_id'] !== null ? (int) $row['inscrito_id'] : null,
                'participante_ref' => $reference,
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

    return [
        'sesion' => [
            'id' => (int) $session['id'],
            'fecha_realizacion' => $session['fecha_realizacion'],
            'estado' => $session['estado'],
            'finalizada_at' => $session['finalizada_at'],
            'fuera_de_plazo' => !evaluacionesDateInsidePeriod(date('Y-m-d'), $session['fecha_inicio'], $session['fecha_fin']),
        ],
        'evaluacion' => [
            'id' => (int) $session['evaluacion_id'],
            'nombre' => $session['evaluacion_nombre'],
            'instrucciones' => $session['instrucciones'],
            'fecha_inicio' => $session['fecha_inicio'],
            'fecha_fin' => $session['fecha_fin'],
        ],
        'actividad' => [
            'id' => (int) $session['actividad_id'],
            'nombre' => $session['actividad_nombre'],
            'grupo' => $session['actividad_grupo'],
            'instalacion_nombre' => $session['instalacion_nombre'],
            'centro_nombre' => $session['centro_nombre'],
        ],
        'campos' => $fields,
        'participantes' => array_values($participants),
        'cobertura' => evaluacionesMonitorFetchCoverage($pdo, $sessionId),
    ];
}

function evaluacionesMonitorRollback(PDO $pdo)
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

