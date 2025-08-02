# Propuesta de Diseño para Selector de Horarios en Actividades

## Objetivo
Reemplazar el campo de texto plano para horarios con selectores estructurados que permitan una mejor gestión, filtrado y visualización de los horarios de las actividades.

## Elementos de UI Propuestos

### 1. Selector de Días de la Semana
**Tipo de control**: Grupo de checkboxes
**Justificación**: Permite seleccionar múltiples días de forma intuitiva

**Opciones**:
- Lunes
- Martes
- Miércoles
- Jueves
- Viernes
- Sábado
- Domingo

**Diseño visual**:
```
[ ] Lunes  [ ] Martes  [ ] Miércoles  [ ] Jueves
[ ] Viernes  [ ] Sábado  [ ] Domingo
```

### 2. Selector de Hora de Inicio
**Tipo de control**: Input de tipo time
**Justificación**: Permite seleccionar horas y minutos de forma precisa

**Diseño visual**:
```
Hora de inicio: [10:00]
```

### 3. Selector de Hora de Fin
**Tipo de control**: Input de tipo time
**Justificación**: Permite seleccionar horas y minutos de forma precisa

**Diseño visual**:
```
Hora de fin: [11:30]
```

## Implementación en Formularios

### Formulario de Creación de Actividad
Los nuevos campos se integrarán en el formulario existente, reemplazando el campo de texto plano.

**Sección actual**:
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

**Sección propuesta**:
```html
<div class="form-group">
    <label>
        <i class="fas fa-calendar-days"></i> Días de la semana
    </label>
    <div class="days-selector">
        <label><input type="checkbox" name="dias_semana[]" value="Lunes"> Lunes</label>
        <label><input type="checkbox" name="dias_semana[]" value="Martes"> Martes</label>
        <label><input type="checkbox" name="dias_semana[]" value="Miércoles"> Miércoles</label>
        <label><input type="checkbox" name="dias_semana[]" value="Jueves"> Jueves</label>
        <label><input type="checkbox" name="dias_semana[]" value="Viernes"> Viernes</label>
        <label><input type="checkbox" name="dias_semana[]" value="Sábado"> Sábado</label>
        <label><input type="checkbox" name="dias_semana[]" value="Domingo"> Domingo</label>
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="hora_inicio">
            <i class="fas fa-hourglass-start"></i> Hora de inicio
        </label>
        <input type="time" 
               id="hora_inicio" 
               name="hora_inicio"
               value="<?php echo isset($_POST['hora_inicio']) ? htmlspecialchars($_POST['hora_inicio']) : ''; ?>">
    </div>

    <div class="form-group">
        <label for="hora_fin">
            <i class="fas fa-hourglass-end"></i> Hora de fin
        </label>
        <input type="time" 
               id="hora_fin" 
               name="hora_fin"
               value="<?php echo isset($_POST['hora_fin']) ? htmlspecialchars($_POST['hora_fin']) : ''; ?>">
    </div>
</div>
```

### Formulario de Edición de Actividad
La estructura será similar al formulario de creación, pero con los valores pre-cargados desde la base de datos.

## Consideraciones de Estilo CSS

### Estilos para el Selector de Días
```css
.days-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.days-selector label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.days-selector label:hover {
    background-color: #e9ecef;
}

.days-selector input[type="checkbox"] {
    transform: scale(1.2);
}
```

### Estilos para Inputs de Hora
```css
.form-row {
    display: flex;
    gap: 20px;
}

.form-row .form-group {
    flex: 1;
}

input[type="time"] {
    padding: 12px;
    border: 2px solid #e1e1e1;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

input[type="time"]:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(35, 170, 197, 0.1);
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
}
```

## Visualización en Listados

### Tarjeta de Actividad
En los listados de actividades, se mostrará la información de horario de forma estructurada:

**Actual**:
```html
<div class="activity-schedule">
    <i class="fas fa-clock"></i>
    <span><?php echo htmlspecialchars($actividad['horario']); ?></span>
</div>
```

**Propuesto**:
```html
<div class="activity-schedule">
    <i class="fas fa-calendar-days"></i>
    <span><?php echo implode(', ', explode(',', $actividad['dias_semana'])); ?></span>
    <span class="time-range"><?php echo date('H:i', strtotime($actividad['hora_inicio'])) . ' - ' . date('H:i', strtotime($actividad['hora_fin'])); ?></span>
</div>
```

### Estilos para Visualización
```css
.activity-schedule {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.activity-schedule .time-range {
    font-weight: 500;
    color: var(--primary-color);
}
```

## Compatibilidad

Durante la transición, se mantendrá el campo `horario` para:
1. Mostrar información en la UI existente
2. Asegurar compatibilidad con funcionalidades existentes
3. Facilitar la migración gradual

Una vez que la nueva UI esté completamente implementada y validada, se podrá eliminar el campo `horario` de la interfaz.
