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

    // Comenzar transacción
    $pdo->beginTransaction();
    
    // Preparar la consulta de inserción
    $stmt = $pdo->prepare("INSERT INTO inscritos (actividad_id, nombre, apellidos) VALUES (?, ?, ?)");
    
    $rowCount = 0;
    $errors = [];
    $lineNumber = 0;
    $total_registros = 0;

    // Leer el archivo CSV línea por línea
    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        $lineNumber++;
        
        // Verificar los encabezados en la primera línea
        if ($lineNumber === 1) {
            $encabezados_esperados = ['Nombre', 'Apellidos'];
            if (count($data) < 2 || $data[0] !== $encabezados_esperados[0] || $data[1] !== $encabezados_esperados[1]) {
                throw new Exception('El formato del archivo no es correcto. Los encabezados deben ser: ' . implode(', ', $encabezados_esperados));
            }
            continue;
        }

        // Verificar que tenemos todos los datos necesarios
        if (count($data) < 2) {
            $errors[] = "Línea $lineNumber: no tiene todas las columnas requeridas";
            continue;
        }

        // Verificar que la línea tiene datos
        if (!empty(trim($data[0])) && !empty(trim($data[1]))) {
            $total_registros++;
            
            $nombre = trim($data[0]);
            $apellidos = trim($data[1]);
            
            // Verificar duplicados
            $checkStmt = $pdo->prepare("SELECT id FROM inscritos WHERE actividad_id = ? AND nombre = ? AND apellidos = ?");
            $checkStmt->execute([$actividad_id, $nombre, $apellidos]);
            
            if (!$checkStmt->fetch()) {
                $stmt->execute([$actividad_id, $nombre, $apellidos]);
                $rowCount++;
            } else {
                $errors[] = "Línea $lineNumber: $nombre $apellidos ya está inscrito";
            }
        }
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
