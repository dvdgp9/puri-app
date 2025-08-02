# Propuesta de Cambios en UI para Horarios Estructurados

## Resumen

Esta propuesta detalla los cambios necesarios en la interfaz de usuario para reemplazar el campo de texto libre `horario` con controles estructurados que utilicen los nuevos campos `dias_semana`, `hora_inicio` y `hora_fin` de la tabla `actividades`.

## Cambios Propuestos

### 1. Formulario de Creación de Actividad (`crear_actividad.php`)

Reemplazar el campo de texto libre actual:
```html
<div class="form-group">
    <label for="horario">
        <i class="fas fa-clock"></i> Horario
    </label>
    <input type="text" 
           id="horario" 
           name="horario" 
           required
           placeholder="Ejemplo: Lunes y Miércoles 16:00-17:30"
           value="<?php echo isset($_POST['horario']) ? htmlspecialchars($_POST['horario']) : ''; ?>">
</div>
```

Con controles estructurados:
```html
<!-- Selector de días de la semana -->
<div class="form-group">
    <label>
        <i class="fas fa-calendar-days"></i> Días de la semana
    </label>
    <div class="checkbox-group">
        <label class="checkbox-inline">
            <input type="checkbox" name="dias_semana[]" value="Lunes"> Lunes
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="dias_semana[]" value="Martes"> Martes
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="dias_semana[]" value="Miércoles"> Miércoles
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="dias_semana[]" value="Jueves"> Jueves
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="dias_semana[]" value="Viernes"> Viernes
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="dias_semana[]" value="Sábado"> Sábado
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" name="dias_semana[]" value="Domingo"> Domingo
        </label>
    </div>
</div>

<!-- Selectores de hora de inicio y fin -->
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="hora_inicio">
            <i class="fas fa-clock"></i> Hora de inicio
        </label>
        <input type="time" 
               id="hora_inicio" 
               name="hora_inicio" 
               required
               value="<?php echo isset($_POST['hora_inicio']) ? htmlspecialchars($_POST['hora_inicio']) : ''; ?>">
    </div>
    
    <div class="form-group col-md-6">
        <label for="hora_fin">
            <i class="fas fa-clock"></i> Hora de finalización
        </label>
        <input type="time" 
               id="hora_fin" 
               name="hora_fin" 
               required
               value="<?php echo isset($_POST['hora_fin']) ? htmlspecialchars($_POST['hora_fin']) : ''; ?>">
    </div>
</div>
```

### 2. Formulario de Edición de Actividad (`editar_actividad.php`)

Aplicar los mismos cambios que en el formulario de creación, pero preseleccionando los valores actuales:
```html
<!-- Selector de días de la semana -->
<div class="form-group">
    <label>
        <i class="fas fa-calendar-days"></i> Días de la semana
    </label>
    <div class="checkbox-group">
        <?php 
        // Convertir el valor de dias_semana en un array
        $dias_seleccionados = !empty($actividad['dias_semana']) ? 
                              explode(',', $actividad['dias_semana']) : [];
        $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        
        foreach ($dias_semana as $dia): 
        ?>
            <label class="checkbox-inline">
                <input type="checkbox" 
                       name="dias_semana[]" 
                       value="<?php echo $dia; ?>" 
                       <?php echo in_array($dia, $dias_seleccionados) ? 'checked' : ''; ?>>
                <?php echo $dia; ?>
            </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- Selectores de hora de inicio y fin -->
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="hora_inicio">
            <i class="fas fa-clock"></i> Hora de inicio
        </label>
        <input type="time" 
               id="hora_inicio" 
               name="hora_inicio" 
               required
               value="<?php echo htmlspecialchars($actividad['hora_inicio'] ?? ''); ?>">
    </div>
    
    <div class="form-group col-md-6">
        <label for="hora_fin">
            <i class="fas fa-clock"></i> Hora de finalización
        </label>
        <input type="time" 
               id="hora_fin" 
               name="hora_fin" 
               required
               value="<?php echo htmlspecialchars($actividad['hora_fin'] ?? ''); ?>">
    </div>
</div>
```

### 3. Mostrar Horario en Listados (`actividades.php`)

Actualizar la visualización del horario en los listados para usar los nuevos campos:
```php
<!-- En lugar de: -->
<div class="activity-schedule">
    <i class="fas fa-clock"></i>
    <span><?php echo htmlspecialchars($actividad['horario']); ?></span>
</div>

<!-- Usar: -->
<div class="activity-schedule">
    <i class="fas fa-clock"></i>
    <span>
        <?php 
        echo !empty($actividad['dias_semana']) ? 
             htmlspecialchars($actividad['dias_semana']) : 
             htmlspecialchars($actividad['horario']); 
        ?>
        <?php if (!empty($actividad['hora_inicio']) && !empty($actividad['hora_fin'])): ?>
            <?php echo htmlspecialchars($actividad['hora_inicio']) . ' - ' . htmlspecialchars($actividad['hora_fin']); ?>
        <?php endif; ?>
    </span>
</div>
```

## Cambios en Backend

### 1. Procesamiento en `crear_actividad.php`

Reemplazar:
```php
$horario = filter_input(INPUT_POST, 'horario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// En la inserción
$stmt = $pdo->prepare("INSERT INTO actividades (nombre, horario, instalacion_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)");
$result = $stmt->execute([$nombre, $horario, $instalacion_id, $fecha_inicio, $fecha_fin ?: null]);
```

Con:
```php
// Procesar días de la semana
$dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : '';
$hora_inicio = filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$hora_fin = filter_input(INPUT_POST, 'hora_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Mantener compatibilidad con campo legacy
$horario = '';
if (!empty($dias_semana) && !empty($hora_inicio) && !empty($hora_fin)) {
    $horario = "$dias_semana $hora_inicio-$hora_fin";
}

// En la inserción
$stmt = $pdo->prepare("INSERT INTO actividades (nombre, horario, dias_semana, hora_inicio, hora_fin, instalacion_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$result = $stmt->execute([$nombre, $horario, $dias_semana, $hora_inicio, $hora_fin, $instalacion_id, $fecha_inicio, $fecha_fin ?: null]);
```

### 2. Procesamiento en `editar_actividad.php`

Reemplazar:
```php
$horario = filter_input(INPUT_POST, 'horario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// En la actualización
$stmt = $pdo->prepare("UPDATE actividades SET nombre = ?, horario = ?, instalacion_id = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ?");
$result = $stmt->execute([$nombre, $horario, $instalacion_id, $fecha_inicio, $fecha_fin ?: null, $id]);
```

Con:
```php
// Procesar días de la semana
$dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : '';
$hora_inicio = filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$hora_fin = filter_input(INPUT_POST, 'hora_fin', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Mantener compatibilidad con campo legacy
$horario = '';
if (!empty($dias_semana) && !empty($hora_inicio) && !empty($hora_fin)) {
    $horario = "$dias_semana $hora_inicio-$hora_fin";
}

// En la actualización
$stmt = $pdo->prepare("UPDATE actividades SET nombre = ?, horario = ?, dias_semana = ?, hora_inicio = ?, hora_fin = ?, instalacion_id = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ?");
$result = $stmt->execute([$nombre, $horario, $dias_semana, $hora_inicio, $hora_fin, $instalacion_id, $fecha_inicio, $fecha_fin ?: null, $id]);
```

## Cambios en CSS

Añadir estilos para los nuevos controles en `public/assets/css/style.css`:
```css
/* Estilos para grupos de checkboxes */
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 5px;
}

.checkbox-inline {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: normal;
    cursor: pointer;
}

.checkbox-inline input[type="checkbox"] {
    width: auto;
    margin: 0;
}

/* Estilos para selectores de tiempo */
input[type="time"] {
    padding: 10px;
}

/* Filas de formulario */
.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 1rem;
}

.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .checkbox-group {
        gap: 10px;
    }
}
```

## Consideraciones de Compatibilidad

1. **Mantener campo legacy**: El campo `horario` se mantendrá temporalmente para:
   - Mostrar actividades que aún no han sido actualizadas
   - Proporcionar compatibilidad con otras partes del sistema que puedan usar este campo

2. **Validación**: La validación se realizará tanto en frontend como en backend:
   - Verificar que al menos un día esté seleccionado
   - Verificar que la hora de inicio sea anterior a la hora de fin
   - Mantener compatibilidad con datos existentes

3. **Migración progresiva**: Las actividades existentes ya tienen los datos migrados a los nuevos campos, pero la UI mostrará ambos formatos hasta que se complete la transición.

## Pruebas Sugeridas

1. **Creación de actividad**:
   - Verificar que se pueden seleccionar múltiples días
   - Verificar que se pueden establecer horas de inicio y fin
   - Verificar que se guardan correctamente en la base de datos

2. **Edición de actividad**:
   - Verificar que los días seleccionados se muestran correctamente
   - Verificar que se pueden modificar los días y horas
   - Verificar que los cambios se guardan correctamente

3. **Visualización**:
   - Verificar que los horarios se muestran correctamente en los listados
   - Verificar compatibilidad con actividades que aún usan el formato legacy

4. **Validaciones**:
   - Verificar mensajes de error cuando no se seleccionan días
   - Verificar mensajes de error cuando las horas no son válidas
