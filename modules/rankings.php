<?php
/**
 * Rankings Module - Main Controller & Standard UI
 * File: modules/rankings.php
 * Updated: Version Filtering (0.97 - S6) & ELO Table Logic
 * ADAPTADO PARA OFUSCACIÓN TOTAL (HTML convertido a PHP)
 */
if(!defined('access') or !access) die();

loadModuleConfigs('rankings'); 
$rankings_config = mconfig(); 
$db = Connection::Database('Me_MuOnline');

// --- LÓGICA DE VERSIÓN (FILTRO GLOBAL) ---
$ver = custom_config('server_version');
$maxClassAllowed = 999; 

if ($ver == "0.97" || $ver == "0.99") {
    $maxClassAllowed = 47;
}
elseif (strpos($ver, 'Season 1') !== false || strpos($ver, 'Season 2') !== false || strpos($ver, 'Season 3') !== false) {
    $maxClassAllowed = 79;
}
elseif (strpos($ver, 'Season 4') !== false || strpos($ver, 'Season 5') !== false) {
    $maxClassAllowed = 95;
}

// 1. DETECCIÓN DE PÁGINA DE BOSSES
$isBossPage = (isset($_GET['op']) && $_GET['op'] == 'bosses') || 
              (isset($_GET['action']) && $_GET['action'] == 'bosses') ||
              (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/rankings/bosses') !== false);

if ($isBossPage) {
    include(__DIR__ . '/rankings/bosses.php');
    return;
}

// 2. RUTEO AJAX
if(isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    if (ob_get_length()) ob_clean();
    
    $type = isset($_GET['type']) ? $_GET['type'] : 'general';
    $max_limit = 50;
    
    $classFilter = "";
    if(isset($_GET['class']) && $_GET['class'] !== 'all' && is_numeric($_GET['class'])) {
        $baseClass = (int)$_GET['class'];
        $classFilter = "AND Class >= $baseClass AND Class <= " . ($baseClass + 15);
    }

    if($maxClassAllowed < 999) {
        $classFilter .= " AND Class <= $maxClassAllowed";
    }

    $exclude_clause = "";
    if(!empty($rankings_config['exclude_list'])) {
        $excluded_names = array();
        foreach(explode(',', (string)$rankings_config['exclude_list']) as $__n) {
            $__n = trim($__n);
            if($__n === '') continue;
            $excluded_names[] = str_replace("'", "''", $__n);
        }
        unset($__n);
        if(!empty($excluded_names)) {
            $exclude_clause = "AND Name NOT IN ('" . implode("','", $excluded_names) . "')";
        }
    }

    $basePath = __DIR__ . '/rankings/'; 
    
    if (strpos($type, 'bosses_') === 0) {
        include($basePath . 'bosses.php');
        die();
    }

    switch($type) {
        case 'guilds': include($basePath . 'guilds.php'); break;
        case 'killers': include($basePath . 'killers.php'); break;
        case 'bc':
        case 'ds':
        case 'cc': include($basePath . 'events.php'); break;
        case 'gens': include($basePath . 'gens.php'); break;
        case 'duels': include($basePath . 'duels.php'); break;
        case 'elo': include($basePath . 'elo.php'); break;
        default: include($basePath . 'general.php'); break;
    }
    die();
}

// 3. INTERFAZ DE USUARIO (CONVERTIDA A PHP PARA PODER ENCRIPTARSE)
// Preparamos variables para insertar en el HTML
$serverName = custom_config("server_name");
$baseUrl = __BASE_URL__;

// Botones condicionales
$btnGuilds = $rankings_config['active_rank_guilds'] ? '<a href="javascript:void(0);" onclick="loadRank(\'guilds\', this)" class="rank-nav-btn"><i class="fa fa-shield-halved"></i> Guilds</a>' : '';
$btnKillers = $rankings_config['active_rank_killers'] ? '<a href="javascript:void(0);" onclick="loadRank(\'killers\', this)" class="rank-nav-btn"><i class="fa fa-skull"></i> Killers</a>' : '';

$btnEvents = '';
if($rankings_config['active_rank_events']) {
    $btnEvents .= '<a href="javascript:void(0);" onclick="loadRank(\'bc\', this)" class="rank-nav-btn"><i class="fa fa-ghost"></i> BC</a>';
    $btnEvents .= '<a href="javascript:void(0);" onclick="loadRank(\'ds\', this)" class="rank-nav-btn"><i class="fa fa-mask"></i> DS</a>';
    $btnEvents .= '<a href="javascript:void(0);" onclick="loadRank(\'cc\', this)" class="rank-nav-btn"><i class="fa fa-crown"></i> CC</a>';
}

$btnGens = $rankings_config['active_rank_gens'] ? '<a href="javascript:void(0);" onclick="loadRank(\'gens\', this)" class="rank-nav-btn"><i class="fa fa-flag"></i> Gens</a>' : '';
$btnDuels = $rankings_config['active_rank_duels'] ? '<a href="javascript:void(0);" onclick="loadRank(\'duels\', this)" class="rank-nav-btn"><i class="fa-solid fa-shield"></i> Duels</a>' : '';
$btnElo = (isset($rankings_config['active_rank_elo']) && $rankings_config['active_rank_elo']) ? '<a href="javascript:void(0);" onclick="loadRank(\'elo\', this)" class="rank-nav-btn"><i class="fa-solid fa-chart-line"></i> ELO Ranking</a>' : '';
$btnBosses = (isset($rankings_config['active_rank_bosses']) && $rankings_config['active_rank_bosses']) ? '<a href="'.$baseUrl.'rankings/bosses" class="rank-nav-btn" style="border-color: #f39c12; color: #f39c12;"><i class="fa fa-dragon"></i> Bosses</a>' : '';

// Renderizamos el Header y Navegación
echo <<<HTML
<div class="rankings-wrapper">
    <div class="rank-header text-center mb-5">
        <h1 class="rank-title">RANKINGS</h1>
        <p class="rank-subtitle">Descubre quiénes son los mejores jugadores de {$serverName}</p>
    </div>

    <div class="rank-nav-container d-flex justify-content-center flex-wrap gap-2 mb-5">
        <a href="javascript:void(0);" onclick="loadRank('general', this)" class="rank-nav-btn active"><i class="fa fa-trophy"></i> General</a>
        {$btnGuilds}
        {$btnKillers}
        {$btnEvents}
        {$btnGens}
        {$btnDuels}
        {$btnElo}
        {$btnBosses}
    </div>

    <div class="rank-filter-box mb-5">
        <div class="d-flex justify-content-center flex-wrap gap-4">
            <div class="class-filter-item active" onclick="filterClass('all', this)" style="cursor:pointer;">
                <div class="class-avatar-circle all-classes"><span>ALL</span></div>
            </div>
HTML;

// Renderizamos el Loop de Clases
$classes = [0 => 'dw', 16 => 'dk', 32 => 'elf', 48 => 'mg', 64 => 'dl', 80 => 'sum', 96 => 'rf'];
foreach($classes as $code => $name) {
    if($code > $maxClassAllowed) continue;
    $uName = strtoupper($name);
    $imgUrl = $baseUrl . 'img/character-avatars/' . $name . '.jpg';
    $errUrl = $baseUrl . 'img/character-avatars/avatar.jpg';
    
    echo <<<HTML
            <div class="class-filter-item" onclick="filterClass({$code}, this)" style="cursor:pointer;" title="{$uName}">
                <div class="class-avatar-circle">
                    <img src="{$imgUrl}" onerror="this.src='{$errUrl}'" alt="{$name}">
                </div>
            </div>
HTML;
}

// Variables JS pre-procesadas
$jsShowResets = $rankings_config['show_resets'] ? 'true' : 'false';
$jsShowMResets = $rankings_config['show_master_resets'] ? 'true' : 'false';
$jsShowMLevel = $rankings_config['show_master_level'] ? 'true' : 'false';
$jsShowStatus = $rankings_config['show_status'] ? 'true' : 'false';
$jsShowGuild = $rankings_config['show_guild'] ? 'true' : 'false';

// Renderizamos el final de la tabla y los Scripts
echo <<<HTML
        </div>
    </div>

    <div class="rank-table-card" style="position: relative; min-height: 400px;">
        <table class="table table-dark custom-balduneta-table mb-0">
            <thead id="rank-header"></thead>
            <tbody id="rank-content"></tbody>
        </table>
        <div id="rank-loader" style="display:none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 10; align-items: center; justify-content: center; border-radius: 15px;">
            <div class="spinner-border text-orange" role="status"></div>
        </div>
    </div>
</div>

<style>
    .rank-avatar-img { width: 32px; height: 32px; border-radius: 50%; border: 1px solid rgba(200, 161, 101, 0.3); object-fit: cover; margin: 0 auto; display: block; }
</style>

<script>
let currentRankType = 'general';
let currentClass = 'all';

const cfg = {
    showResets: {$jsShowResets},
    showMasterResets: {$jsShowMResets},
    showMasterLevel: {$jsShowMLevel},
    showStatus: {$jsShowStatus},
    showGuild: {$jsShowGuild}
};

function updateTableHeader(type) {
    const header = document.getElementById('rank-header');
    if(!header) return;
    
    let html = '<tr class="text-uppercase" style="font-size: 0.8rem; color: #8a8f9d; border-bottom: 2px solid #222;">';
    
    if (type !== 'elo') {
        html += '<th style="width: 60px; text-align: center;">Pos</th>';
    }
    
    if(type === 'guilds') {
        html += '<th>Guild</th><th>Logo</th><th>Master</th><th>Puntos</th><th>Miembros</th>';
        
    } else if(['bc', 'ds', 'cc'].includes(type)) {
        html += '<th>Jugador</th>';
        html += '<th style="text-align: center;">Clase</th>';
        html += '<th style="text-align: center;">Score</th>';
        html += '<th style="text-align: center;">Entradas Hoy</th>';
        
    } else if(type === 'duels') {
        html += '<th>Jugador</th>';
        html += '<th style="text-align: center;">Avatar</th>';
        html += '<th style="text-align: center;">DG</th>'; 
        html += '<th style="text-align: center;">DP</th>'; 
        html += '<th style="text-align: center;">Score</th>';
    
    } else if(type === 'elo') {
        html += '<th style="text-align: center;">Rango</th>'; 
        html += '<th>Jugador</th>';
        html += '<th style="text-align: center;">Clase</th>';
        html += '<th style="text-align: center;">ELO</th>';

    } else {
        html += '<th>Jugador</th>';
        if(cfg.showMasterResets && type === 'general') html += '<th style="text-align: center;">M. Resets</th>';
        if(cfg.showResets && type === 'general') html += '<th style="text-align: center;">Resets</th>';
        if(type === 'gens') html += '<th style="text-align: center;">Puntos</th>';
        if(type === 'killers') html += '<th style="text-align: center;">Kills</th>';
        
        html += '<th style="text-align: center;">Nivel</th>';
        if(cfg.showMasterLevel && type === 'general') html += '<th style="text-align: center;">M. Level</th>';
        html += '<th style="text-align: center;">Clase</th>';
        
        if(cfg.showStatus) html += '<th style="text-align: center;">Status</th>';
        if(cfg.showGuild)  html += '<th style="text-align: center;">Guild</th>';
    }
    
    html += '</tr>';
    header.innerHTML = html;
}

function filterClass(classCode, element) {
    if(!element || ['guilds', 'bc', 'ds', 'cc', 'gens', 'duels', 'elo'].includes(currentRankType)) return;
    currentClass = classCode;
    document.querySelectorAll('.class-filter-item').forEach(i => i.classList.remove('active'));
    element.classList.add('active');
    loadRank(currentRankType, null, currentClass);
}

let rankRequestId = 0;

function loadRank(type, element, classCode = currentClass) {
    if(element) {
        document.querySelectorAll('.rank-nav-btn').forEach(btn => btn.classList.remove('active'));
        element.classList.add('active');
        currentRankType = type;
    }
    updateTableHeader(type);
    
    const filterBox = document.querySelector('.rank-filter-box');
    if(filterBox) filterBox.style.display = (['guilds', 'bc', 'ds', 'cc', 'gens', 'duels', 'elo'].includes(type)) ? 'none' : 'block';
    
    const content = document.getElementById('rank-content');
    const loader = document.getElementById('rank-loader');

    // Al cambiar de tipo de ranking cambian las columnas: se vacia la tabla
    // para no mostrar el encabezado nuevo sobre las filas del ranking anterior.
    if(content && element) content.innerHTML = '';
    if(content) content.style.opacity = '0.3';
    if(loader) loader.style.display = 'flex'; 

    // Si se piden dos rankings seguidos, solo se muestra la ultima respuesta
    const requestId = ++rankRequestId;

    fetch('{$baseUrl}rankings/?ajax=1&type=' + type + '&class=' + classCode)
        .then(response => response.text())
        .then(data => {
            if(requestId !== rankRequestId) return;
            if(content) {
                content.innerHTML = data.trim(); 
                content.style.opacity = '1';
            }
            if(loader) loader.style.display = 'none';
        })
        .catch(err => {
            if(requestId !== rankRequestId) return;
            console.error(err);
            if(content) content.style.opacity = '1';
            if(loader) loader.style.display = 'none';
        });
}

document.addEventListener('DOMContentLoaded', () => { loadRank('general'); });
</script>
HTML;
?>