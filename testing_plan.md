# Plan de Pruebas para la Nueva UI de Horarios de Actividades

## Objetivo

Validar que la implementación de los nuevos selectores de días y horas en los formularios de creación y edición de actividades funciona correctamente y mantiene la compatibilidad con la funcionalidad existente.

## Pruebas Funcionales

### 1. Creación de Actividades con Nuevos Campos

**Caso 1: Crear actividad usando selectores de días y horas**
- Acceder al formulario de creación de actividades
- Completar el nombre de la actividad
- Seleccionar varios días de la semana
- Introducir hora de inicio y hora de fin
- Completar las fechas de inicio y fin
- Guardar la actividad
- Verificar que la actividad se crea correctamente en la base de datos
- Verificar que los campos `dias_semana`, `hora_inicio` y `hora_fin` contienen los valores correctos
- Verificar que el campo `horario` se genera automáticamente con el formato correcto

**Caso 2: Crear actividad usando el modo retrocompatible**
- Acceder al formulario de creación de actividades
- Completar el nombre de la actividad
- Seleccionar el campo de texto para el horario
- Introducir un horario en formato de texto plano
- Completar las fechas de inicio y fin
- Guardar la actividad
- Verificar que la actividad se crea correctamente en la base de datos
- Verificar que solo se usa el campo `horario` existente

### 2. Validaciones de Formulario

**Caso 3: Validación de campos requeridos**
- Acceder al formulario de creación de actividades
- Intentar guardar sin completar campos obligatorios
- Verificar que se muestran mensajes de error apropiados

**Caso 4: Validación de horas**
- Acceder al formulario de creación de actividades
- Seleccionar días de la semana
- Completar solo uno de los campos de hora (inicio o fin)
- Intentar guardar
- Verificar que se muestra un mensaje de error indicando que ambos campos de hora son requeridos

### 3. Interfaz de Usuario

**Caso 5: Visualización de selectores**
- Acceder al formulario de creación de actividades
- Verificar que los selectores de días y horas se muestran correctamente
- Verificar que los estilos CSS se aplican correctamente
- Verificar que la interfaz es responsiva en diferentes tamaños de pantalla

**Caso 6: Comportamiento dinámico**
- Acceder al formulario de creación de actividades
- Seleccionar días de la semana
- Verificar que el campo de texto para horario se oculta automáticamente
- Deseleccionar todos los días
- Verificar que el campo de texto para horario se muestra nuevamente

## Pruebas de Compatibilidad

### 1. Visualización de Actividades Existentes

**Caso 7: Mostrar actividades creadas con el formato antiguo**
- Acceder al listado de actividades
- Verificar que las actividades creadas con el formato de texto plano se muestran correctamente
- Verificar que las actividades creadas con los nuevos campos se muestran correctamente

### 2. Edición de Actividades

**Caso 8: Editar actividad creada con formato antiguo**
- Acceder al formulario de edición de una actividad creada con el formato antiguo
- Verificar que se muestra el campo de texto para horario
- Modificar la actividad usando los nuevos selectores
- Guardar los cambios
- Verificar que la actividad se actualiza correctamente con los nuevos campos

**Caso 9: Editar actividad creada con nuevos campos**
- Acceder al formulario de edición de una actividad creada con los nuevos campos
- Verificar que se muestran los valores correctos en los selectores
- Modificar los días y horas
- Guardar los cambios
- Verificar que la actividad se actualiza correctamente

## Pruebas de Rendimiento

### 1. Tiempo de Carga

**Caso 10: Tiempo de carga del formulario**
- Medir el tiempo de carga del formulario de creación de actividades
- Verificar que no hay degradación significativa en el rendimiento

## Pruebas de Seguridad

### 1. Validación de Entrada

**Caso 11: Validación de caracteres especiales**
- Intentar introducir caracteres especiales o scripts maliciosos en los campos
- Verificar que la entrada se sanitiza correctamente

## Pruebas en Diferentes Entornos

### 1. Navegadores

**Caso 12: Compatibilidad con navegadores**
- Probar la funcionalidad en Chrome, Firefox, Safari y Edge
- Verificar que todos los elementos se muestran y funcionan correctamente

### 2. Dispositivos

**Caso 13: Compatibilidad con dispositivos móviles**
- Probar la funcionalidad en dispositivos móviles
- Verificar que la interfaz es responsiva y usable

## Criterios de Aceptación

1. Todas las actividades creadas con los nuevos campos deben almacenarse correctamente en la base de datos
2. La funcionalidad retrocompatible debe seguir funcionando sin problemas
3. La interfaz debe ser intuitiva y fácil de usar
4. No debe haber degradación significativa en el rendimiento
5. La aplicación debe funcionar correctamente en todos los navegadores soportados
6. La aplicación debe ser responsiva en diferentes dispositivos

## Métricas de Éxito

1. 100% de los casos de prueba funcionales deben pasar
2. 100% de los casos de prueba de compatibilidad deben pasar
3. Tiempo de carga del formulario no debe aumentar más del 10%
4. 100% de los casos de prueba de seguridad deben pasar
5. 100% de los casos de prueba en diferentes entornos deben pasar
