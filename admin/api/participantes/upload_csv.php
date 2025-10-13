<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración y autenticación
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    
    // Verificar autenticación de admin
    $admin_info = getAdminInfo();

    // Solo aceptar POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Verificar que se recibió el archivo y el ID de actividad
    if (!isset($_FILES['csv']) || !isset($_POST['actividad_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
        exit;
    }

    $actividad_id = intval($_POST['actividad_id']);

    // Verificar que la actividad existe
    $stmt = $pdo->prepare("SELECT id FROM actividades WHERE id = ?");
    $stmt->execute([$actividad_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }
    
    // Autorización: si no es superadmin, validar que la actividad pertenezca a un centro asignado
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM actividades a
             INNER JOIN instalaciones i ON a.instalacion_id = i.id
             INNER JOIN admin_asignaciones aa ON aa.centro_id = i.centro_id
             WHERE a.id = ? AND aa.admin_id = ?"
        );
        $stmt->execute([$actividad_id, $admin_info['id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para esta actividad']);
            exit;
        }
    }

    // Verificar que el archivo se subió correctamente
    if ($_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $error_message = '';
        switch ($_FILES['csv']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "El archivo excede el tamaño máximo permitido";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "No se seleccionó ningún archivo";
                break;
            default:
                $error_message = "Error al subir el archivo";
                break;
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }

    // Verificar que el archivo existe
    if (!file_exists($_FILES['csv']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo encontrar el archivo temporal']);
        exit;
    }

    // Verificar extensión del archivo
    $extension = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El archivo debe ser un archivo CSV (.csv)']);
        exit;
    }

    // Leer el contenido del archivo
    $content = file_get_contents($_FILES['csv']['tmp_name']);
    
    // Detectar BOM UTF-8 y removerlo si existe
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    
    // Convertir a UTF-8 si es necesario
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
    }
    
    // Determinar el delimitador
    $delimiter = (strpos($content, ';') !== false) ? ';' : ',';

    // Procesar CSV
    $handle = fopen('php://memory', 'w+');
    fwrite($handle, $content);
    rewind($handle);

    // Modo de importación: append (por defecto) o replace
    $import_mode = isset($_POST['import_mode']) && $_POST['import_mode'] === 'replace' ? 'replace' : 'append';

    // Helpers para normalizar encabezados
    $normalize = function($s) {
        $s = trim((string)$s);
        if ($s === '') return '';
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return $s;
    };

    $mapHeader = function($raw) use ($normalize) {
        $n = $normalize($raw);
        // Posibles alias
        $nombreAliases = ['nombre', 'name', 'first name', 'first', 'given name'];
        $apellidosAliases = ['apellidos', 'apellido', 'last name', 'last', 'surname', 'family name'];
        if (in_array($n, $nombreAliases, true)) return 'nombre';
        if (in_array($n, $apellidosAliases, true)) return 'apellidos';
        return null; // columna ignorada
    };

    // Comenzar transacción
    $pdo->beginTransaction();

    // Si es reemplazo, borrar primero asistencias e inscritos de la actividad
    if ($import_mode === 'replace') {
        $stmtDelA = $pdo->prepare('DELETE FROM asistencias WHERE actividad_id = ?');
        $stmtDelA->execute([$actividad_id]);
        $stmtDelI = $pdo->prepare('DELETE FROM inscritos WHERE actividad_id = ?');
        $stmtDelI->execute([$actividad_id]);
    }

    // Preparar la inserción
    $stmt = $pdo->prepare("INSERT INTO inscritos (actividad_id, nombre, apellidos) VALUES (?, ?, ?)");

    $rowCount = 0;
    $errors = [];
    $lineNumber = 0;
    $total_registros = 0;
    $nombreIdx = null; $apellidosIdx = null;

    // Leer el archivo CSV línea por línea
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        $lineNumber++;
        // Encabezados (primera fila): detectar posiciones de nombre y apellidos de forma flexible
        if ($lineNumber === 1) {
            if (!$data || count($data) < 2) {
                throw new Exception('El archivo CSV debe contener al menos las columnas Nombre y Apellidos en la primera fila.');
            }
            foreach ($data as $idx => $h) {
                $mapped = $mapHeader((string)$h);
                if ($mapped === 'nombre' && $nombreIdx === null) $nombreIdx = $idx;
                if ($mapped === 'apellidos' && $apellidosIdx === null) $apellidosIdx = $idx;
            }
            if ($nombreIdx === null || $apellidosIdx === null) {
                throw new Exception('No se han encontrado las columnas requeridas (Nombre y Apellidos) en la cabecera.');
            }
            continue;
        }

        // Verificar que tenemos todos los datos necesarios
        if (!is_array($data) || count($data) <= max($nombreIdx, $apellidosIdx)) {
            $errors[] = "Línea $lineNumber: no tiene todas las columnas requeridas";
            continue;
        }

        // Verificar que la línea tiene datos (nombre y apellidos no vacíos)
        $nombre = isset($data[$nombreIdx]) ? trim($data[$nombreIdx]) : '';
        $apellidos = isset($data[$apellidosIdx]) ? trim($data[$apellidosIdx]) : '';
        if ($nombre === '' && $apellidos === '') { continue; } // saltar filas vacías
        if ($nombre === '' || $apellidos === '') {
            $errors[] = "Línea $lineNumber: faltan Nombre o Apellidos";
            continue;
        }
        $total_registros++;
        // Insertar sin verificación de duplicados (según decisión actual)
        $stmt->execute([$actividad_id, $nombre, $apellidos]);
        $rowCount++;
    }
    
    fclose($handle);
    
    if ($total_registros === 0) {
        throw new Exception("El archivo no contiene registros válidos para importar");
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    $message = "Importación completada: $rowCount participantes inscritos";
    if (!empty($errors)) {
        $message .= ". Errores: " . implode(', ', array_slice($errors, 0, 3));
        if (count($errors) > 3) {
            $message .= " y " . (count($errors) - 3) . " más";
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'imported' => $rowCount,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Error uploading CSV participantes: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
