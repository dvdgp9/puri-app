<?php
require_once 'config/config.php';

// Verificar autenticación
if (!isset($_SESSION['centro_id'])) {
    die(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Verificar que se recibió el archivo y el ID de actividad
if (!isset($_FILES['excel']) || !isset($_POST['actividad_id'])) {
    die(json_encode(['success' => false, 'message' => 'Faltan datos requeridos']));
}

try {
    $actividad_id = $_POST['actividad_id'];
    
    // Verificar que el archivo se subió correctamente
    if ($_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        $error_message = '';
        switch ($_FILES['excel']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "El archivo subido excede la directiva upload_max_filesize en php.ini.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "El archivo subido excede la directiva MAX_FILE_SIZE especificada en el formulario HTML.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "El archivo subido fue sólo parcialmente subido.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "Ningún archivo fue subido.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Falta la carpeta temporal.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "Error al escribir el archivo en el disco.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "Una extensión de PHP detuvo la subida del archivo.";
                break;
            default:
                $error_message = "Error desconocido en la subida del archivo: " . $_FILES['excel']['error'];
                break;
        }
        throw new Exception($error_message);
    }

    // Verificar que el archivo existe
    if (!file_exists($_FILES['excel']['tmp_name'])) {
        throw new Exception('No se pudo encontrar el archivo temporal');
    }

    // Verificar extensión del archivo
    $extension = strtolower(pathinfo($_FILES['excel']['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        throw new Exception('El archivo debe ser un archivo CSV (.csv)');
    }

    // Leer el contenido del archivo para determinar el delimitador y la codificación
    $content = file_get_contents($_FILES['excel']['tmp_name']);
    
    // Detectar BOM UTF-8 y removerlo si existe
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    
    // Intentar convertir a UTF-8 si es necesario, asumiendo ISO-8859-1 si no se puede detectar
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
    }
    
    // Determinar el delimitador
    $delimiter = (strpos($content, ';') !== false) ? ';' : ',';

    // Abrir el archivo CSV con la codificación correcta
    $handle = null;
    try {
        $handle = fopen('php://memory', 'w+');
        if ($handle === false) {
            throw new Exception('No se pudo crear el stream de memoria');
        }
        
        // Escribir el contenido en el stream de memoria
        $bytes_written = fwrite($handle, $content);
        if ($bytes_written === false) {
            throw new Exception('No se pudo escribir el contenido en memoria');
        }
        
        // Volver al inicio del stream
        if (rewind($handle) === false) {
            throw new Exception('No se pudo reiniciar el puntero del archivo');
        }

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
                // Verificamos si los primeros dos campos coinciden con los esperados
                if (count($data) < 2 || $data[0] !== $encabezados_esperados[0] || $data[1] !== $encabezados_esperados[1]) {
                    throw new Exception('El formato del archivo no es correcto. Los encabezados deben ser: ' . implode(', ', $encabezados_esperados));
                }
                continue;
            }

            // Verificar que tenemos todos los datos necesarios
            if (count($data) < 2) {
                $errors[] = "Línea $lineNumber: no tiene todas las columnas requeridas (encontradas: " . count($data) . ")";
                continue;
            }

            // Verificar que la línea tiene datos
            if (!empty($data[0])) {
                $total_registros++;
                
                $stmt->execute([
                    $actividad_id,
                    trim($data[0]), // Nombre (columna A)
                    trim($data[1])  // Apellido (columna B)
                ]);
                $rowCount++;
            }
        }
        
        if ($total_registros === 0) {
            throw new Exception("El archivo no contiene registros para importar o todas las filas están vacías. Líneas totales leídas: $lineNumber");
        }

        // Preparar mensaje de respuesta
        $mensaje = [];
        
        // Si hay errores, consideramos la operación como parcialmente exitosa o fallida
        $success = true;
        if (!empty($errors)) {
            $success = false;
        }

        if ($rowCount > 0) {
            $mensaje[] = "Se importaron $rowCount de $total_registros registros.";
        } else {
            $mensaje[] = "No se importó ningún registro de los $total_registros encontrados.";
        }
        
        if (!empty($errors)) {
            $mensaje[] = "\nErrores encontrados:";
            $mensaje = array_merge($mensaje, $errors);
        }

        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode([
            'success' => $success,
            'message' => implode("\n", $mensaje),
            'imported' => $rowCount,
            'total' => $total_registros,
            'errors' => count($errors)
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'file' => $_FILES['excel']['name'],
            'type' => $_FILES['excel']['type']
        ]);
    } finally {
        // Cerrar el handle del archivo si está abierto
        if ($handle !== null && is_resource($handle)) {
            fclose($handle);
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $_FILES['excel']['name'],
        'type' => $_FILES['excel']['type']
    ]);
} 