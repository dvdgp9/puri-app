// instalaciones-search.js

// Almacenar los datos originales de las instalaciones
let instalacionesOriginales = [];

// Inicializar los datos originales cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Almacenar copias de los elementos originales
    document.querySelectorAll('ul.list-container .list-item').forEach(item => {
        instalacionesOriginales.push(item.cloneNode(true));
    });
    
    // Aplicar ordenación inicial
    filtrarInstalaciones();
    
    // Configurar event listeners
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    
    if (searchInput) {
        searchInput.addEventListener('input', filtrarInstalaciones);
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', filtrarInstalaciones);
    }
});

// Función para filtrar instalaciones
function filtrarInstalaciones() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const sortBy = document.getElementById('sort-select').value;
    
    // Filtrar y ordenar instalaciones usando los datos originales
    const instalacionesFiltradas = instalacionesOriginales.filter(item => {
        const nombre = item.querySelector('.item-title').textContent.toLowerCase();
        return nombre.includes(searchTerm);
    });
    
    // Ordenar
    instalacionesFiltradas.sort((a, b) => {
        const nombreA = a.querySelector('.item-title').textContent.toLowerCase();
        const nombreB = b.querySelector('.item-title').textContent.toLowerCase();
        
        if (sortBy === 'nombre-asc') {
            return nombreA.localeCompare(nombreB);
        } else {
            return nombreB.localeCompare(nombreA);
        }
    });
    
    // Actualizar la vista
    actualizarVista(instalacionesFiltradas);
    
    // Mostrar mensaje si no hay resultados
    mostrarMensajeSinResultados();
}

// Función para actualizar la vista
function actualizarVista(instalacionesFiltradas) {
    const contenedor = document.querySelector('ul.list-container');
    
    // Si no se encuentra el contenedor, salir
    if (!contenedor) {
        console.error('No se encontró el contenedor de instalaciones');
        return;
    }
    
    // Limpiar contenedor
    contenedor.innerHTML = '';
    
    // Añadir instalaciones filtradas
    if (instalacionesFiltradas.length > 0) {
        instalacionesFiltradas.forEach(item => {
            // Clonar el elemento para evitar problemas de referencia
            const itemClonado = item.cloneNode(true);
            contenedor.appendChild(itemClonado);
        });
    } else {
        // Mostrar mensaje de no resultados
        const mensaje = document.createElement('li');
        mensaje.className = 'empty-message';
        mensaje.textContent = 'No se encontraron instalaciones que coincidan con la búsqueda.';
        mensaje.style.listStyle = 'none';
        contenedor.appendChild(mensaje);
    }
}

// Función para mostrar mensaje si no hay resultados
function mostrarMensajeSinResultados() {
    const hayResultados = document.querySelectorAll('ul.list-container .list-item').length > 0;
    const mensajeGlobal = document.getElementById('no-results-message');
    
    if (!hayResultados && document.getElementById('search-input').value) {
        mensajeGlobal.style.display = 'block';
    } else {
        mensajeGlobal.style.display = 'none';
    }
}
