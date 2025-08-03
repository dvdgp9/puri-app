// actividades-search.js

// Función para filtrar actividades
function filtrarActividades() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const sortBy = document.getElementById('sort-select').value;
    
    // Obtener todas las actividades
    const actividades = {
        activas: Array.from(document.querySelectorAll('.active-activities .list-item')),
        programadas: Array.from(document.querySelectorAll('.scheduled-activities .list-item')),
        finalizadas: Array.from(document.querySelectorAll('.finished-activities .list-item'))
    };
    
    // Filtrar y ordenar cada categoría
    Object.keys(actividades).forEach(categoria => {
        const actividadesFiltradas = actividades[categoria].filter(item => {
            const nombre = item.querySelector('.activity-name span').textContent.toLowerCase();
            return nombre.includes(searchTerm);
        });
        
        // Ordenar
        actividadesFiltradas.sort((a, b) => {
            const nombreA = a.querySelector('.activity-name span').textContent.toLowerCase();
            const nombreB = b.querySelector('.activity-name span').textContent.toLowerCase();
            
            if (sortBy === 'nombre-asc') {
                return nombreA.localeCompare(nombreB);
            } else {
                return nombreB.localeCompare(nombreA);
            }
        });
        
        // Actualizar la vista
        actualizarVista(categoria, actividadesFiltradas);
    });
    
    // Mostrar mensaje si no hay resultados
    mostrarMensajeSinResultados();
}

// Función para actualizar la vista de una categoría
function actualizarVista(categoria, actividadesFiltradas) {
    const contenedor = document.querySelector(`.${categoria === 'activas' ? 'active' : categoria === 'programadas' ? 'scheduled' : 'finished'}-activities .list-container`);
    
    // Limpiar contenedor
    contenedor.innerHTML = '';
    
    // Añadir actividades filtradas
    if (actividadesFiltradas.length > 0) {
        actividadesFiltradas.forEach(item => {
            contenedor.appendChild(item);
        });
    } else {
        // Mostrar mensaje de no resultados
        const mensaje = document.createElement('p');
        mensaje.className = 'empty-message';
        mensaje.textContent = 'No se encontraron actividades que coincidan con la búsqueda.';
        contenedor.appendChild(mensaje);
    }
}

// Función para mostrar mensaje si no hay resultados en ninguna categoría
function mostrarMensajeSinResultados() {
    const todasVacias = [
        document.querySelectorAll('.active-activities .list-item').length === 0 && 
        !document.querySelector('.active-activities .list-container').querySelector('.empty-message'),
        document.querySelectorAll('.scheduled-activities .list-item').length === 0 && 
        !document.querySelector('.scheduled-activities .list-container').querySelector('.empty-message'),
        document.querySelectorAll('.finished-activities .list-item').length === 0 && 
        !document.querySelector('.finished-activities .list-container').querySelector('.empty-message')
    ].every(vacio => vacio);
    
    const mensajeGlobal = document.getElementById('no-results-message');
    
    if (todasVacias && document.getElementById('search-input').value) {
        mensajeGlobal.style.display = 'block';
    } else {
        mensajeGlobal.style.display = 'none';
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    
    if (searchInput) {
        searchInput.addEventListener('input', filtrarActividades);
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', filtrarActividades);
    }
});
