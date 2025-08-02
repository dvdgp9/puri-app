# Plan de Implementación de la Nueva UI para Horarios de Actividades

## Resumen

Este documento detalla la implementación propuesta para reemplazar el campo de texto plano de horarios con selectores estructurados en los formularios de creación y edición de actividades. La implementación incluye cambios en la interfaz de usuario, estilos CSS y la forma en que se muestran los horarios en los listados.

## Componentes de la Nueva UI

### 1. Selector de Días de la Semana

**Tipo**: Grupo de checkboxes
**Campos del modelo**: `dias_semana` (SET)

**Características**:
- Permite seleccionar múltiples días
- Diseño en cuadrícula responsive
- Etiquetas claras para cada día
- Feedback visual al pasar el mouse

### 2. Selector de Hora de Inicio

**Tipo**: Input de tipo time
**Campos del modelo**: `hora_inicio` (TIME)

**Características**:
- Control nativo del navegador para selección de hora
- Validación automática de formato
- Diseño consistente con otros inputs del formulario

### 3. Selector de Hora de Fin

**Tipo**: Input de tipo time
**Campos del modelo**: `hora_fin` (TIME)

**Características**:
- Control nativo del navegador para selección de hora
- Validación automática de formato
- Diseño consistente con otros inputs del formulario

## Implementación en Formularios

### Formulario de Creación (`crear_actividad.php`)

**Cambios propuestos**:
1. Reemplazar el campo de texto `horario` con los nuevos selectores
2. Añadir validación para asegurar que se seleccionen días y se introduzcan horas
3. Mantener compatibilidad con el campo `horario` existente

### Formulario de Edición (`editar_actividad.php`)

**Cambios propuestos**:
1. Reemplazar el campo de texto `horario` con los nuevos selectores
2. Precargar los valores existentes de `dias_semana`, `hora_inicio` y `hora_fin`
3. Mantener compatibilidad con el campo `horario` existente

## Cambios en la Visualización

### Listado de Actividades (`actividades.php`)

**Cambios propuestos**:
1. Modificar la visualización del horario para mostrar los días y horas de forma estructurada
2. Usar iconos apropiados para diferenciar días de horas
3. Aplicar estilos diferenciados para el rango horario

## Estilos CSS

Se han definido nuevos estilos para los componentes de la nueva UI:

1. **Estilos para el selector de días**:
   - Diseño en cuadrícula responsive
   - Fondo diferenciado para mejor visibilidad
   - Efectos hover para interacción

2. **Estilos para inputs de hora**:
   - Diseño consistente con otros inputs del formulario
   - Layout flexible para dispositivos móviles

3. **Estilos para visualización de horarios**:
   - Diseño en columnas para mejor legibilidad
   - Colores diferenciados para el rango horario

## Compatibilidad y Migración

### Fase 1: Implementación Paralela
- Mantener el campo `horario` en la base de datos
- Añadir lógica para actualizar ambos formatos (texto y estructurado)
- Mostrar información usando los nuevos campos en la UI

### Fase 2: Validación
- Verificar que todos los datos se muestran correctamente
- Confirmar que la funcionalidad existente no se vea afectada
- Probar en diferentes navegadores y dispositivos

### Fase 3: Eliminación del Campo Legacy
- Una vez validada la nueva UI, eliminar el campo `horario` de la interfaz
- Mantener el campo en la base de datos por si se necesita retroceder
- Planificar eliminación definitiva en una fase posterior

## Archivos Involucrados

1. `crear_actividad.php` - Formulario de creación
2. `editar_actividad.php` - Formulario de edición
3. `actividades.php` - Listado de actividades
4. `public/assets/css/style.css` - Estilos CSS
5. `database_structure_analysis.md` - Documentación de la estructura

## Pruebas Requeridas

1. **Funcionalidad**:
   - Crear actividades con diferentes combinaciones de días
   - Editar actividades existentes
   - Verificar que los datos se guardan correctamente

2. **Visualización**:
   - Verificar la presentación en diferentes tamaños de pantalla
   - Confirmar que los iconos se muestran correctamente
   - Validar el contraste y legibilidad de los textos

3. **Compatibilidad**:
   - Probar en diferentes navegadores (Chrome, Firefox, Safari)
   - Verificar funcionamiento en dispositivos móviles
   - Confirmar retrocompatibilidad con datos existentes

## Siguientes Pasos

1. Implementar los cambios en el formulario de creación
2. Implementar los cambios en el formulario de edición
3. Actualizar la visualización en el listado de actividades
4. Probar la funcionalidad en diferentes escenarios
5. Validar la compatibilidad con datos existentes
