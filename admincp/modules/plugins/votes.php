<?php
/**
 * Vote Manager Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 */
if(!defined('access') or !access) die();

// 1. RUTA DE ARCHIVOS JSON
$basePath = __DIR__ . '/../../../includes/config/';
$settingsFile = $basePath . 'custom_settings.json';
$votesFile    = $basePath . 'votes.json';

// 2. CARGAR CONFIGURACIÓN GENERAL
$generalConfig = [];
if(file_exists($settingsFile)) {
    $generalConfig = json_decode(file_get_contents($settingsFile), true);
}

// 3. SITIOS AUTORIZADOS (Whitelist)
$allowedSites = [
    'mmohub.com' => 'MMOHub',
    'xtremetop100.com' => 'XtremeTop100',
    'mtop.site' => 'MTop',
    'topg.org' => 'TopG',
    'supremetop100.com' => 'SupremeTop100',
    'top100arena.com' => 'Top100Arena',
    'gtop100.com' => 'GTop100',
    'arenatop100.com' => 'ArenaTop100',
    'mmtop200.com' => 'MMTop200',
    'google.com' => 'Google'
];

// 4. PROCESAR GUARDADO
$successMsg = '';
if(isset($_POST['save_votes'])) {
    $dataToSave = [
        'vote_reward'   => isset($_POST['vote_reward']) ? $_POST['vote_reward'] : 0,
        'vote_currency' => isset($_POST['vote_currency']) ? $_POST['vote_currency'] : '',
        'vote_interval' => isset($_POST['vote_interval']) ? $_POST['vote_interval'] : 12,
        'links'         => []
    ];

    // Procesar cada sitio
    foreach($allowedSites as $domain => $name) {
        $key = str_replace('.', '_', $domain);
        
        $url = isset($_POST['url_' . $key]) ? $_POST['url_' . $key] : '';
        $active = isset($_POST['active_' . $key]) ? 1 : 0;
        
        // Validación de dominio
        if(!empty($url)) {
            $parsed = parse_url($url);
            $host = isset($parsed['host']) ? $parsed['host'] : '';
            if(strpos($host, $domain) === false) {
                $url = ""; 
            }
        }

        $dataToSave['links'][$domain] = [
            'name'   => $name,
            'url'    => $url,
            'active' => $active
        ];
    }

    // Crear directorio si no existe
    if(!is_dir(dirname($votesFile))) @mkdir(dirname($votesFile), 0777, true);

    file_put_contents($votesFile, json_encode($dataToSave, JSON_PRETTY_PRINT));
    $successMsg = '<div class="alert-success-custom">Cambios guardados correctamente.</div>';
}

// 5. CARGAR DATOS GUARDADOS
$votesConfig = [];
if(file_exists($votesFile)) {
    $votesConfig = json_decode(file_get_contents($votesFile), true);
}

// Helpers
function getVoteVal($key, $data, $default="") { return isset($data[$key]) ? $data[$key] : $default; }
function getLinkData($domain, $data) { 
    return isset($data['links'][$domain]) ? $data['links'][$domain] : ['url'=>'','active'=>0]; 
}

// Preparar Monedas
$activeCoins = [];
for($i=1; $i<=3; $i++) {
    if(isset($generalConfig["active_coin_$i"]) && $generalConfig["active_coin_$i"] === true) {
        $colName = isset($generalConfig["coin_{$i}_column"]) ? $generalConfig["coin_{$i}_column"] : "Coin $i";
        $activeCoins[$colName] = $colName;
    }
}
$tableCoins = isset($generalConfig['table_coins']) ? $generalConfig['table_coins'] : 'CashShopData';

// --- RENDERIZADO HTML (PHP PURO) ---

// CSS Styles
echo '<style>
    .settings-section { background-color: #1b1b28; border-radius: 8px; padding: 25px; border: 1px solid #2b2b40; box-shadow: 0 0 40px 0 rgba(0,0,0,0.2); margin-bottom: 30px; }
    .settings-title { font-size: 1.1rem; font-weight: 600; color: #fff; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #2b2b40; }
    .settings-label { color: #7e8299; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px; display: block; text-transform: uppercase; }
    .form-control-dark { background-color: #151521; border: 1px solid #2b2b40; color: #fff; width: 100%; padding: 10px; border-radius: 6px; outline: none; font-size: 0.95rem; }
    .form-control-dark:focus { border-color: #3699ff; }
    .form-control-dark:read-only { background-color: #1a1a24; color: #565674; cursor: not-allowed; }
    .btn-save-green { background-color: #00d084; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; }
    .btn-save-green:hover { background-color: #00b372; }
    .btn-action { padding: 6px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; border: 1px solid #2b2b40; background: #151521; color: #a1a5b7; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; margin-left: 5px; }
    .btn-action:hover { border-color: #3699ff; color: #fff; }
    .btn-visit { color: #3699ff; border-color: rgba(54, 153, 255, 0.3); }
    .btn-visit:hover { background: #3699ff; color: #fff; }
    .switch { position: relative; display: inline-block; width: 40px; height: 20px; margin-right: 10px; vertical-align: middle; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #2b2b40; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #00d084; }
    input:checked + .slider:before { transform: translateX(20px); }
    .alert-success-custom { background: #1b1b28; border-left: 4px solid #00d084; color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .vote-row { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #2b2b40; }
    .vote-row:last-child { border-bottom: none; }
    .vote-name { width: 150px; font-weight: 600; color: #fff; }
    .vote-input-container { flex-grow: 1; padding-right: 15px; }
    .vote-actions { display: flex; align-items: center; }
    .custom-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); backdrop-filter: blur(2px); }
    .custom-modal-content { background-color: #1e1e2d; margin: 15% auto; padding: 30px; border: 1px solid #f1416c; width: 400px; border-radius: 8px; text-align: center; box-shadow: 0 0 20px rgba(241, 65, 108, 0.2); color: #fff; }
    .modal-icon { font-size: 3rem; color: #f1416c; margin-bottom: 15px; }
    .modal-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 10px; }
    .modal-text { color: #a1a5b7; margin-bottom: 20px; font-size: 0.9rem; }
    .modal-btn { background: #f1416c; color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .modal-btn:hover { background: #d9214e; }
</style>';

echo '<div class="container-fluid">';
    
    // Alerta de éxito
    if(!empty($successMsg)) {
        echo $successMsg;
    }

    echo '<form action="" method="POST" id="votesForm">';
        
        // Header
        echo '<div class="row mb-4">';
            echo '<div class="col-6">';
                echo '<h3 style="color:#fff; font-weight:700; margin:0;">Administrar Votos</h3>';
            echo '</div>';
            echo '<div class="col-6 text-end" style="text-align: right;">';
                echo '<button type="button" onclick="validateAndSubmit()" class="btn-save-green">Guardar Cambios</button>';
            echo '</div>';
        echo '</div>';

        // Configuración General
        echo '<div class="settings-section">';
            echo '<h3 class="settings-title">Configurar Votos</h3>';
            echo '<div class="row">';
                
                // Reward
                echo '<div class="col-md-3">';
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Premio Votos</label>';
                        echo '<input type="number" name="vote_reward" class="form-control-dark" value="' . getVoteVal('vote_reward', $votesConfig, 10) . '" placeholder="0">';
                    echo '</div>';
                echo '</div>';
                
                // Currency
                echo '<div class="col-md-3">';
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Moneda</label>';
                        echo '<select name="vote_currency" class="form-control-dark">';
                            if(empty($activeCoins)) {
                                echo '<option value="">No hay monedas activas</option>';
                            } else {
                                foreach($activeCoins as $val => $text) {
                                    $sel = (getVoteVal('vote_currency', $votesConfig) == $val) ? 'selected' : '';
                                    echo '<option value="' . $val . '" ' . $sel . '>' . $text . '</option>';
                                }
                            }
                        echo '</select>';
                    echo '</div>';
                echo '</div>';

                // Table Info
                echo '<div class="col-md-3">';
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tabla Moneda</label>';
                        echo '<input type="text" class="form-control-dark" value="' . $tableCoins . '" readonly title="Dato extraído de Settings Tables">';
                    echo '</div>';
                echo '</div>';

                // Interval
                echo '<div class="col-md-3">';
                    echo '<div class="form-group">';
                        echo '<label class="settings-label">Tiempo de Voto</label>';
                        echo '<select name="vote_interval" class="form-control-dark">';
                            $intervals = [1, 6, 12, 24];
                            foreach($intervals as $iv) {
                                $sel = (getVoteVal('vote_interval', $votesConfig) == $iv) ? 'selected' : '';
                                echo '<option value="' . $iv . '" ' . $sel . '>Cada ' . $iv . ' Horas</option>';
                            }
                        echo '</select>';
                    echo '</div>';
                echo '</div>';

            echo '</div>';
        echo '</div>';

        // Links List
        echo '<div class="settings-section">';
            echo '<h3 class="settings-title">Links de Votos</h3>';
            
            foreach($allowedSites as $domain => $siteName) {
                $key = str_replace('.', '_', $domain);
                $linkData = getLinkData($domain, $votesConfig);
                $currentUrl = $linkData['url'];
                $isActive = $linkData['active'];
                $inputId = "url_" . $key;
                
                echo '<div class="vote-row">';
                    echo '<div class="vote-name">' . $siteName . '</div>';
                    
                    echo '<div class="vote-input-container">';
                        echo '<input type="text" id="' . $inputId . '" name="' . $inputId . '" class="form-control-dark vote-url-input" value="' . $currentUrl . '" placeholder="https://' . $domain . '/..." data-domain="' . $domain . '">';
                    echo '</div>';
                    
                    echo '<div class="vote-actions">';
                        echo '<label class="switch">';
                            echo '<input type="checkbox" name="active_' . $key . '" ' . ($isActive ? 'checked' : '') . '>';
                            echo '<span class="slider"></span>';
                        echo '</label>';
                        
                        echo '<button type="button" class="btn-action" onclick="enableEdit(\'' . $inputId . '\')"><i class="fa fa-pencil"></i> Editar</button>';
                        
                        if(!empty($currentUrl)) {
                            echo '<a href="' . $currentUrl . '" target="_blank" class="btn-action btn-visit"><i class="fa fa-external-link"></i> Visitar Sitio</a>';
                        } else {
                            echo '<button type="button" class="btn-action" style="opacity:0.5; cursor: default;">Visitar Sitio</button>';
                        }
                    echo '</div>';
                echo '</div>';
            }
            
        echo '</div>';
        
        echo '<input type="hidden" name="save_votes" value="1">';
    echo '</form>';
echo '</div>';

// Modal Error
echo '<div id="errorModal" class="custom-modal">';
    echo '<div class="custom-modal-content">';
        echo '<div class="modal-icon"><i class="fa fa-exclamation-circle"></i></div>';
        echo '<div class="modal-title">URL No Permitida</div>';
        echo '<div class="modal-text" id="modalMessage">El enlace ingresado no corresponde al sitio de votación seleccionado. Por seguridad, solo se permiten enlaces oficiales.</div>';
        echo '<button class="modal-btn" onclick="closeModal()">Entendido</button>';
    echo '</div>';
echo '</div>';

// Scripts
echo '<script>
    function enableEdit(id) {
        var input = document.getElementById(id);
        input.focus();
        input.style.borderColor = "#3699ff";
    }

    function closeModal() {
        document.getElementById("errorModal").style.display = "none";
    }

    function validateAndSubmit() {
        var inputs = document.getElementsByClassName("vote-url-input");
        var hasError = false;
        var errorDomain = "";

        for (var i = 0; i < inputs.length; i++) {
            var url = inputs[i].value.trim();
            var requiredDomain = inputs[i].getAttribute("data-domain");
            
            if(url !== "") {
                try {
                    if (!/^https?:\/\//i.test(url)) {
                        url = "https://" + url;
                    }
                    var urlObj = new URL(url);
                    
                    if (!urlObj.hostname.includes(requiredDomain)) {
                        hasError = true;
                        errorDomain = requiredDomain;
                        inputs[i].style.borderColor = "#f1416c"; 
                        break;
                    } else {
                         inputs[i].style.borderColor = "#2b2b40"; 
                    }
                } catch (_) {
                    hasError = true;
                    errorDomain = requiredDomain;
                    inputs[i].style.borderColor = "#f1416c";
                    break;
                }
            }
        }

        if(hasError) {
            var modal = document.getElementById("errorModal");
            var msg = document.getElementById("modalMessage");
            msg.innerHTML = "La URL ingresada no pertenece a <strong>" + errorDomain + "</strong>.<br>Por favor verifica el enlace.";
            modal.style.display = "block";
        } else {
            document.getElementById("votesForm").submit();
        }
    }
</script>';
?>