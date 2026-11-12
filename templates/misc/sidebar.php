<?php
// 1. CARGA DE DATOS (PHP)
$eventsFilePath = __PATH_INCLUDES__ . 'config/server_events.json';
$customSettingsPath = __PATH_INCLUDES__ . 'config/custom_settings.json';
$bannedFilePath = __PATH_INCLUDES__ . 'config/banned_list.json'; // Archivo de bans

$sidebarEvents = [];
$bannedList = []; // Lista vacía por defecto
$bannedActive = false;

// Cargar Eventos
if (file_exists($eventsFilePath)) {
    $jsonContent = file_get_contents($eventsFilePath);
    $sidebarEvents = json_decode($jsonContent, true);
    if (!is_array($sidebarEvents)) { $sidebarEvents = []; }
}

// Cargar Configuración Custom
if (file_exists($customSettingsPath)) {
    $cSet = json_decode(file_get_contents($customSettingsPath), true);
    $bannedActive = (isset($cSet['module_banned_active']) && $cSet['module_banned_active'] == 'on');
}

// Cargar Sancionados (Solo si el modulo está activo)
if ($bannedActive && file_exists($bannedFilePath)) {
    $jsonBans = file_get_contents($bannedFilePath);
    $bannedList = json_decode($jsonBans, true);
    if (!is_array($bannedList)) { $bannedList = []; }
}

@include_once __DIR__ . '/inc/site_counts.php';
try {
    $msmCounts = function_exists('msm_cached_counts')
        ? msm_cached_counts()
        : array('accounts' => 0, 'chars' => 0, 'guilds' => 0);
    $stats_accounts   = ['result' => $msmCounts['accounts']];
    $stats_characters = ['result' => $msmCounts['chars']];
    $stats_guilds     = ['result' => $msmCounts['guilds']];    
    $sidebarOnline = isset($displayOnline) ? $displayOnline : 0;
    $sidebarRecord = isset($maxOnline) ? $maxOnline : 0;
    $sidebarVersion = (function_exists('custom_config')) ? custom_config('server_version') : 'N/A';
} catch (Exception $e) {
    $sidebarOnline = $sidebarRecord = 0; $sidebarVersion = "Error";
}
$checkLogin = (isset($_SESSION['username']) && !empty($_SESSION['username'])) ? 1 : 0;
?>

<div id="global-sidebar" class="sidebar-closed">
    <div id="sidebar-toggle"></div>

    <div class="sidebar-bubbles">
        <div class="stat-bubble" onclick="openModal('stats')" title="Estadísticas"><i class="fa fa-chart-bar"></i></div>
        <div class="stat-bubble" onclick="openModal('events')" title="Eventos"><i class="fa fa-calendar-alt"></i></div>
        <div class="stat-bubble" onclick="openModal('economy')" title="Economía"><i class="fa fa-gem"></i></div>
        
        <?php if($bannedActive): ?>
            <div class="stat-bubble" onclick="openModal('banned')" title="Sancionados" style="background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #e74c3c;">
                <i class="fa fa-ban"></i>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="global-modal-overlay" onclick="closeModal()">
    <div class="stat-modal-content" onclick="event.stopPropagation()">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div id="modal-dynamic-content"></div>
    </div>
</div>

<script>
let countdownInterval;

function getNextOccurrence(timesString) {
    if(!timesString) return null;
    const now = new Date();
    const times = timesString.split(',').map(t => t.trim());
    let nextEvent = null;
    let minDiff = Infinity;
    times.forEach(time => {
        const parts = time.split(':');
        if(parts.length !== 2) return;
        let eventDate = new Date(now);
        eventDate.setHours(parseInt(parts[0]), parseInt(parts[1]), 0, 0);
        if (eventDate <= now) eventDate.setDate(eventDate.getDate() + 1);
        let diff = eventDate - now;
        if (diff < minDiff) { minDiff = diff; nextEvent = eventDate; }
    });
    return nextEvent;
}
function updateCountdowns() {
    const timers = document.querySelectorAll('.event-timer');
    const now = new Date();
    timers.forEach(timer => {
        const targetTime = new Date(parseInt(timer.dataset.target));
        const diff = targetTime - now;
        if (diff <= 0) {
            timer.innerHTML = "<span style='color:#28a745; font-weight:bold;'>¡EN CURSO!</span>";
        } else {
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            timer.innerHTML = `${h > 0 ? h + 'h ' : ''}${m}m ${s}s`;
        }
    });
}
function filterEvents(category) {
    const containers = document.querySelectorAll('.event-category-container');
    const tabs = document.querySelectorAll('.event-tab');
    tabs.forEach(tab => { tab.style.background = 'rgba(255,255,255,0.1)'; tab.style.color = '#fff'; });
    const activeTab = document.getElementById('tab-' + category);
    if(activeTab) { activeTab.style.background = '#FF8A3B'; activeTab.style.color = '#000'; }
    containers.forEach(container => {
        container.style.opacity = '0';
        setTimeout(() => {
            container.style.display = 'none';
            if (container.id === 'cat-' + category) {
                container.style.display = 'block';
                setTimeout(() => { container.style.opacity = '1'; }, 50);
            }
        }, 200);
    });
}

function openModal(type) {
    const overlay = document.getElementById('global-modal-overlay');
    const content = document.getElementById('modal-dynamic-content');
    const isLogged = <?php echo (int)$checkLogin; ?>; 

    clearInterval(countdownInterval);
    overlay.style.setProperty('display', 'flex', 'important');
    content.innerHTML = ''; 

    let html = '';
    
    if(type === 'stats') {
        html = `
            <h2 class="text-orange text-center text-uppercase mb-4">Estadísticas</h2>
            <div class="stat-row"><span>Season:</span> <span class="text-white"><?php echo $sidebarVersion; ?></span></div>
            <div class="stat-row"><span>Online:</span> <span class="text-success fw-bold"><?php echo $sidebarOnline; ?></span></div>
            <div class="stat-row"><span>Cuentas:</span> <span class="text-white"><?php echo number_format($stats_accounts['result']); ?></span></div>
            <div class="stat-row"><span>Personajes:</span> <span class="text-white"><?php echo number_format($stats_characters['result']); ?></span></div>
        `;
    } 
    else if(type === 'events') {
        const eventsList = <?php echo json_encode($sidebarEvents); ?>;
        html = `
            <h2 class="text-orange text-center text-uppercase mb-3">Cronograma</h2>
            <div style="display: flex; gap: 5px; margin-bottom: 20px;">
                <div id="tab-Daily" class="event-tab" onclick="filterEvents('Daily')" style="flex:1; text-align:center; padding:8px; cursor:pointer; border-radius:4px; font-weight:bold; font-size:11px; transition: 0.3s; background:#FF8A3B; color:#000;">DAILY</div>
                <div id="tab-Weekly" class="event-tab" onclick="filterEvents('Weekly')" style="flex:1; text-align:center; padding:8px; cursor:pointer; border-radius:4px; font-weight:bold; font-size:11px; transition: 0.3s; background:rgba(255,255,255,0.1); color:#fff;">WEEKLY</div>
                <div id="tab-Special" class="event-tab" onclick="filterEvents('Special')" style="flex:1; text-align:center; padding:8px; cursor:pointer; border-radius:4px; font-weight:bold; font-size:11px; transition: 0.3s; background:rgba(255,255,255,0.1); color:#fff;">SPECIAL</div>
            </div>
        `;
        ['Daily', 'Weekly', 'Special'].forEach(cat => {
            html += `<div id="cat-${cat}" class="event-category-container" style="display: ${cat === 'Daily' ? 'block' : 'none'}; opacity: 1; transition: opacity 0.3s ease-in-out; max-height: 350px; overflow-y: auto;">`;
            let filtered = Object.values(eventsList).filter(e => e.type === cat);
            if (filtered.length === 0) {
                html += `<p class="text-white text-center opacity-50">No hay eventos ${cat}.</p>`;
            } else {
                filtered.forEach(event => {
                    let color = cat === 'Daily' ? '#28a745' : (cat === 'Weekly' ? '#007bff' : '#dc3545');
                    let nextDate = (cat === 'Daily' || cat === 'Special') ? getNextOccurrence(event.times) : null;
                    html += `
                        <div style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid ${color};">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong style="color: #eee; text-transform: uppercase; font-size: 13px;">${event.name}</strong>
                                ${nextDate ? `<span class="event-timer" data-target="${nextDate.getTime()}" style="color: #FF8A3B; font-weight: bold; font-size:12px;">--:--</span>` : `<span style="color:#888; font-size:11px;">${event.day_start} ${event.time_start}</span>`}
                            </div>
                        </div>`;
                });
            }
            html += `</div>`;
        });
    } 
    else if(type === 'economy') {
        html = '<h2 class="text-orange text-center text-uppercase mb-4">Economía</h2><p class="text-white text-center">Analizando mercado...</p>';
    }
    else if(type === 'banned') {
        // --- MODAL DE SANCIONADOS (DINÁMICO) ---
        const bans = <?php echo json_encode(array_values($bannedList)); ?>;
        
        html = `<h2 class="text-danger text-center text-uppercase mb-4">Sancionados</h2>`;
        
        if(bans.length > 0) {
            html += `
            <div class="table-responsive">
                <table class="table table-dark table-sm" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th class="text-white">Personaje</th>
                            <th class="text-white">Razón</th>
                            <th class="text-white text-end">Estado</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            bans.forEach(ban => {
                let badge = '';
                if(ban.type === 'permanent') {
                    badge = '<span class="badge bg-danger">PERMANENTE</span>';
                } else {
                    // Calculo simple de estado para temporal
                    badge = `<span class="badge bg-warning text-dark">${ban.days} DÍAS</span>`;
                }
                
                html += `
                        <tr>
                            <td class="text-danger fw-bold">${ban.name}</td>
                            <td class="text-white">${ban.reason}</td>
                            <td class="text-end">${badge}</td>
                        </tr>`;
            });

            html += `</tbody></table></div>`;
        } else {
            html += `<p class="text-center text-white p-4">No hay personajes sancionados actualmente.</p>`;
        }
        
        html += `<p class="text-center text-muted small mt-3">Mantengamos la comunidad limpia.</p>`;
    }

    // --- BOTONES DINÁMICOS ---
    html += `<hr class="border-secondary border-opacity-25 my-4">`;

    if(type === 'events') {
        html += `<a href="<?php echo __BASE_URL__; ?>info" class="btn btn-outline-orange w-100 text-uppercase fw-bold py-2">Ver cronograma completo</a>`;
    } else if(type !== 'banned') {
        html += `<a href="<?php echo __BASE_URL__; ?>info" class="btn btn-outline-orange w-100 text-uppercase fw-bold py-2">Ver más info</a>`;
    }

    if(isLogged === 0) {
        html += `
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <div style="flex: 1;"><a href="<?php echo __BASE_URL__; ?>register" class="btn btn-orange w-100 text-uppercase fw-bold small py-2">Registrate</a></div>
                <div style="flex: 1;"><a href="<?php echo __BASE_URL__; ?>login" class="btn btn-dark border-secondary w-100 text-uppercase fw-bold small py-2">Iniciá Sesión</a></div>
            </div>
        `;
    }

    content.innerHTML = html;
    
    if(type === 'events') {
        updateCountdowns();
        countdownInterval = setInterval(updateCountdowns, 1000);
    }
}

function closeModal() {
    clearInterval(countdownInterval);
    document.getElementById('global-modal-overlay').style.setProperty('display', 'none', 'important');
}

(function() {
    const toggle = document.getElementById('sidebar-toggle');
    if(toggle) {
        toggle.onclick = function() {
            const sidebar = document.getElementById('global-sidebar');
            sidebar.classList.toggle('sidebar-open');
            sidebar.classList.toggle('sidebar-closed');
        };
    }
})();
</script>