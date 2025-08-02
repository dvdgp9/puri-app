<?php
require_once 'config/config.php';

try {
    // Crear la tabla de observaciones si no existe
    $sql = "
    CREATE TABLE IF NOT EXISTS observaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        actividad_id INT NOT NULL,
        fecha DATE NOT NULL,
        observacion TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE,
        UNIQUE KEY (actividad_id, fecha)
    )";
    
    $pdo->exec($sql);
    echo "La tabla 'observaciones' ha sido creada correctamente o ya existía.";
    
} catch (PDOException $e) {
    die("Error al crear la tabla: " . $e->getMessage());
}
?> 