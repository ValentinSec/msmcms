<?php
/**
 * Player Profile Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Logic & Render Unified)
 */

if(!defined('access') or !access) die();

// --- 1. CONFIGURACIÓN Y VERIFICACIÓN ---
$configPath = __PATH_CONFIGS__ . 'muservermaker.json';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/config/muservermaker.json';
}

$configData = json_decode(file_get_contents($configPath), true);
$is_profile_enabled = (isset($configData['player_profiles']) && $configData['player_profiles'] == true);

if (!$is_profile_enabled) {
    echo '<div class="alert-disabled-container p-5 text-center"><div class="profile-card d-inline-block p-5" style="background: rgba(10,10,12,0.9); border: 2px solid #ff6600; border-radius: 15px;"><h2 class="text-white text-uppercase fw-bold">Módulo no habilitado</h2></div></div>';
    return;
}

$myCharCfgPath = defined('__PATH_CONFIGS__') ? __PATH_CONFIGS__ . 'mychar_config.json' : $_SERVER['DOCUMENT_ROOT'] . '/includes/config/mychar_config.json';
$myCharCfg = file_exists($myCharCfgPath) ? json_decode(file_get_contents($myCharCfgPath), true) : array();

$showStats = (!isset($myCharCfg['show_stats']) || $myCharCfg['show_stats'] == 1);
$showInventory = (!isset($myCharCfg['show_inventory']) || $myCharCfg['show_inventory'] == 1);
$showPvPLogs = (!isset($myCharCfg['show_pvp_logs']) || $myCharCfg['show_pvp_logs'] == 1);

if (!function_exists('tiempo_transcurrido')) {
    function tiempo_transcurrido($fecha) {
        $timestamp = strtotime($fecha);
        $diferencia = time() - $timestamp;
        if ($diferencia < 60) return "Hace un momento";
        $minutos = floor($diferencia / 60);
        if ($minutos < 60) return "Hace " . $minutos . " minuto(s)";
        $horas = floor($minutos / 60);
        if ($horas < 24) return "Hace " . $horas . " hora(s)";
        $dias = floor($horas / 24);
        return "Hace " . $dias . " día(s)";
    }
}

// --- 2. CAPTURA DEL NOMBRE ---
$charName = '';
if(isset($_GET['name']) && !empty($_GET['name'])) {
    $charName = $_GET['name'];
} elseif(isset($_GET['id']) && !empty($_GET['id'])) {
    $charName = $_GET['id'];
} else {
    $pathParts = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $charName = end($pathParts);
}
$charName = trim(strip_tags($charName));

if (empty($charName) || $charName == 'player' || $charName == 'profile') {
    echo '<div class="text-white text-center p-5"><h4 class="text-orange trajan-title">POR FAVOR, SELECCIONA UN PERSONAJE</h4></div>';
    return;
}

// --- 3. CONEXIÓN Y DATOS ---
try {
    $targetDB = isset($configData['SQL_DB_NAME']) ? $configData['SQL_DB_NAME'] : 'MuOnline';
    $db = Connection::Database('MuOnline');

    $charData = $db->query_fetch_single("
    SELECT 
        c.Name, c.Class, c.cLevel, c.Strength, c.Dexterity, c.Vitality, c.Energy, c.Leadership, 
        CAST(c.Inventory AS VARBINARY(MAX)) as Inventory,
        g.G_Name as GuildName,
        g.G_Mark as GuildMark
    FROM Character c
    LEFT JOIN GuildMember gm ON c.Name = gm.Name
    LEFT JOIN Guild g ON gm.G_Name = g.G_Name
    WHERE c.Name = ?", array($charName));

    if(!$charData) {
        echo "<div class='text-white text-center p-5'>EL PERSONAJE [".$charName."] NO EXISTE.</div>";
        return;
    }

    // PvP Elo
    $charElo = 0;
    try {
        $pvpData = $db->query_fetch_single("SELECT Elo FROM [{$targetDB}].[dbo].[MUSERVERMAKER_PVP_SYSTEM] WHERE Name = ?", array($charName));
        if($pvpData) { $charElo = $pvpData['Elo']; }
    } catch (Exception $ex) { $charElo = 0; }

    // Logs
    $killLogs = array();
    try {
        $sqlLogs = $db->query_fetch("SELECT TOP 10 Killer, Victim, LogDate, Map FROM [{$targetDB}].[dbo].[MUSERVERMAKER_PVP_LOGS] WHERE Killer = ? OR Victim = ? ORDER BY LogDate DESC", array($charName, $charName));
        if($sqlLogs && is_array($sqlLogs)) {
            foreach($sqlLogs as $log) {
                $haceCuanto = tiempo_transcurrido($log['LogDate']);
                $mapName = !empty($log['Map']) ? $log['Map'] : 'Desconocido';
                $killer = str_replace(array('[', ']'), '', $log['Killer']);
                $victim = str_replace(array('[', ']'), '', $log['Victim']);
                $killLogs[] = array(
                    'full_msg' => '<b>'.$killer.'</b> mató a <b>'.$victim.'</b> en '.$mapName.'. <span class="text-muted" style="font-size:0.8rem">'.$haceCuanto.'</span>',
                    'icon' => (strcasecmp($killer, $charName) == 0) ? 'fa-crosshairs' : 'fa-skull'
                );
            }
        }
    } catch (Exception $ex) {}

    // Progreso
    $resetLevelConfig = isset($configData['reset_level']) ? (int)$configData['reset_level'] : 400;
    $currentLevel = (int)$charData['cLevel'];
    $progressPercentage = min(max(($currentLevel / $resetLevelConfig) * 100, 0), 100);

    // Mapeo Clases
    $classId = $charData['Class'];
    $baseName = 'dw'; $isDL = false;
    if(in_array($classId, array(0, 1, 2, 3))) $baseName = 'dw';
    elseif(in_array($classId, array(16, 17, 18, 19))) $baseName = 'dk';
    elseif(in_array($classId, array(32, 33, 34, 35))) $baseName = 'elf';
    elseif(in_array($classId, array(48, 49, 50))) $baseName = 'mg';
    elseif(in_array($classId, array(64, 65, 66))) { $baseName = 'dl'; $isDL = true; }
    elseif(in_array($classId, array(80, 81, 82, 83))) $baseName = 'sum';
    elseif(in_array($classId, array(96, 97, 98))) $baseName = 'rf';

    $avatarPath = __BASE_URL__ . 'templates/default/img/character-avatars/' . $baseName . '.jpg';
    $modelPath = __BASE_URL__ . 'img/models/Faces/' . $baseName . '.gltf';

} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage();
    return;
}

// --- RENDERIZADO ---
echo '<link rel="stylesheet" href="' . __BASE_URL__ . 'templates/misc/css/player.css">';
echo '<div class="container-fluid py-5 profile-wrapper">';
    echo '<div class="row g-4 d-flex align-items-stretch">';
        
        // COL 1: Render 3D
        echo '<div class="col-lg-4">';
            echo '<div class="profile-card h-100 p-4 d-flex flex-column center">';
                echo '<h2 class="trajan-title text-orange m-0 text-center" style="font-size: 2.5rem; text-shadow: 0 0 15px rgba(255, 102, 0, 0.4); letter-spacing: 2px;">' . $charData['Name'] . '</h2>';
                if (!empty($charData['GuildName'])) {
                    echo '<div class="d-flex align-items-center justify-content-center mt-1">';
                        if (!empty($charData['GuildMark'])) {
                            echo '<img src="' . __BASE_URL__ . 'api/guildmark.php?data=' . urlencode(bin2hex($charData['GuildMark'])) . '" width="24" height="24" class="me-2" style="image-rendering: pixelated; border: 1px solid rgba(255,255,255,0.1);" alt="Guild Logo">';
                        }
                        echo '<div class="text-white-50 text-uppercase trajan-title" style="font-size: 1.1rem; letter-spacing: 3px;">' . $charData['GuildName'] . '</div>';
                    echo '</div>';
                }
                echo '<div id="character-3d-container" class="render-box flex-grow-1 d-flex align-items-center justify-content-center" style="min-height: 400px; cursor: grab;"></div>';
                echo '<div class="exp-bar-container mt-3" title="Progreso para el Reset: ' . $currentLevel . ' / ' . $resetLevelConfig . '">';
                    echo '<div class="exp-bar-progress" style="width: ' . $progressPercentage . '%;"></div>';
                echo '</div>';
                echo '<small class="text-white-50 cinzel-text" style="font-size: 0.7rem;">Nivel ' . $currentLevel . ' de ' . $resetLevelConfig . ' para el siguiente Reset</small>';
            echo '</div>';
        echo '</div>';

        // COL 2: Stats e Inventario
        echo '<div class="col-lg-4">';
            echo '<div class="profile-card h-100 p-4 d-flex flex-column">';
                echo '<div class="stats-section">';
                    echo '<h4 class="text-orange text-uppercase border-bottom border-secondary border-opacity-25 pb-2 mb-3 trajan-title">STATS</h4>';
                    echo '<div class="p-3" style="background: rgba(0,0,0,0.3); border-radius: 8px;">';
                        echo '<div class="d-flex align-items-center gap-4">';
                            echo '<div class="stat-avatar"><img src="' . $avatarPath . '" alt="Clase" class="rounded border border-orange shadow" style="width: 80px; height: 80px; object-fit: cover;"></div>';
                            echo '<div class="stats-list flex-grow-1"><div class="d-flex justify-content-start gap-4">';
                                echo '<div class="stat-group">';
                                    echo '<div class="stat-line"><span>STR:</span> <span class="text-orange fw-bold">' . ($showStats ? number_format($charData['Strength']) : '<b>Oculto</b>') . '</span></div>';
                                    echo '<div class="stat-line"><span>AGI:</span> <span class="text-orange fw-bold">' . ($showStats ? number_format($charData['Dexterity']) : '<b>Oculto</b>') . '</span></div>';
                                echo '</div>';
                                echo '<div class="stat-group">';
                                    echo '<div class="stat-line"><span>VIT:</span> <span class="text-orange fw-bold">' . ($showStats ? number_format($charData['Vitality']) : '<b>Oculto</b>') . '</span></div>';
                                    echo '<div class="stat-line"><span>ENE:</span> <span class="text-orange fw-bold">' . ($showStats ? number_format($charData['Energy']) : '<b>Oculto</b>') . '</span></div>';
                                    if($isDL || (isset($charData['Leadership']) && $charData['Leadership'] > 0)) {
                                        echo '<div class="stat-line"><span>CMD:</span> <span class="text-orange fw-bold">' . ($showStats ? number_format($charData['Leadership']) : '<b>Oculto</b>') . '</span></div>';
                                    }
                                echo '</div>';
                            echo '</div></div>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
                echo '<hr class="border-orange border-opacity-50 my-4">';
                echo '<div class="w-100 d-flex align-items-stretch flex-grow-1">';
                    echo '<div class="w-100 d-flex flex-column h-100">';
                        if($showInventory) {
                            $invFile = __DIR__ . '/inventario.php';
                            if(file_exists($invFile)) { include($invFile); }
                        } else {
                            echo '<div class="d-flex flex-column align-items-center justify-content-center w-100 h-100" style="min-height: 350px; background: rgba(0,0,0,0.4); border-radius: 10px; border: 2px dashed rgba(255,255,255,0.1);">';
                            echo '<i class="fa fa-lock fa-5x mb-4 text-muted" style="opacity: 0.3;"></i>';
                            echo '<h4 class="text-white text-uppercase trajan-title m-0" style="opacity: 0.5; letter-spacing: 2px;">Inventario</h4>';
                            echo '<h3 class="text-orange text-uppercase trajan-title m-0" style="opacity: 0.8; letter-spacing: 2px;">OCULTO</h3>';
                            echo '</div>';
                        }
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

        // COL 3: PvP Ranking y Logs
        echo '<div class="col-lg-4">';
            echo '<div class="profile-card h-100 p-4 d-flex flex-column">';
                echo '<div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 pb-2 mb-3">';
                    echo '<h4 class="text-orange text-uppercase m-0 trajan-title">Ranking PvP</h4>';
                    echo '<div class="elo-badge px-3 py-1" style="background: rgba(255,102,0,0.1); border: 1px solid #ff6600; border-radius: 4px;"><small class="text-white cinzel-text">ELO:</small> <span class="text-orange fw-bold">' . number_format($charElo) . '</span></div>';
                echo '</div>';
                echo '<div class="activity-log flex-grow-1 log-scroll">';
                    if ($showPvPLogs) {
                        if (empty($killLogs)) {
                            echo '<div class="text-white text-center py-5 cinzel-text"><i class="fa fa-info-circle d-block mb-2 opacity-50"></i>No hay actividad de PvP reciente.</div>';
                        } else {
                            foreach ($killLogs as $log) {
                                echo '<div class="log-item border-bottom border-secondary border-opacity-10 py-3 d-flex align-items-center gap-2"><span class="text-white cinzel-text" style="font-size: 0.9rem;">' . $log['full_msg'] . '</span></div>';
                            }
                        }
                    } else {
                        echo '<div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="opacity: 0.5;"><i class="fa fa-user-secret fa-4x mb-3"></i><span class="text-uppercase fw-bold trajan-title" style="letter-spacing: 1px;">Historial PvP Oculto</span></div>';
                    }
                echo '</div>';
            echo '</div>';
        echo '</div>';

    echo '</div>';
echo '</div>';

// JavaScript Loader
echo '<script type="importmap">{"imports": {"three": "https://unpkg.com/three@0.150.1/build/three.module.js","three/addons/": "https://unpkg.com/three@0.150.1/examples/jsm/"}}</script>';
echo '<script type="module">
import * as THREE from "three";
import { GLTFLoader } from "three/addons/loaders/GLTFLoader.js";

document.addEventListener("DOMContentLoaded", function() {
    const container = document.getElementById("character-3d-container");
    if (!container) return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    container.appendChild(renderer.domElement);

    const ambientLight = new THREE.AmbientLight(0xffffff, 1.8);
    scene.add(ambientLight);
    const dirLight = new THREE.DirectionalLight(0xffffff, 1);
    dirLight.position.set(5, 5, 5);
    scene.add(dirLight);

    const loader = new GLTFLoader();
    const modelUrl = "' . $modelPath . '";

    loader.load(modelUrl, function(gltf) {
        const model = gltf.scene;
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        model.position.y = -(size.y / 2); 
        scene.add(model);
        const maxDim = Math.max(size.x, size.y, size.z);
        camera.position.z = maxDim * 2.5; 
        camera.lookAt(0, 0, 0);

        function animate() {
            requestAnimationFrame(animate);
            model.rotation.y += 0.005;
            renderer.render(scene, camera);
        }
        animate();
    }, undefined, function(error) {
        container.innerHTML = \'<img src="' . $avatarPath . '" class="img-fluid" style="max-height:250px; opacity:0.8;">\';
    });

    window.addEventListener("resize", () => {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });
});
</script>';
echo '<link rel="stylesheet" href="' . __BASE_URL__ . 'templates/misc/css/inventory_viewer.css">';
echo '<div id="mu-tooltip"></div>';
echo '<script src="' . __BASE_URL__ . 'templates/misc/js/inventory_tooltip.js"></script>';
?>