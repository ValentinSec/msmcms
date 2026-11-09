<?php
/**
 * Rankings Module - Bosses (Unified Logic & UI)
 * File: modules/rankings/bosses.php
 * Fix: Dynamic DB Connection & Collation Check
 * ADAPTADO PARA OFUSCACIÓN (HTML -> PHP ECHO)
 */

// 1. CARGA DE CONFIGURACIONES
if(!defined('__PATH_CONFIGS__')) define('__PATH_CONFIGS__', __DIR__ . '/../../includes/config/');
if(!defined('__PATH_INCLUDES__')) define('__PATH_INCLUDES__', __DIR__ . '/../../includes/');

$weConfigPath = __PATH_CONFIGS__ . 'muservermaker.json';
$bossConfigPath = __PATH_INCLUDES__ . 'config/bosses.json';

// --- DETECCIÓN INTELIGENTE DE LA BASE DE DATOS ---
$dbName = ''; 

if (file_exists($weConfigPath)) {
    $weConfig = json_decode(file_get_contents($weConfigPath), true);
    if (isset($weConfig['SQL_DB_NAME']) && !empty($weConfig['SQL_DB_NAME'])) {
        $dbName = $weConfig['SQL_DB_NAME']; 
    } elseif (isset($weConfig['SQL']['MuOnline']['Database']) && !empty($weConfig['SQL']['MuOnline']['Database'])) {
        $dbName = $weConfig['SQL']['MuOnline']['Database']; 
    } elseif (isset($weConfig['server_db_name']) && !empty($weConfig['server_db_name'])) {
        $dbName = $weConfig['server_db_name']; 
    }
}

if (empty($dbName) && defined('__DB_NAME__')) {
    $dbName = __DB_NAME__;
}

if (empty($dbName)) {
    $dbName = 'MuOnline';
}

// Cargar Configuración de Bosses
$bossConfig = [];
if (file_exists($bossConfigPath)) {
    $bossConfig = json_decode(file_get_contents($bossConfigPath), true);
}
if (!is_array($bossConfig)) $bossConfig = [];


// --------------------------------------------------------------------
// 2. LÓGICA DE DATOS (RESPUESTA AJAX)
// --------------------------------------------------------------------
if(isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    if (ob_get_length()) ob_clean();

    $rawType = isset($_GET['type']) ? $_GET['type'] : 'bosses_general';
    $requestType = preg_replace('/[^a-zA-Z0-9_]/', '', $rawType);
    $bossID = str_replace('bosses_', '', $requestType);
    
    $qty_c = 50; 
    $query = "";

    if ($bossID === 'general') {
        $query = "SELECT TOP $qty_c 
                    b.CharacterName as Name, 
                    SUM(b.KillCount) as Stat, 
                    MAX(c.Class) as Class,
                    MAX(b.MonsterIndex) as RefMob 
                  FROM [{$dbName}].[dbo].[MUSERVERMAKER_BOSS_RANKING] b
                  LEFT JOIN [{$dbName}].[dbo].[Character] c ON b.CharacterName = c.Name COLLATE DATABASE_DEFAULT
                  GROUP BY b.CharacterName
                  ORDER BY Stat DESC";
    } else {
        $targetMob = (int)$bossID;
        $query = "SELECT TOP $qty_c 
                    b.CharacterName as Name, 
                    b.KillCount as Stat, 
                    c.Class,
                    b.MonsterIndex as RefMob
                  FROM [{$dbName}].[dbo].[MUSERVERMAKER_BOSS_RANKING] b
                  LEFT JOIN [{$dbName}].[dbo].[Character] c ON b.CharacterName = c.Name COLLATE DATABASE_DEFAULT
                  WHERE b.MonsterIndex = $targetMob
                  ORDER BY Stat DESC";
    }

    $rankingData = [];
    try {
        if(!isset($db)) {
            $db = Connection::Database('Me_MuOnline');
        }
        $rankingData = $db->query_fetch($query);
    } catch (Exception $e) {
        error_log("Error Ranking Bosses: " . $e->getMessage());
        $rankingData = [];
    }

    if(!$rankingData || empty($rankingData)) {
        $cols = ($bossID === 'general') ? 4 : 5;
        echo '<tr><td colspan="'.$cols.'" style="text-align: center !important; vertical-align: middle; height: 100px;">';
        echo '<div style="display:flex; flex-direction:column; justify-content:center; align-items:center;">';
        echo '<strong style="color:white; font-size:1.1rem;">Ranking desierto</strong>';
        echo '<span class="text-muted" style="margin-top:5px;">Nadie ha cazado a este Boss todavía.</span>';
        echo '</div>';
        echo '</td></tr>';
    } 
    else {
        $i = 1;
        foreach($rankingData as $row) {
            $classCode = isset($row['Class']) ? $row['Class'] : -1;
            
            $avatarImg = 'dw.jpg'; 
            if(in_array($classCode, [0, 1, 2, 3, 7])) $avatarImg = 'dw.jpg';
            elseif(in_array($classCode, [16, 17, 18, 19, 23])) $avatarImg = 'dk.jpg';
            elseif(in_array($classCode, [32, 33, 34, 35, 39])) $avatarImg = 'elf.jpg';
            elseif(in_array($classCode, [48, 49, 50, 54])) $avatarImg = 'mg.jpg';
            elseif(in_array($classCode, [64, 65, 66, 70])) $avatarImg = 'dl.jpg';
            elseif(in_array($classCode, [80, 81, 82, 86])) $avatarImg = 'sum.jpg';
            elseif(in_array($classCode, [96, 97, 98, 102])) $avatarImg = 'rf.jpg';
            
            $baseUrl = defined('__BASE_URL__') ? __BASE_URL__ : './';
            $pjAvatarPath = $baseUrl . "img/character-avatars/" . $avatarImg;

            $mobIndex = $row['RefMob'];
            
            $showReward = true;
            if (isset($bossConfig[$mobIndex]) && isset($bossConfig[$mobIndex]['show_reward'])) {
                $showReward = $bossConfig[$mobIndex]['show_reward'];
            }

            $customImg = isset($bossConfig[$mobIndex]['custom_image']) ? $bossConfig[$mobIndex]['custom_image'] : '';
            
            if ($bossID === 'general') {
                $bossImgUrl = $baseUrl . "img/dorados/trophy.png";
            } elseif (!empty($customImg)) {
                $bossImgUrl = $baseUrl . "img/bosses/" . $customImg;
            } else {
                $bossImgUrl = $baseUrl . "img/dorados/" . $mobIndex . ".png";
            }

            $rewardFile = isset($bossConfig[$mobIndex]['reward_img']) ? $bossConfig[$mobIndex]['reward_img'] : 'box_gm.webp';
            $rewardUrl = $baseUrl . "img/boxes/" . $rewardFile;

            echo '<tr>';
            echo '<td><div class="rank-pos">'.$i.'</div></td>';
            
            echo '<td class="fw-bold text-white char-column">';
            echo $row['Name'];
            echo '</td>';

            echo '<td class="text-center"><img src="'.$pjAvatarPath.'" class="rank-avatar-img" title="Class"></td>';
            
            if ($bossID !== 'general') {
                echo '<td class="text-center"><img src="'.$bossImgUrl.'" style="width: 35px; height: auto; filter: drop-shadow(0 0 5px rgba(255,215,0,0.5));" onerror="this.style.display=\'none\'"></td>';
            }
            
            echo '<td class="text-center align-middle">';
            if ($bossID === 'general' || !$showReward) {
                echo '<span class="text-warning fw-bold" style="font-size:1.1em;">'.number_format($row['Stat']).'</span>';
            } else {
                echo '<div class="d-flex align-items-center justify-content-center gap-1">';
                echo '<img src="'.$rewardUrl.'" style="width: 22px; height: auto;">';
                echo '<span class="text-white fw-bold">x'.number_format($row['Stat']).'</span>';
                echo '</div>';
            }
            echo '</td>';
            
            echo '</tr>';
            $i++;
        }
    }
    die();
}

// 3. INTERFAZ DE USUARIO (CONVERTIDA A PHP)
$serverName = custom_config("server_name");
$bossBtns = "";

foreach($bossConfig as $id => $data) {
    if(isset($data['active']) && $data['active']) {
        $bossBtns .= '<a href="javascript:void(0);" onclick="loadBossRank(\'bosses_'.$id.'\', this)" class="rank-nav-btn boss-btn">'.$data['name'].'</a>';
    }
}

// Preparamos variables JS
$jsBossConfig = !empty($bossConfig) ? json_encode($bossConfig) : '{}';
$jsBaseUrl = defined('__BASE_URL__') ? __BASE_URL__ : './';

echo <<<HTML
<div class="rankings-wrapper">
    <div class="rank-header text-center mb-5">
        <h1 class="rank-title" style="color: #ffc107; text-shadow: 0 0 15px rgba(255, 193, 7, 0.3);">BOSS RANKING</h1>
        <p class="rank-subtitle">Los cazadores más letales de {$serverName}</p>
    </div>

    <div class="rank-nav-container d-flex justify-content-center flex-wrap gap-2 mb-5">
        <a href="javascript:void(0);" onclick="loadBossRank('bosses_general', this)" class="rank-nav-btn boss-btn active">
            <i class="fa fa-trophy"></i> General
        </a>
        {$bossBtns}
    </div>

    <div class="rank-table-card" style="position: relative; min-height: 400px;">
        <table class="table table-dark custom-balduneta-table mb-0">
            <thead id="boss-header"></thead>
            <tbody id="rank-content"></tbody>
        </table>
        
        <div id="rank-loader" style="display:none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 10; align-items: center; justify-content: center; border-radius: 15px;">
            <div class="spinner-border text-warning" role="status"></div>
        </div>
    </div>
</div>

<style>
    .rank-avatar-img { width: 32px; height: 32px; border-radius: 50%; border: 1px solid rgba(255, 193, 7, 0.3); object-fit: cover; margin: 0 auto; display: block; }
    .rank-nav-btn.boss-btn { border-color: rgba(255, 193, 7, 0.2); color: #aaa; transition: all 0.3s ease; }
    .rank-nav-btn.boss-btn:hover { color: #fff; background: rgba(255, 193, 7, 0.05); }
    .rank-nav-btn.boss-btn.active { background: rgba(255, 193, 7, 0.15); color: #ffc107; border-color: #ffc107; box-shadow: 0 0 10px rgba(255, 193, 7, 0.1); }
</style>

<script>
// JSON seguro
const bossSettings = {$jsBossConfig};
// Obtenemos URL base de PHP o deducimos
const BASE_URL = '{$jsBaseUrl}';

function updateBossHeader(type) {
    const header = document.getElementById('boss-header');
    const bossID = type.replace('bosses_', '');
    let showRewardTitle = 'Reward';
    
    if (bossID === 'general') {
        showRewardTitle = 'TOTAL';
    } else {
        if (bossSettings[bossID] && bossSettings[bossID].show_reward === false) {
            showRewardTitle = 'Total';
        }
    }

    let html = '<tr class="text-uppercase" style="font-size: 0.8rem; color: #8a8f9d; border-bottom: 2px solid #222;">';
    html += '<th style="width: 60px; text-align: center;">Pos</th>';
    html += '<th>Jugador</th>';
    html += '<th style="text-align: center;">Class</th>';
    if (type !== 'bosses_general') html += '<th style="text-align: center;">Boss</th>';
    html += '<th style="text-align: center;">' + showRewardTitle + '</th>';
    html += '</tr>';
    header.innerHTML = html;
}

function loadBossRank(type, element) {
    if(element) {
        document.querySelectorAll('.rank-nav-btn').forEach(btn => btn.classList.remove('active'));
        element.classList.add('active');
    }
    updateBossHeader(type);
    
    const content = document.getElementById('rank-content');
    const loader = document.getElementById('rank-loader');
    
    if(content) content.style.opacity = '0.3';
    if(loader) loader.style.display = 'flex'; 

    // IMPORTANTE: Aseguramos que la petición vaya al módulo correcto
    fetch(BASE_URL + 'rankings/?ajax=1&type=' + type)
        .then(response => response.text())
        .then(data => {
            if(content) {
                content.innerHTML = data.trim(); 
                content.style.opacity = '1';
            }
            if(loader) loader.style.display = 'none';
        })
        .catch(err => {
            console.error(err);
            if(loader) loader.style.display = 'none';
        });
}

document.addEventListener('DOMContentLoaded', () => { loadBossRank('bosses_general', null); });
</script>
HTML;
?>