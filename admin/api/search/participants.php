<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';

    $admin_info = getAdminInfo();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if ($search_query === '') {
        echo json_encode(['success' => true, 'results' => []]);
        exit;
    }

    // Preparar patrones de búsqueda para soportar registros con '*'
    $search_like = '%' . $search_query . '%';
    $search_like_star = '%' . str_replace(' ', '*', $search_query) . '%';

    // Obtener centros a los que el admin tiene acceso
    if ($admin_info['role'] === 'superadmin') {
        // Superadmin puede buscar en todos los centros
        $sql_search = "
            SELECT 
                i.id as inscrito_id,
                i.nombre,
                i.apellidos,
                a.id as actividad_id,
                a.nombre as actividad_nombre,
                inst.id as instalacion_id,
                inst.nombre as instalacion_nombre,
                c.id as centro_id,
                c.nombre as centro_nombre,
                a.fecha_inicio,
                a.fecha_fin
            FROM inscritos i
            INNER JOIN actividades a ON i.actividad_id = a.id
            INNER JOIN instalaciones inst ON a.instalacion_id = inst.id
            INNER JOIN centros c ON inst.centro_id = c.id
            WHERE (
                i.nombre LIKE ?
                OR REPLACE(i.nombre, '*', ' ') LIKE ?
                OR i.nombre LIKE ?
                OR i.apellidos LIKE ?
                OR REPLACE(i.apellidos, '*', ' ') LIKE ?
                OR i.apellidos LIKE ?
                OR CONCAT(i.nombre, ' ', i.apellidos) LIKE ?
                OR CONCAT(i.nombre, ' ', REPLACE(i.apellidos, '*', ' ')) LIKE ?
                OR CONCAT(REPLACE(i.nombre, '*', ' '), ' ', REPLACE(i.apellidos, '*', ' ')) LIKE ?
                OR CONCAT(i.apellidos, ' ', i.nombre) LIKE ?
                OR CONCAT(REPLACE(i.apellidos, '*', ' '), ' ', i.nombre) LIKE ?
                OR CONCAT(REPLACE(i.apellidos, '*', ' '), ' ', REPLACE(i.nombre, '*', ' ')) LIKE ?
            )
            ORDER BY i.apellidos ASC, i.nombre ASC
            LIMIT 100
        ";
        
        $params = [
            $search_like,
            $search_like,
            $search_like_star,
            $search_like,
            $search_like,
            $search_like_star,
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $search_like,
        ];

        $stmt = $pdo->prepare($sql_search);
        $stmt->execute($params);
        
    } else {
        // Admin regular: solo buscar en sus centros asignados
        $stmt_centers = $pdo->prepare('SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?');
        $stmt_centers->execute([$admin_info['id']]);
        $center_ids = $stmt_centers->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($center_ids)) {
            echo json_encode(['success' => true, 'results' => []]);
            exit;
        }
        
        $placeholders = implode(',', array_fill(0, count($center_ids), '?'));
        $sql_search = "
            SELECT 
                i.id as inscrito_id,
                i.nombre,
                i.apellidos,
                a.id as actividad_id,
                a.nombre as actividad_nombre,
                inst.id as instalacion_id,
                inst.nombre as instalacion_nombre,
                c.id as centro_id,
                c.nombre as centro_nombre,
                a.fecha_inicio,
                a.fecha_fin
            FROM inscritos i
            INNER JOIN actividades a ON i.actividad_id = a.id
            INNER JOIN instalaciones inst ON a.instalacion_id = inst.id
            INNER JOIN centros c ON inst.centro_id = c.id
            WHERE c.id IN ($placeholders)
              AND (
                  i.nombre LIKE ?
                  OR REPLACE(i.nombre, '*', ' ') LIKE ?
                  OR i.nombre LIKE ?
                  OR i.apellidos LIKE ?
                  OR REPLACE(i.apellidos, '*', ' ') LIKE ?
                  OR i.apellidos LIKE ?
                  OR CONCAT(i.nombre, ' ', i.apellidos) LIKE ?
                  OR CONCAT(i.nombre, ' ', REPLACE(i.apellidos, '*', ' ')) LIKE ?
                  OR CONCAT(REPLACE(i.nombre, '*', ' '), ' ', REPLACE(i.apellidos, '*', ' ')) LIKE ?
                  OR CONCAT(i.apellidos, ' ', i.nombre) LIKE ?
                  OR CONCAT(REPLACE(i.apellidos, '*', ' '), ' ', i.nombre) LIKE ?
                  OR CONCAT(REPLACE(i.apellidos, '*', ' '), ' ', REPLACE(i.nombre, '*', ' ')) LIKE ?
              )
            ORDER BY i.apellidos ASC, i.nombre ASC
            LIMIT 100
        ";
        
        $params = array_merge(
            $center_ids,
            [
                $search_like,
                $search_like,
                $search_like_star,
                $search_like,
                $search_like,
                $search_like_star,
                $search_like,
                $search_like,
                $search_like,
                $search_like,
                $search_like,
                $search_like,
            ]
        );

        $stmt = $pdo->prepare($sql_search);
        $stmt->execute($params);
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas
    foreach ($results as &$result) {
        if ($result['fecha_inicio']) {
            $result['fecha_inicio_formatted'] = date('d/m/Y', strtotime($result['fecha_inicio']));
        } else {
            $result['fecha_inicio_formatted'] = '-';
        }
        
        if ($result['fecha_fin']) {
            $result['fecha_fin_formatted'] = date('d/m/Y', strtotime($result['fecha_fin']));
        } else {
            $result['fecha_fin_formatted'] = '-';
        }
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log('Error in search/participants.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
