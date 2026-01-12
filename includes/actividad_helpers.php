<?php
/**
 * Helper functions para actividades
 */

/**
 * Formatea el nombre de la actividad con el grupo si existe
 * @param string $nombre Nombre de la actividad
 * @param string|null $grupo Grupo de la actividad
 * @return string Nombre formateado
 */
function formatearNombreActividad($nombre, $grupo = null) {
    $nombre_html = htmlspecialchars(html_entity_decode($nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    
    if (!empty($grupo)) {
        $grupo_html = htmlspecialchars($grupo);
        return $nombre_html . ' (' . $grupo_html . ')';
    }
    
    return $nombre_html;
}
?>
