<?php
/**
 * info.php - Logic Only (PREPARED FOR OBFUSCATION)
 * Styles loaded from templates/classic/css/info.css
 * Updated: Character Images & Version Filter
 * FORMAT: Pure PHP Wrapper
 */

// --- 1. DETECTAR VERSIÓN DEL SERVIDOR (AdminCP) ---
$confFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$ver = 'Season 6'; // Default
if(file_exists($confFile)) {
    $c = json_decode(file_get_contents($confFile), true);
    if(isset($c['server_version'])) $ver = $c['server_version'];
}

// Determinar límite de razas según versión
// Índices: 0:DW, 1:DK, 2:ELF, 3:MG, 4:DL, 5:SU, 6:RF
$maxClassIndex = 6; // S6 = Todos
if($ver == '0.97' || $ver == '0.99') {
    $maxClassIndex = 2; // Hasta ELF
} elseif(strpos($ver, 'Season 1')!==false || strpos($ver, 'Season 2')!==false || strpos($ver, 'Season 3')!==false) {
    $maxClassIndex = 4; // Hasta DL
} elseif(strpos($ver, 'Season 4')!==false || strpos($ver, 'Season 5')!==false) {
    $maxClassIndex = 5; // Hasta SUM
}

// Preparar datos para el HTML
$serverName = custom_config("server_name");
$baseUrl = __BASE_URL__;

// --- GENERAR ITEMS DEL SIDEBAR ---
$menuItems = '';
$dataFile = __PATH_INCLUDES__ . 'config/server_info.json';
if(file_exists($dataFile)) {
    $customInfo = json_decode(file_get_contents($dataFile), true);
    if(is_array($customInfo)) {
        foreach($customInfo as $category => $content) {
            if($category == 'general') continue;
            
            // Solo mostrar si tiene la llave 'data' y NO está vacía
            if(!isset($content['data']) || empty($content['data'])) continue;

            $icon = isset($content['config']['icon']) ? $content['config']['icon'] : 'fas fa-chevron-right';
            $menuItems .= '<li onclick="loadInfo(\''.$category.'\', this)"><i class="'.$icon.'"></i> '.ucfirst($category).'</li>';
        }
    }
}

// --- IMPRIMIR TODO EL CONTENIDO (HEREDOC) ---
// Nota: Las variables de JS dentro de backticks `${}` están escapadas como `\${}` para que PHP no las toque.

echo <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="rank-header text-center mb-5">
    <h1 class="rank-title">INFORMACIÓN</h1>
    <p class="rank-subtitle">Descubre las características de {$serverName}</p>
</div>

<div class="info-container">
    <div class="info-sidebar">
        <ul class="info-menu">
            <li class="active" onclick="loadInfo('general', this)">
                <i class="fas fa-info-circle"></i> General
            </li>
            {$menuItems}
            <li onclick="loadInfo('stats', this)"><i class="fas fa-chart-pie"></i> Estadísticas</li>
            <li onclick="loadInfo('invasions', this)"><i class="fas fa-skull"></i> Invasiones</li>
        </ul>
    </div>
    
    <div id="info-load-area" class="info-content"></div>
</div>

<script>
// Variables de Configuración (Inyectadas desde PHP)
const maxClassLimit = {$maxClassIndex}; // Límite de array
const charLabels = ['DW', 'DK', 'ELF', 'MG', 'DL', 'SU', 'RF'];
const charColors = ['#3498db', '#e74c3c', '#2ecc71', '#9b59b6', '#f1c40f', '#e67e22', '#bdc3c7'];
const charImages = ['dw.jpg', 'dk.jpg', 'elf.jpg', 'mg.jpg', 'dl.jpg', 'su.jpg', 'rf.jpg'];

// Función Maestra para el Gráfico
function createRaceChart(dataValues) {
    const canvas = document.getElementById('charsChart');
    if(!canvas) return;

    const oldChart = Chart.getChart(canvas);
    if(oldChart) oldChart.destroy();

    // FILTRADO: Cortamos los arrays según la versión del servidor
    // slice(0, N+1) porque el segundo parámetro es exclusivo
    const fLabels = charLabels.slice(0, maxClassLimit + 1);
    const fData   = dataValues.slice(0, maxClassLimit + 1);
    const fColors = charColors.slice(0, maxClassLimit + 1);

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: fLabels,
            datasets: [{
                data: fData,
                backgroundColor: fColors,
                hoverOffset: 25, 
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            layout: {
                padding: 20
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    titleFont: { size: 14, weight: 'bold', family: 'Cinzel' },
                    bodyFont: { size: 13, family: 'Poppins' },
                    padding: 12,
                    displayColors: true,
                    borderColor: '#FF8A3B',
                    borderWidth: 1
                }
            }
        }
    });
}

// Función para reemplazar los avatares de texto por imágenes reales
function renderVisualStats(counts) {
    // 1. Buscamos el contenedor donde el sistema pegó los stats "feos"
    const area = document.getElementById('info-load-area');
    const allDivs = area.querySelectorAll('div');
    let targetContainer = null;
    
    // Heurística: Buscamos el div que contiene "DW" y "DK" pero NO es el gráfico
    for(let div of allDivs) {
        if(div.innerText.includes('DW') && div.innerText.includes('DK') && !div.querySelector('canvas')) {
            targetContainer = div;
            break;
        }
    }

    if(!targetContainer) return; // Si no lo encuentra, deja el original

    // 2. Construimos el nuevo HTML con imágenes
    let html = '<div style="display:flex; justify-content:center; flex-wrap:wrap; gap:25px; margin-top:40px;">';
    
    for(let i=0; i <= maxClassLimit; i++) {
        let count = counts[i] || 0;
        let imgPath = '{$baseUrl}img/character-avatars/' + charImages[i];
        
        html += `
            <div style="text-align:center; display:flex; flex-direction:column; align-items:center;">
                <div style="width:65px; height:65px; border-radius:50%; border:3px solid \${charColors[i]}; overflow:hidden; margin-bottom:10px; background:#000; box-shadow: 0 0 15px rgba(0,0,0,0.5);">
                    <img src="\${imgPath}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='{$baseUrl}img/character-avatars/default.jpg'">
                </div>
                <div style="font-weight:700; color:\${charColors[i]}; font-family:'Poppins'; font-size:14px; letter-spacing:1px;">\${charLabels[i]}</div>
                <div style="color:#fff; font-size:1.2rem; font-weight:bold; margin-top:2px;">\${count}</div>
            </div>
        `;
    }
    html += '</div>';
    
    // 3. Reemplazamos el contenido viejo
    targetContainer.innerHTML = html;
}

function loadInfo(type, element) {
    const area = document.getElementById('info-load-area');
    document.querySelectorAll('.info-menu li').forEach(li => li.classList.remove('active'));
    if(element) element.classList.add('active');
    
    // Loader con estilo
    area.innerHTML = '<div style="text-align:center; padding-top:150px;"><i class="fas fa-circle-notch fa-spin fa-3x" style="color:#FF8A3B;"></i></div>';

    fetch('api/info_processor.php?type=' + type)
        .then(response => response.text())
        .then(html => {
            area.innerHTML = html;
            
            // Caso 1: Si es Estadísticas (Gráfico y Avatares)
            if(type === 'stats') {
                const dataRaw = document.getElementById('raw-stats-data');
                if(dataRaw) {
                    const cleanData = JSON.parse(dataRaw.value);
                    createRaceChart(cleanData); // Dibuja el gráfico filtrado
                    renderVisualStats(cleanData); // Dibuja los avatares con imágenes
                }
            }

            // Caso 2: Si es Invasiones
            if(type === 'invasions') {
                const scripts = area.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    newScript.text = oldScript.text;
                    document.body.appendChild(newScript).parentNode.removeChild(newScript);
                });
            }
        });
}

// Cargar 'general' al inicio por defecto
document.addEventListener('DOMContentLoaded', function() {
    // Busca el primer elemento 'li' y simula click
    const firstLi = document.querySelector('.info-menu li');
    if(firstLi) loadInfo('general', firstLi);
});
</script>
HTML;
?>