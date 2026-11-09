<?php
/**
 * api/info_processor.php
 * Backend Blindado - Auto-Search Core
 */

define('access', 'api');
error_reporting(0);
ini_set('display_errors', 0);

// --- 1. BUSCADOR DE NÚCLEO INTELIGENTE ---
$possible_cores = [
    // Opción A: Estructura estándar (api/ -> includes/)
    __DIR__ . '/../includes/webengine.php',
    __DIR__ . '/../includes/muservermaker.php',
    
    // Opción B: Ruta Absoluta (Document Root)
    $_SERVER['DOCUMENT_ROOT'] . '/includes/webengine.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/muservermaker.php',
    
    // Opción C: Estás en la raíz (por si acaso)
    __DIR__ . '/includes/webengine.php'
];

$core_loaded = false;
foreach ($possible_cores as $path) {
    if (file_exists($path)) {
        require_once($path);
        $core_loaded = true;
        break;
    }
}

if (!$core_loaded) {
    // Si falla, mostramos dónde estamos parados para depurar
    die("Error Crítico: No se encuentra 'includes/webengine.php' ni 'muservermaker.php'.<br>Script ejecutado desde: " . __DIR__);
}

// Cargar clases DB si el núcleo no las cargó
if(!class_exists('Connection')) {
    $classPath = dirname(__DIR__) . '/includes/classes/';
    if(file_exists($classPath . 'class.connection.php')) include($classPath . 'class.connection.php');
    if(file_exists($classPath . 'class.database.php')) include($classPath . 'class.database.php');
}

ob_clean(); // Limpiar salidas previas

// --- 2. CONFIGURACIÓN ---
// Intentamos ubicar la carpeta config basándonos en donde encontramos el núcleo
$base_includes = dirname(dirname(__FILE__)) . '/includes/';
if(!is_dir($base_includes)) $base_includes = $_SERVER['DOCUMENT_ROOT'] . '/includes/';

$jsonPath = $base_includes . 'config/custom_settings.json';
$config = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];

$eventsFile = $base_includes . 'config/server_events.json';
$eventsData = file_exists($eventsFile) ? json_decode(file_get_contents($eventsFile), true) : [];

$dataFile = $base_includes . 'config/server_info.json';
$customData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

$type = isset($_GET['type']) ? $_GET['type'] : 'general';

// --- 3. LÓGICA DE DATOS ---

if($type == 'stats') {
    try {
        $db = Connection::Database('MuOnline');
        // Consulta optimizada para contar personajes por clase
        $sql = "SELECT Class, COUNT(*) as Total FROM Character GROUP BY Class";
        $characters = $db->query_fetch($sql);
        
        $raceData = [
            'DW'  => ['count' => 0, 'img' => 'dw.jpg',  'color' => '#3498db'],
            'DK'  => ['count' => 0, 'img' => 'dk.jpg',  'color' => '#e74c3c'],
            'ELF' => ['count' => 0, 'img' => 'elf.jpg', 'color' => '#2ecc71'],
            'MG'  => ['count' => 0, 'img' => 'mg.jpg',  'color' => '#9b59b6'],
            'DL'  => ['count' => 0, 'img' => 'dl.jpg',  'color' => '#f1c40f'],
            'SU'  => ['count' => 0, 'img' => 'sum.jpg', 'color' => '#e67e22'],
            'RF'  => ['count' => 0, 'img' => 'rf.jpg',  'color' => '#bdc3c7']
        ];

        if(is_array($characters)) {
            foreach($characters as $char) {
                $c = (int)$char['Class'];
                if(in_array($c, [0,1,2,3,7])) $raceData['DW']['count'] += $char['Total'];
                elseif(in_array($c, [16,17,18,19,23])) $raceData['DK']['count'] += $char['Total'];
                elseif(in_array($c, [32,33,34,35,39])) $raceData['ELF']['count'] += $char['Total'];
                elseif(in_array($c, [48,49,50,54])) $raceData['MG']['count'] += $char['Total'];
                elseif(in_array($c, [64,65,66,70])) $raceData['DL']['count'] += $char['Total'];
                elseif(in_array($c, [80,81,82,83,87])) $raceData['SU']['count'] += $char['Total'];
                elseif(in_array($c, [96,97,98,102])) $raceData['RF']['count'] += $char['Total'];
            }
        }
        
        $json_counts = json_encode(array_column($raceData, 'count'));
        
        // Obtener nombre del template activo para la ruta de imágenes
        $tpl = 'default';
        if(function_exists('muservermakerConfigs')) {
            $cfg = muservermakerConfigs();
            if(isset($cfg['website_template'])) $tpl = $cfg['website_template'];
        }

        ?>
        <div style="text-align:center;">
            <h3 style="color:#FF8A3B; margin-bottom:20px;">Población del Servidor</h3>
            <input type="hidden" id="raw-stats-data" value='<?php echo $json_counts; ?>'>
            
            <div style="width:100%; max-width:350px; height:250px; margin: 0 auto; position:relative;">
                <canvas id="charsChart"></canvas>
            </div>
            
            <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:10px; margin-top:20px;">
                <?php foreach($raceData as $name => $data): 
                    // Ruta de imagen dinámica
                    $imgUrl = __BASE_URL__ . 'templates/' . $tpl . '/img/character-avatars/' . $data['img'];
                ?>
                    <div style="text-align:center; width:60px;">
                        <img src="<?php echo $imgUrl; ?>" 
                             onerror="this.src='https://placehold.co/45x45/000/FFF?text=<?php echo $name; ?>'"
                             style="width:40px; height:40px; border-radius:50%; border:2px solid <?php echo $data['color']; ?>; object-fit:cover;">
                        <div style="font-size:9px; font-weight:bold; margin-top:3px; color:#aaa;"><?php echo $name; ?></div>
                        <div style="font-size:11px; color:#FF8A3B;"><?php echo $data['count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        exit;
    } catch (Exception $e) { 
        die("<div class='alert alert-danger'>Error conectando a DB: ".$e->getMessage()."</div>"); 
    }
}

if($type == 'invasions') {
    ?>
    <h3 style="color:#FF8A3B; margin-bottom:15px;"><i class="fas fa-clock"></i> Horario de eventos</h3>
    <div style="width: 100%; display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid rgba(255,138,59,0.1); padding-bottom: 20px;">
        <div class="info-event-tab active-tab" onclick="filterInfoEvents('Daily', this)" style="background:#FF8A3B; color:#000; width: 100px; text-align:center; padding:8px; cursor:pointer; border-radius:4px; font-weight:bold; font-size:11px;">DIARIOS</div>
        <div class="info-event-tab" onclick="filterInfoEvents('Weekly', this)" style="background:rgba(255,255,255,0.05); color:#fff; width: 100px; text-align:center; padding:8px; cursor:pointer; border-radius:4px; font-weight:bold; font-size:11px;">SEMANALES</div>
        <div class="info-event-tab" onclick="filterInfoEvents('Special', this)" style="background:rgba(255,255,255,0.05); color:#fff; width: 100px; text-align:center; padding:8px; cursor:pointer; border-radius:4px; font-weight:bold; font-size:11px;">ESPECIALES</div>
    </div>
    
    <?php 
    $categories = ['Daily' => 'diarios', 'Weekly' => 'semanales', 'Special' => 'especiales'];
    foreach($categories as $catKey => $catName): 
        $filtered = is_array($eventsData) ? array_filter($eventsData, function($e) use ($catKey) { return isset($e['type']) && $e['type'] === $catKey; }) : [];
    ?>
        <div id="info-cat-<?php echo $catKey; ?>" class="info-event-container" style="display: <?php echo ($catKey === 'Daily' ? 'grid' : 'none'); ?>; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
            <?php if(empty($filtered)): ?>
                <div style="grid-column: 1 / -1; text-align:center; padding:20px; color:#555;">No hay eventos <?php echo $catName; ?>.</div>
            <?php else: ?>
                <?php foreach($filtered as $event): 
                    $color = ($catKey == 'Daily') ? '#28a745' : (($catKey == 'Weekly') ? '#007bff' : '#dc3545');
                ?>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 10px; border-radius: 6px; border-top: 2px solid <?php echo $color; ?>; text-align: center;">
                        <h4 style="color: #fff; margin: 0 0 5px 0; font-size: 13px; text-transform: uppercase;"><?php echo $event['name']; ?></h4>
                        <div style="color: #FF8A3B; font-weight: bold; font-size: 14px; margin-bottom: 5px;">
                            <span class="info-timer-live" data-times="<?php echo $event['times']; ?>">--:--</span>
                        </div>
                        <div style="background: rgba(0,0,0,0.3); padding: 3px; border-radius: 3px;">
                            <span style="color: #ccc; font-size: 10px;">
                                <?php echo ($catKey == 'Weekly' && isset($event['day_start'])) ? $event['day_start']." ".$event['time_start'] : $event['times']; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <script>
    function filterInfoEvents(cat, el) {
        document.querySelectorAll('.info-event-tab').forEach(t => { t.style.background = 'rgba(255,255,255,0.05)'; t.style.color = '#fff'; });
        el.style.background = '#FF8A3B'; el.style.color = '#000';
        document.querySelectorAll('.info-event-container').forEach(c => c.style.display = 'none');
        document.getElementById('info-cat-' + cat).style.display = 'grid';
    }
    
    // Timer Logic Simplificada
    function runTimers() {
        const now = new Date();
        document.querySelectorAll('.info-timer-live').forEach(t => {
            const times = t.dataset.times.split(',');
            let next = null; let minMs = Infinity;
            times.forEach(tm => {
                const p = tm.trim().split(':');
                if(p.length<2) return;
                let d = new Date(now);
                d.setHours(parseInt(p[0]), parseInt(p[1]), 0, 0);
                if(d <= now) d.setDate(d.getDate() + 1);
                let diff = d - now;
                if(diff < minMs) { minMs = diff; next = d; }
            });
            if(next) {
                let diff = next - now;
                let h = Math.floor(diff/3600000);
                let m = Math.floor((diff%3600000)/60000);
                let s = Math.floor((diff%60000)/1000);
                t.innerText = diff < 60000 ? "¡AHORA!" : (h>0?h+'h ':'') + m+'m '+s+'s';
            }
        });
    }
    if(window.iv) clearInterval(window.iv);
    window.iv = setInterval(runTimers, 1000);
    runTimers();
    </script>
    <?php
    exit;
}

// CASO 3: GENERAL
if(isset($customData[$type])) {
    $data = isset($customData[$type]['data']) ? $customData[$type]['data'] : $customData[$type];
    $icon = isset($customData[$type]['config']['icon']) ? $customData[$type]['config']['icon'] : 'fas fa-info-circle';

    if($type !== 'general' && empty($data)) return;

    echo '<div style="background:rgba(0,0,0,0.2); padding:20px; border-radius:8px;">';
    echo '<h3 style="color:#FF8A3B; margin-bottom:15px; text-transform:uppercase; font-size:16px;">';
    echo '<i class="'.$icon.'" style="margin-right:10px;"></i> ' . ($type == 'general' ? 'Información General' : htmlspecialchars($type));
    echo '</h3>';
    echo '<table class="info-table" style="width:100%;"><tbody>';

    $skip = [];
    if($type == 'general') {
        $defs = [
            'Versión' => $config['server_version'] ?? 'N/A',
            'Exp' => $config['server_exp'] ?? 'N/A',
            'Master Exp' => $config['server_exp_master'] ?? 'N/A',
            'Drop' => ($config['server_drop'] ?? 'N/A'),
            'Max Stats' => $config['config_max_stats'] ?? 'N/A'
        ];
        foreach($defs as $k => $v) {
            echo '<tr><td style="font-weight:bold; color:#FF8A3B; width:40%;">'.$k.'</td><td style="color:#ddd;">'.$v.'</td></tr>';
            $skip[] = strtolower($k);
        }
    }

    if(is_array($data)) {
        foreach($data as $itm) {
            if(!isset($itm['title'])) continue;
            if($type == 'general' && in_array(strtolower($itm['title']), $skip)) continue;
            echo '<tr><td style="font-weight:bold; color:#FF8A3B; width:40%;">'.htmlspecialchars($itm['title']).'</td><td style="color:#ddd;">'.htmlspecialchars($itm['desc']).'</td></tr>';
        }
    }
    echo '</tbody></table></div>';
} else {
    echo '<div style="padding:20px; color:#888;">Información no disponible.</div>';
}
?>