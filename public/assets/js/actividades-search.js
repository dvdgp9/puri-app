// actividades-search.js

// Almacenar los datos originales de las actividades
let actividadesOriginales = {
    activas: [],
    programadas: [],
    finalizadas: []
};

// Inicializar los datos originales cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Almacenar copias de los elementos originales
    document.querySelectorAll('ul.list-container:not(.scheduled-activities):not(.finished-activities) .list-item').forEach(item => {
        actividadesOriginales.activas.push(item.cloneNode(true));
    });
    
    document.querySelectorAll('ul.scheduled-activities .list-item').forEach(item => {
        actividadesOriginales.programadas.push(item.cloneNode(true));
    });
    
    document.querySelectorAll('ul.finished-activities .list-item').forEach(item => {
        actividadesOriginales.finalizadas.push(item.cloneNode(true));
    });
    
    // Aplicar ordenación inicial
    filtrarActividades();
    
    // Configurar event listeners
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    const dateFrom = document.getElementById('start-date-from');
    const dateTo = document.getElementById('start-date-to');
    const dayChips = Array.from(document.querySelectorAll('.chip-day'));
    const resetBtn = document.getElementById('filters-reset');
    
    if (searchInput) {
        searchInput.addEventListener('input', filtrarActividades);
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', filtrarActividades);
    }

    const syncDateHasValue = (el) => {
        if (!el) return;
        if (el.value && el.value.trim() !== '') {
            el.classList.add('has-value');
        } else {
            el.classList.remove('has-value');
        }
    };

    if (dateFrom) {
        syncDateHasValue(dateFrom);
        dateFrom.addEventListener('change', () => { syncDateHasValue(dateFrom); filtrarActividades(); });
        dateFrom.addEventListener('input', () => { syncDateHasValue(dateFrom); });
    }
    if (dateTo) {
        syncDateHasValue(dateTo);
        dateTo.addEventListener('change', () => { syncDateHasValue(dateTo); filtrarActividades(); });
        dateTo.addEventListener('input', () => { syncDateHasValue(dateTo); });
    }
    if (dayChips.length) {
        dayChips.forEach(chip => {
            chip.addEventListener('click', () => {
                const pressed = chip.getAttribute('aria-pressed') === 'true';
                chip.setAttribute('aria-pressed', String(!pressed));
                chip.classList.toggle('active', !pressed);
                filtrarActividades();
            });
        });
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (sortSelect) sortSelect.selectedIndex = 0;
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            syncDateHasValue(dateFrom);
            syncDateHasValue(dateTo);
            dayChips.forEach(chip => {
                chip.setAttribute('aria-pressed', 'false');
                chip.classList.remove('active');
            });
            filtrarActividades();
        });
    }
});

// Función para filtrar actividades
function filtrarActividades() {
    const searchTerm = (document.getElementById('search-input')?.value || '').toLowerCase();
    const sortBy = (document.getElementById('sort-select')?.value) || '';
    const fromVal = document.getElementById('start-date-from')?.value || '';
    const toVal = document.getElementById('start-date-to')?.value || '';
    const selectedDays = Array.from(document.querySelectorAll('.chip-day[aria-pressed="true"]')).map(chip => chip.getAttribute('data-day'));
    
    // Filtrar y ordenar cada categoría usando los datos originales
    Object.keys(actividadesOriginales).forEach(categoria => {
        const actividadesFiltradas = actividadesOriginales[categoria].filter(item => {
            // Texto (nombre)
            const nombre = item.querySelector('.activity-name span')?.textContent.toLowerCase() || '';
            if (searchTerm && !nombre.includes(searchTerm)) return false;

            // Fecha inicio (data-fecha-inicio en formato YYYY-MM-DD)
            const fechaInicio = item.getAttribute('data-fecha-inicio') || '';
            if (fromVal && (!fechaInicio || fechaInicio < fromVal)) return false;
            if (toVal && (!fechaInicio || fechaInicio > toVal)) return false;

            // Días de realización (data-dias: "Lunes,Martes,...") con lógica AND
            if (selectedDays.length) {
                const diasStr = (item.getAttribute('data-dias') || '').trim();
                if (!diasStr) return false;
                const dias = diasStr.split(',').map(d => d.trim());
                // Debe contener TODOS los seleccionados (AND)
                const allMatch = selectedDays.every(d => dias.includes(d));
                if (!allMatch) return false;
            }

            return true;
        });
        
        // Ordenar
        actividadesFiltradas.sort((a, b) => {
            const nombreA = a.querySelector('.activity-name span')?.textContent.toLowerCase() || '';
            const nombreB = b.querySelector('.activity-name span')?.textContent.toLowerCase() || '';
            const fechaA = a.getAttribute('data-fecha-inicio') || '';
            const fechaB = b.getAttribute('data-fecha-inicio') || '';

            if (sortBy === 'nombre-asc') return nombreA.localeCompare(nombreB);
            if (sortBy === 'nombre-desc') return nombreB.localeCompare(nombreA);
            if (sortBy === 'fecha-asc') return fechaA.localeCompare(fechaB);
            if (sortBy === 'fecha-desc') return fechaB.localeCompare(fechaA);
            return 0;
        });
        
        // Actualizar la vista
        actualizarVista(categoria, actividadesFiltradas);
    });
    
    // Mostrar mensaje si no hay resultados
    mostrarMensajeSinResultados();
}

// Función para actualizar la vista de una categoría
function actualizarVista(categoria, actividadesFiltradas) {
    let selector;
    if (categoria === 'activas') {
        selector = 'ul.list-container:not(.scheduled-activities):not(.finished-activities)';
    } else if (categoria === 'programadas') {
        selector = 'ul.scheduled-activities';
    } else {
        selector = 'ul.finished-activities';
    }
    
    const contenedor = document.querySelector(selector);
    
    // Si no se encuentra el contenedor, salir
    if (!contenedor) {
        console.error('No se encontró el contenedor para la categoría:', categoria);
        return;
    }
    
    // Limpiar contenedor
    contenedor.innerHTML = '';
    
    // Añadir actividades filtradas
    if (actividadesFiltradas.length > 0) {
        actividadesFiltradas.forEach(item => {
            // Clonar el elemento para evitar problemas de referencia
            const itemClonado = item.cloneNode(true);
            contenedor.appendChild(itemClonado);
        });
    } else {
        // Mostrar mensaje de no resultados
        const mensaje = document.createElement('li');
        mensaje.className = 'empty-message';
        mensaje.textContent = 'No se encontraron actividades que coincidan con la búsqueda.';
        mensaje.style.listStyle = 'none';
        contenedor.appendChild(mensaje);
    }
}

// Función para mostrar mensaje si no hay resultados en ninguna categoría
function mostrarMensajeSinResultados() {
    const listas = [
        'ul.list-container:not(.scheduled-activities):not(.finished-activities)',
        'ul.scheduled-activities',
        'ul.finished-activities'
    ];
    const todasVacias = listas.every(sel => {
        const cont = document.querySelector(sel);
        if (!cont) return true;
        const hayItems = cont.querySelectorAll('.list-item').length > 0;
        const hayMensaje = !!cont.querySelector('.empty-message');
        return !(hayItems || hayMensaje) || (hayMensaje && !hayItems);
    });

    const mensajeGlobal = document.getElementById('no-results-message');
    const tieneAlgunaEntrada = (
        (document.getElementById('search-input')?.value || '') ||
        (document.getElementById('start-date-from')?.value || '') ||
        (document.getElementById('start-date-to')?.value || '') ||
        Array.from(document.querySelectorAll('.chip-day[aria-pressed="true"]')).length
    );
    mensajeGlobal.style.display = (todasVacias && tieneAlgunaEntrada) ? 'block' : 'none';
}
