<?php
/**
 * Buscador de Participantes
 * Permite buscar participantes por nombre o apellidos dentro de los centros del admin
 */

require_once '../config/config.php';
require_once 'auth_middleware.php';

// Verificar autenticación
$admin_info = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Participantes - Admin Puri</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=GeistSans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .search-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .search-header {
            margin-bottom: 2rem;
        }
        
        .search-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .search-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }
        
        .search-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-input-wrapper {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-btn {
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-btn:hover {
            background: #2563eb;
        }
        
        .results-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .results-header {
            padding: 1rem 1.5rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .results-table th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .results-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }
        
        .results-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .results-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .no-results {
            padding: 3rem;
            text-align: center;
            color: #6b7280;
        }
        
        .loading {
            padding: 3rem;
            text-align: center;
            color: #6b7280;
        }
        
        .activity-link {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .activity-link:hover {
            text-decoration: underline;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-ended {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-scheduled {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo-section">
            <div class="logo">P</div>
            <div class="title">Puri: Gestión de centros deportivos</div>
        </div>
        <div class="actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Volver al Dashboard
            </a>
            <div class="dropdown">
                <button class="btn btn-secondary" id="profile-dropdown-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?php echo htmlspecialchars($admin_info['username']); ?>
                </button>
                <div class="dropdown-content" id="profile-dropdown">
                    <a href="account.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756.426-1.756 2.924 0 3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Mi Cuenta
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="search-page">
        <div class="search-header">
            <h1>Buscar Participantes</h1>
            <p>Busca participantes por nombre o apellidos en todos tus centros</p>
        </div>

        <div class="search-box">
            <div class="search-input-wrapper">
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-input" 
                    placeholder="Introduce nombre o apellidos..."
                    autocomplete="off">
                <button class="search-btn" onclick="performSearch()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="display: inline; vertical-align: middle; margin-right: 0.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Buscar
                </button>
            </div>
        </div>

        <div class="results-container" id="resultsContainer" style="display: none;">
            <div class="results-header" id="resultsHeader">
                Resultados de búsqueda
            </div>
            <div id="resultsContent">
                <!-- Los resultados se cargarán aquí -->
            </div>
        </div>
    </div>

    <script>
        // Dropdown toggle
        document.getElementById('profile-dropdown-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('profile-dropdown').classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profile-dropdown');
            if (!e.target.closest('.dropdown')) {
                dropdown.classList.remove('show');
            }
        });

        // Buscar al presionar Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            
            if (query.length < 2) {
                alert('Por favor introduce al menos 2 caracteres');
                return;
            }

            const resultsContainer = document.getElementById('resultsContainer');
            const resultsContent = document.getElementById('resultsContent');
            const resultsHeader = document.getElementById('resultsHeader');

            // Mostrar loading
            resultsContainer.style.display = 'block';
            resultsContent.innerHTML = '<div class="loading">Buscando...</div>';

            // Realizar búsqueda
            fetch(`api/search/participants.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Error en la búsqueda');
                    }

                    resultsHeader.textContent = `${data.count} resultado${data.count !== 1 ? 's' : ''} encontrado${data.count !== 1 ? 's' : ''}`;

                    if (data.results.length === 0) {
                        resultsContent.innerHTML = '<div class="no-results">No se encontraron participantes con ese nombre o apellidos</div>';
                        return;
                    }

                    // Renderizar tabla de resultados
                    let html = `
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellidos</th>
                                    <th>Actividad</th>
                                    <th>Instalación</th>
                                    <th>Centro</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    data.results.forEach(result => {
                        html += `
                            <tr>
                                <td>${escapeHtml(result.nombre)}</td>
                                <td>${escapeHtml(result.apellidos)}</td>
                                <td>
                                    <a href="activity.php?id=${result.actividad_id}" class="activity-link">
                                        ${escapeHtml(result.actividad_nombre)}
                                    </a>
                                </td>
                                <td>${escapeHtml(result.instalacion_nombre)}</td>
                                <td>${escapeHtml(result.centro_nombre)}</td>
                                <td>${escapeHtml(result.fecha_inicio_formatted)}</td>
                                <td>${escapeHtml(result.fecha_fin_formatted)}</td>
                            </tr>
                        `;
                    });

                    html += `
                            </tbody>
                        </table>
                    `;

                    resultsContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsContent.innerHTML = '<div class="no-results">Error al realizar la búsqueda. Por favor intenta de nuevo.</div>';
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
