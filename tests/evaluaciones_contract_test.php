<?php

declare(strict_types=1);

/**
 * Contract tests for the evaluations MVP (M1).
 *
 * These tests intentionally avoid connecting to a database. They protect the
 * migration and API contract before M2 introduces executable endpoints.
 */

$root = dirname(__DIR__);
$upPath = $root . '/migrations/20260812_create_evaluaciones.up.sql';
$downPath = $root . '/migrations/20260812_create_evaluaciones.down.sql';
$docsPath = $root . '/docs/api/evaluaciones.md';

$tests = [];

function contractTest(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function expectTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function readContractFile(string $path): string
{
    expectTrue(is_file($path), 'Falta el archivo requerido: ' . $path);
    $content = file_get_contents($path);
    expectTrue($content !== false && trim($content) !== '', 'El archivo está vacío: ' . $path);
    return (string) $content;
}

function expectContains(string $content, string $needle, string $context): void
{
    expectTrue(
        str_contains($content, $needle),
        sprintf('Falta "%s" en %s.', $needle, $context)
    );
}

function expectRegex(string $content, string $pattern, string $message): void
{
    expectTrue(preg_match($pattern, $content) === 1, $message);
}

function tableDefinition(string $sql, string $table): string
{
    $pattern = '/CREATE\s+TABLE\s+' . preg_quote($table, '/') . '\s*\((.*?)\)\s*ENGINE\s*=\s*InnoDB/is';
    expectTrue(preg_match($pattern, $sql, $matches) === 1, 'No se pudo aislar la tabla ' . $table . '.');
    return $matches[1];
}

contractTest('existen la migración reversible y el contrato API', function () use ($upPath, $downPath, $docsPath): void {
    readContractFile($upPath);
    readContractFile($downPath);
    readContractFile($docsPath);
});

contractTest('la migración crea las tablas en orden de dependencia', function () use ($upPath): void {
    $sql = readContractFile($upPath);
    $tables = ['evaluaciones', 'evaluacion_campos', 'evaluacion_sesiones', 'evaluacion_resultados'];
    $previousPosition = -1;

    foreach ($tables as $table) {
        $needle = 'CREATE TABLE ' . $table;
        $position = strpos($sql, $needle);
        expectTrue($position !== false, 'Falta ' . $needle . '.');
        expectTrue($position > $previousPosition, 'Orden de creación incorrecto para ' . $table . '.');
        $previousPosition = $position;
    }
});

contractTest('evaluaciones define período, actividad, archivado y auditoría', function () use ($upPath): void {
    $table = tableDefinition(readContractFile($upPath), 'evaluaciones');

    foreach (['actividad_id INT NOT NULL', 'fecha_inicio DATE NOT NULL', 'fecha_fin DATE NOT NULL', 'archivada_at DATETIME NULL', 'created_at TIMESTAMP', 'updated_at TIMESTAMP'] as $column) {
        expectContains($table, $column, 'evaluaciones');
    }

    expectRegex($table, '/CHECK\s*\(\s*fecha_fin\s*>=\s*fecha_inicio\s*\)/i', 'Falta validar el orden del período.');
    expectRegex($table, '/FOREIGN KEY\s*\(actividad_id\)\s*REFERENCES\s+actividades\s*\(id\)\s*ON DELETE CASCADE/is', 'Falta la FK de actividad con borrado en cascada.');
    expectRegex($table, '/FOREIGN KEY\s*\(created_by_admin_id\)\s*REFERENCES\s+admins\s*\(id\)\s*ON DELETE SET NULL/is', 'Falta auditoría del admin creador.');
});

contractTest('los campos soportan los cuatro tipos y futuras variables', function () use ($upPath): void {
    $table = tableDefinition(readContractFile($upPath), 'evaluacion_campos');

    expectRegex($table, "/tipo_dato\s+ENUM\s*\(\s*'entero'\s*,\s*'decimal'\s*,\s*'duracion'\s*,\s*'texto_corto'\s*\)/i", 'Los tipos de dato no coinciden con el contrato.');
    expectContains($table, 'unidad VARCHAR(50) NULL', 'evaluacion_campos');
    expectContains($table, 'configuracion_json JSON NULL', 'evaluacion_campos');
    expectRegex($table, '/UNIQUE\s+KEY\s+uq_evaluacion_campos_orden\s*\(evaluacion_id,\s*orden\)/i', 'Falta impedir órdenes duplicados dentro de una evaluación.');
});

contractTest('las sesiones separan período y fecha real sin bloquear repeticiones futuras', function () use ($upPath): void {
    $table = tableDefinition(readContractFile($upPath), 'evaluacion_sesiones');

    expectContains($table, 'fecha_realizacion DATE NOT NULL', 'evaluacion_sesiones');
    expectContains($table, 'numero_intento SMALLINT NOT NULL DEFAULT 1', 'evaluacion_sesiones');
    expectRegex($table, "/estado\s+ENUM\s*\(\s*'en_curso'\s*,\s*'finalizada'\s*\)\s+NOT NULL DEFAULT 'en_curso'/i", 'Estados de sesión incorrectos.');
    expectRegex($table, '/UNIQUE\s+KEY\s+uq_evaluacion_sesiones_intento\s*\(evaluacion_id,\s*numero_intento\)/i', 'Falta una clave idempotente por intento.');
    expectRegex($table, '/FOREIGN KEY\s*\(registrada_por_centro_id\)\s*REFERENCES\s+centros\s*\(id\)\s*ON DELETE SET NULL/is', 'Falta auditar el centro que inicia la realización.');
});

contractTest('los resultados distinguen cero, ausencia, texto y valores acotados', function () use ($upPath): void {
    $table = tableDefinition(readContractFile($upPath), 'evaluacion_resultados');

    expectRegex($table, "/estado\s+ENUM\s*\(\s*'sin_evaluar'\s*,\s*'medido'\s*\)\s+NOT NULL DEFAULT 'sin_evaluar'/i", 'Estados de resultado incorrectos.');
    expectContains($table, 'valor_numero DECIMAL(12,3) NULL', 'evaluacion_resultados');
    expectContains($table, 'valor_texto VARCHAR(255) NULL', 'evaluacion_resultados');
    expectContains($table, 'participante_ref VARCHAR(64) NOT NULL', 'evaluacion_resultados');
    expectContains($table, 'participante_nombre VARCHAR(150) NOT NULL', 'evaluacion_resultados');
    expectContains($table, 'participante_apellidos VARCHAR(200) NOT NULL', 'evaluacion_resultados');
    expectRegex($table, "/calificador\s+ENUM\s*\(\s*'exacto'\s*,\s*'mayor_que'\s*,\s*'menor_que'\s*\)/i", 'Faltan calificadores para valores como >30 s.');
    expectRegex($table, '/UNIQUE\s+KEY\s+uq_evaluacion_resultado_participante\s*\(evaluacion_sesion_id,\s*evaluacion_campo_id,\s*participante_ref\)/i', 'Falta la idempotencia por sesión, campo y participante.');
    expectRegex($table, '/FOREIGN KEY\s*\(inscrito_id\)\s*REFERENCES\s+inscritos\s*\(id\)\s*ON DELETE SET NULL/is', 'Borrar un inscrito no debe eliminar su historial de evaluación.');
});

contractTest('la reversión elimina las tablas en orden inverso', function () use ($downPath): void {
    $sql = readContractFile($downPath);
    $tables = ['evaluacion_resultados', 'evaluacion_sesiones', 'evaluacion_campos', 'evaluaciones'];
    $previousPosition = -1;

    foreach ($tables as $table) {
        $needle = 'DROP TABLE IF EXISTS ' . $table;
        $position = strpos($sql, $needle);
        expectTrue($position !== false, 'Falta ' . $needle . '.');
        expectTrue($position > $previousPosition, 'Orden de reversión incorrecto para ' . $table . '.');
        $previousPosition = $position;
    }
});

contractTest('el contrato documenta todas las operaciones del MVP', function () use ($docsPath): void {
    $docs = readContractFile($docsPath);
    $routes = [
        'GET /admin/api/evaluaciones/list_by_activity.php',
        'POST /admin/api/evaluaciones/create.php',
        'POST /admin/api/evaluaciones/update.php',
        'POST /admin/api/evaluaciones/archive.php',
        'POST /admin/api/evaluaciones/reopen.php',
        'POST /admin/api/evaluaciones/update_result.php',
        'GET /api/evaluaciones/listar.php',
        'POST /api/evaluaciones/iniciar.php',
        'GET /api/evaluaciones/detalle.php',
        'POST /api/evaluaciones/guardar_resultado.php',
        'POST /api/evaluaciones/finalizar.php',
    ];

    foreach ($routes as $route) {
        expectContains($docs, $route, 'docs/api/evaluaciones.md');
    }
});

contractTest('el contrato cubre autorización, validación e idempotencia', function () use ($docsPath): void {
    $docs = readContractFile($docsPath);

    foreach ([
        '403 CENTRO_NO_ASIGNADO',
        '409 SESION_YA_FINALIZADA',
        '409 INTENTO_YA_EXISTE',
        '422 FECHA_FUERA_DE_PERIODO',
        '422 FECHA_FUTURA',
        '422 TIPO_DATO_INVALIDO',
        'Idempotencia',
        'una realización por planificación',
    ] as $rule) {
        expectContains($docs, $rule, 'contrato de autorización y validación');
    }
});

$passed = 0;
$failed = 0;

foreach ($tests as [$name, $test]) {
    try {
        $test();
        $passed++;
        fwrite(STDOUT, "[OK] {$name}\n");
    } catch (Throwable $error) {
        $failed++;
        fwrite(STDERR, "[FAIL] {$name}\n       {$error->getMessage()}\n");
    }
}

fwrite(STDOUT, sprintf("\nResultado: %d correctas, %d fallidas.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);
