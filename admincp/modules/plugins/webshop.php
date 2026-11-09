<?php
/**
 * WebShop Manager Module - PREPARED FOR OBFUSCATION
 * Pure PHP Version (Concatenation Safe)
 * FIXES: Harmony Level Limit (Max 13), Zero Value Filter, Tooltip Resurrection.
 */

// --------------------------------------------------------------------
// 1. CONFIGURACIÓN Y RUTAS
// --------------------------------------------------------------------
$basePath = __DIR__ . '/../../../includes/config/';
$fileSettings = realpath($basePath . 'custom_settings.json');
$fileItemsManager = realpath($basePath . 'items_manager.php');
$fileShopList = $basePath . 'webshop_list.json';

if (!file_exists($fileShopList)) {
    file_put_contents($fileShopList, json_encode([]));
}

if (!file_exists($fileItemsManager)) {
    die("Error crítico: No se encuentra <b>includes/config/items_manager.php</b>");
}
require_once($fileItemsManager);

// --------------------------------------------------------------------
// 2. BACKEND: AJAX HANDLERS
// --------------------------------------------------------------------

if(isset($_GET['ajax']) && $_GET['ajax'] == 'load_items') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $itemsFound = [];

    for ($i = 0; $i < 512; $i++) {
        $name = ItemsManager::getItemName($category, $i);
        if ($name !== "Unknown Item" && !empty($name)) {
            $itemsFound[] = [
                'id' => $i,
                'group' => $category,
                'name' => $name,
                'image' => "img/items/{$category}-{$i}.gif"
            ];
        }
    }
    echo json_encode(['status' => 'success', 'data' => $itemsFound]);
    exit;
}

if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'save_item') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $harmonyData = json_decode($_POST['harmony'], true);
    $socketsData = isset($_POST['sockets']) ? json_decode($_POST['sockets'], true) : [];
    $excOptsData = isset($_POST['exc_opts']) ? json_decode($_POST['exc_opts'], true) : [];

    $newItem = [
        'uid' => uniqid(),
        'group' => $_POST['group'],
        'id' => $_POST['id'],
        'name' => $_POST['name'],
        'image' => $_POST['image'],
        'level' => $_POST['level'],
        'option' => $_POST['option'],
        'luck' => $_POST['luck'],
        'skill' => $_POST['skill'],
        'op380' => $_POST['op380'],
        'harmony' => $harmonyData,
        'exc_opts' => $excOptsData,
        'sockets' => $socketsData,
        'price' => $_POST['price'],
        'currency' => $_POST['currency']
    ];

    $currentList = json_decode(file_get_contents($fileShopList), true);
    if(!is_array($currentList)) $currentList = [];
    
    array_unshift($currentList, $newItem);
    
    if(file_put_contents($fileShopList, json_encode($currentList, JSON_PRETTY_PRINT))) {
        // Log Admin Action
        try {
            global $db;
            if(!isset($db) || !$db) $db = Connection::Database('Me_MuOnline');
            $adminUser = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
            $itemJson = json_encode($newItem);
            $db->query("INSERT INTO MUSERVERMAKER_WEBSHOP_ADMIN_LOG (admin_user, action_type, item_name, item_details) VALUES (?, 'ADD', ?, ?)", [$adminUser, $newItem['name'], $itemJson]);
        } catch (Exception $e) { }
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo escribir en el archivo JSON']);
    }
    exit;
}

if(isset($_GET['ajax']) && $_GET['ajax'] == 'load_shop') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $shopItems = json_decode(file_get_contents($fileShopList), true);
    echo json_encode(['status' => 'success', 'data' => $shopItems]);
    exit;
}

if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_item') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $uidToDelete = $_POST['uid'];
    $currentList = json_decode(file_get_contents($fileShopList), true);
    $deletedItemName = "Unknown Item";

    $newList = array_filter($currentList, function($item) use ($uidToDelete, &$deletedItemName) {
        if($item['uid'] === $uidToDelete) {
            $deletedItemName = $item['name'];
            return false;
        }
        return true;
    });
    
    if(file_put_contents($fileShopList, json_encode(array_values($newList), JSON_PRETTY_PRINT))) {
        // Log Delete Action
        try {
            global $db;
            if(!isset($db) || !$db) $db = Connection::Database('Me_MuOnline');
            $adminUser = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
            $db->query("INSERT INTO MUSERVERMAKER_WEBSHOP_ADMIN_LOG (admin_user, action_type, item_name, item_details) VALUES (?, 'DELETE', ?, ?)", [$adminUser, $deletedItemName, "UID: $uidToDelete"]);
        } catch (Exception $e) { }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar cambios']);
    }
    exit;
}

// --------------------------------------------------------------------
// 3. FRONTEND (RENDERIZADO PHP PURO)
// --------------------------------------------------------------------

$settings = [];
if (file_exists($fileSettings)) {
    $settings = json_decode(file_get_contents($fileSettings), true);
}
function getSet($key, $array, $default = '') { return isset($array[$key]) ? $array[$key] : $default; }

$categories = [
    0 => 'Swords', 1 => 'Axes', 2 => 'Maces & Scepters', 3 => 'Spears',
    4 => 'Bows & Crossbows', 5 => 'Staffs', 6 => 'Shields', 7 => 'Helms',
    8 => 'Armors', 9 => 'Pants', 10 => 'Gloves', 11 => 'Boots',
    12 => 'Wings / Orbs', 13 => 'Pets / Rings', 14 => 'Jewels / Misc', 15 => 'Scrolls'
];

$catIcons = [
    0 => 'fa-khanda', 1 => 'fa-hammer', 2 => 'fa-gavel', 3 => 'fa-location-arrow',
    4 => 'fa-bullseye', 5 => 'fa-wand-magic-sparkles', 6 => 'fa-shield-halved', 7 => 'fa-helmet-safety',
    8 => 'fa-shirt', 9 => 'fa-columns', 10 => 'fa-hand-fist', 11 => 'fa-boot',
    12 => 'fa-feather-pointed', 13 => 'fa-paw', 14 => 'fa-gem', 15 => 'fa-scroll'
];

// --- HTML OUTPUT ---

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';

echo '<style>
    /* CSS - Manteniendo estética original */
    .webshop-container { display: flex; flex-direction: column; gap: 15px; height: calc(100vh - 150px); font-family: "Segoe UI", sans-serif; position: relative; }
    .ws-header-card { background: #0d1117; border: 1px solid #30363d; border-radius: 6px; padding: 15px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .currency-selector-box { display: flex; align-items: center; gap: 10px; }
    .currency-label { color: #8b949e; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-select-dark { background-color: #161b22; border: 1px solid #30363d; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 13px; outline: none; transition: 0.2s; }
    
    .nav-tabs-custom { display: flex; gap: 5px; border-bottom: 1px solid #30363d; padding-bottom: 0; align-items: flex-end; }
    .nav-btn { background: #161b22; color: #8b949e; border: 1px solid #30363d; border-bottom: none; padding: 10px 25px; cursor: pointer; font-weight: 600; font-size: 13px; border-radius: 6px 6px 0 0; transition: all 0.2s; position: relative; top: 1px; }
    .nav-btn:hover { background: #21262d; color: #c9d1d9; }
    .nav-btn.active { background: #0d1117; color: #f39c12; border-color: #30363d; border-bottom: 1px solid #0d1117; }
    .nav-btn.active i { color: #f39c12; }
    .nav-btn-special { margin-left: auto; background: #1a2632; color: #58a6ff; border-color: #30363d; }
    .nav-btn-special.active { background: #0d1117; color: #58a6ff; border-bottom: 1px solid #0d1117; }
    .nav-btn-special i { color: #58a6ff; }

    .ws-workspace { display: flex; gap: 15px; flex: 1; overflow: hidden; padding-top: 5px; }
    
    .ws-sidebar-left { width: 240px; background: #0d1117; border: 1px solid #30363d; border-radius: 6px; display: flex; flex-direction: column; flex-shrink: 0; }
    .cat-header { background: #161b22; padding: 12px 15px; border-bottom: 1px solid #30363d; font-size: 11px; font-weight: 700; color: #8b949e; text-transform: uppercase; letter-spacing: 1px; }
    .cat-list { overflow-y: auto; flex: 1; padding: 8px; }
    .cat-list::-webkit-scrollbar { width: 5px; }
    .cat-list::-webkit-scrollbar-thumb { background: #30363d; border-radius: 3px; }
    .cat-btn { display: flex; align-items: center; width: 100%; text-align: left; padding: 10px 12px; background: transparent; border: none; color: #c9d1d9; font-size: 13px; cursor: pointer; border-radius: 6px; transition: 0.15s; margin-bottom: 2px; }
    .cat-btn:hover { background: #21262d; color: #fff; }
    .cat-btn.active { background: #1f6feb; color: #fff; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .cat-btn i { width: 24px; text-align: center; margin-right: 8px; opacity: 0.7; font-size: 14px; }
    .cat-btn.active i { opacity: 1; color: #fff; }

    .ws-center-panel { flex: 1; background: #0d1117; border: 1px solid #30363d; border-radius: 6px; display: flex; flex-direction: column; overflow: hidden; position: relative; }
    .center-toolbar { padding: 12px; border-bottom: 1px solid #30363d; background: #161b22; display: flex; gap: 10px; align-items: center; }
    .search-wrapper { position: relative; width: 100%; max-width: 400px; }
    .search-input { width: 100%; background: #0d1117; border: 1px solid #30363d; color: #fff; padding: 8px 12px 8px 35px; border-radius: 6px; font-size: 13px; transition: 0.2s; }
    .search-input:focus { border-color: #58a6ff; outline: none; box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.1); }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #666; font-size: 13px; }
    
    .item-grid-container { flex: 1; padding: 20px; overflow-y: auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; align-content: start; }
    .item-grid-container::-webkit-scrollbar { width: 8px; }
    .item-grid-container::-webkit-scrollbar-track { background: #0d1117; }
    .item-grid-container::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
    .item-card { background-color: #111419; border: 1px solid #30363d; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 140px; padding: 10px; cursor: pointer; transition: all 0.2s ease; position: relative; overflow: hidden; width: 100%; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
    .item-card:hover { border-color: #7d8590; background-color: #161b22; transform: translateY(-2px); }
    .item-card.selected { border: 1px solid #f39c12; background: linear-gradient(180deg, rgba(243, 156, 18, 0.15) 0%, rgba(17, 20, 25, 0) 70%), #111419; box-shadow: 0 0 15px rgba(243, 156, 18, 0.1); }
    .item-img { max-width: 80%; max-height: 70px; object-fit: contain; margin-bottom: 10px; filter: drop-shadow(0 5px 5px rgba(0,0,0,0.5)); z-index: 2; transition: transform 0.2s; }
    .item-card:hover .item-img { transform: scale(1.05); }
    .item-name { font-size: 12px; color: #8b949e; text-align: center; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-weight: 500; z-index: 2; }
    .item-card.selected .item-name { color: #f39c12; font-weight: 700; }

    .ws-sidebar-right { width: 340px; background: #0d1117; border: 1px solid #30363d; border-radius: 6px; flex-shrink: 0; display: flex; flex-direction: column; }
    .options-header { padding: 15px; border-bottom: 1px solid #30363d; background: #161b22; border-radius: 6px 6px 0 0; }
    .options-title { font-size: 13px; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px; text-transform: uppercase; }
    .options-body { padding: 15px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
    .options-body::-webkit-scrollbar { width: 5px; }
    .options-body::-webkit-scrollbar-thumb { background: #30363d; border-radius: 3px; }

    .opt-group { margin-bottom: 15px; }
    .opt-label { display: block; color: #8b949e; font-size: 11px; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; }
    .opt-row { display: flex; gap: 10px; margin-bottom: 10px; }
    .opt-col { flex: 1; }
    .switch-box { display: flex; align-items: center; justify-content: space-between; background: #161b22; padding: 8px 12px; border-radius: 4px; border: 1px solid #30363d; margin-bottom: 5px; }
    .switch-label { font-size: 12px; color: #c9d1d9; font-weight: 500; }
    .switch { position: relative; display: inline-block; width: 34px; height: 18px; margin: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #30363d; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #238636; }
    input:checked + .slider:before { transform: translateX(16px); }
    .exc-grid { display: grid; grid-template-columns: 1fr; gap: 5px; background: #161b22; padding: 10px; border-radius: 4px; border: 1px solid #30363d; }
    .exc-check { display: flex; align-items: center; font-size: 11px; color: #8b949e; cursor: pointer; padding: 4px 0; }
    .exc-check:hover { color: #fff; }
    .exc-check input { margin-right: 8px; accent-color: #f39c12; cursor: pointer; }
    .socket-row { margin-bottom: 5px; display: flex; gap: 5px; }
    .socket-type { flex: 2; font-size: 11px; padding: 4px; }
    .socket-level { flex: 1; font-size: 11px; padding: 4px; background-color: #1c2128; border: 1px solid #30363d; color: #f39c12; border-radius: 3px; text-align: center; }
    .price-box { background: #161b22; padding: 15px; border-radius: 6px; border: 1px solid #30363d; margin-top: auto; }
    .price-display { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #30363d; }
    .final-price { color: #f39c12; font-size: 18px; font-weight: 800; }
    
    /* UTILS, MODALS & TOOLTIP */
    .hidden { display: none !important; }
    .loading-overlay { display: none; position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(13, 17, 23, 0.9); align-items: center; justify-content: center; z-index: 10; flex-direction: column; backdrop-filter: blur(2px); }
    .spinner { width: 35px; height: 35px; border: 3px solid rgba(255,255,255,0.1); border-radius: 50%; border-top-color: #f39c12; animation: spin 0.8s linear infinite; margin-bottom: 10px; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .empty-select { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; opacity:0.5; }
    
    /* MODALS */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .modal-box { background: #161b22; width: 400px; border-radius: 8px; border: 1px solid #30363d; box-shadow: 0 15px 30px rgba(0,0,0,0.5); overflow: hidden; animation: fadeIn 0.2s ease-out; }
    .modal-header { background: #21262d; padding: 15px 20px; border-bottom: 1px solid #30363d; font-size: 16px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 10px; }
    .modal-header i { color: #f39c12; }
    .modal-body { padding: 25px 20px; text-align: center; color: #c9d1d9; }
    .modal-subtext { display: block; margin-top: 5px; font-size: 12px; color: #8b949e; }
    .modal-footer { background: #0d1117; padding: 15px 20px; border-top: 1px solid #30363d; display: flex; justify-content: flex-end; gap: 10px; }
    .btn-modal-cancel { background: transparent; border: 1px solid #30363d; color: #8b949e; padding: 8px 16px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
    .btn-modal-cancel:hover { background: #21262d; color: #fff; }
    .btn-modal-confirm { background: #b91c1c; border: 1px solid #b91c1c; color: #fff; padding: 8px 16px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; }
    .btn-modal-confirm:hover { background: #dc2626; }
    .btn-modal-ok { background: #1a4d2e; border: 1px solid #00ff88; color: #00ff88; padding: 8px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; }
    .btn-modal-ok:hover { background: #238636; color: #fff; }
    
    /* TOOLTIP */
    #user-tooltip { position: fixed; display: none; z-index: 100000; background: rgba(0, 0, 0, 0.95); border: 1px solid #444; padding: 12px; min-width: 220px; max-width: 250px; color: #fff; font-size: 12px; line-height: 1.4; box-shadow: 0 0 15px rgba(0,0,0,0.8); border-radius: 4px; pointer-events: none; text-align: center; font-family: Tahoma, sans-serif; }
    .ut-name-exc { color: #00ff00; font-weight: bold; font-size: 13px; }
    .ut-name-common { color: #fff; font-weight: bold; font-size: 13px; }
    .ut-level { color: #f39c12; font-weight: bold; }
    .ut-exc { color: #00ff00; display: block; } .ut-opt { color: #9aadcd; display: block; } 
    .ut-sock { color: #cca4e0; display: block; } .ut-harm { color: #ffff00; display: block; } .ut-380 { color: #d000d0; display: block; }
    .ut-skill { color: #9aadcd; display: block; } .ut-luck { color: #9aadcd; display: block; } .ut-price { border-top: 1px solid #444; margin-top: 8px; padding-top: 5px; color: #ffd200; font-weight: bold; font-size: 11px; }
</style>';

echo '<div class="container-fluid webshop-container">';
    
    // Header Card
    echo '<div class="ws-header-card">';
        echo '<div class="currency-selector-box">';
            echo '<span class="currency-label"><i class="fa fa-coins" style="color:#f39c12; margin-right:5px;"></i> Estás vendiendo por:</span>';
            echo '<select id="currencySelect" class="form-select-dark">';
                $hasCurrency = false;
                for($i=1; $i<=3; $i++){
                    $isActive = getSet("active_coin_$i", $settings);
                    $colName = getSet("coin_{$i}_column", $settings);
                    if($isActive == 'on' && !empty($colName)){
                        $hasCurrency = true;
                        echo '<option value="'.$colName.'">'.$colName.'</option>';
                    }
                }
                if(!$hasCurrency){
                    echo '<option value="credits">Créditos (Default)</option>';
                }
            echo '</select>';
        echo '</div>';
        echo '<div style="font-size: 12px; color: #666;">';
            echo '<span style="background: #238636; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; margin-right: 5px;">ON</span> Módulo Activo';
        echo '</div>';
    echo '</div>';

    // Tabs
    echo '<div class="nav-tabs-custom">';
        echo '<button class="nav-btn active" onclick="switchTab(\'individual\')" id="tab_individual"><i class="fa fa-gem"></i> Items Individuales</button>';
        echo '<button class="nav-btn" onclick="switchTab(\'packs\')" id="tab_packs"><i class="fa fa-box-open"></i> Packs de Items</button>';
        echo '<button class="nav-btn nav-btn-special" onclick="switchTab(\'webshop\')" id="tab_webshop"><i class="fa fa-shopping-cart"></i> WebShop</button>';
    echo '</div>';

    // VIEW INDIVIDUAL
    echo '<div id="view_individual" class="ws-workspace">';
        
        // Sidebar Left
        echo '<div class="ws-sidebar-left">';
            echo '<div class="cat-header">Categorías</div>';
            echo '<div class="cat-list">';
                foreach($categories as $id => $name) {
                    $icon = isset($catIcons[$id]) ? $catIcons[$id] : 'fa-dot-circle';
                    echo '<button class="cat-btn" onclick="loadCategory(' . $id . ', this)">';
                    echo '<i class="fa-solid ' . $icon . '"></i> ' . $name;
                    echo '</button>';
                }
            echo '</div>';
        echo '</div>';

        // Center Panel
        echo '<div class="ws-center-panel">';
            echo '<div class="center-toolbar">';
                echo '<div class="search-wrapper">';
                    echo '<i class="fa fa-search search-icon"></i>';
                    echo '<input type="text" id="itemSearch" class="search-input" placeholder="Buscar item...">';
                echo '</div>';
                echo '<div style="margin-left: auto; font-size: 12px; color: #666;"><span id="itemCount">0</span> items</div>';
            echo '</div>';
            echo '<div class="item-grid-container" id="itemGrid">';
                echo '<div style="grid-column: 1 / -1; text-align: center; margin-top: 80px; color: #444;">';
                    echo '<i class="fa fa-arrow-left" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i><br>';
                    echo '<p style="font-size: 14px;">Selecciona una categoría.</p>';
                echo '</div>';
            echo '</div>';
            echo '<div class="loading-overlay" id="gridLoader"><div class="spinner"></div><span style="color:#fff; font-size:12px; font-weight:600;">CARGANDO...</span></div>';
        echo '</div>';

        // Sidebar Right (Options)
        echo '<div class="ws-sidebar-right">';
            echo '<div class="options-header"><h4 class="options-title"><i class="fa fa-cogs" style="color:#8b949e;"></i> Configuración</h4></div>';
            echo '<div class="options-body" id="optionsBody">';
                echo '<div class="empty-select">';
                    echo '<i class="fa fa-mouse-pointer" style="font-size: 40px; margin-bottom: 15px; color: #30363d;"></i>';
                    echo '<p style="margin: 0; font-size: 14px; color: #666;">Selecciona un item para<br>ver sus opciones.</p>';
                echo '</div>';
            echo '</div>';
        echo '</div>';

    echo '</div>';

    // VIEW PACKS (Placeholder)
    echo '<div id="view_packs" class="ws-workspace hidden" style="align-items: center; justify-content: center; background: #0d1117; border: 1px solid #30363d; border-radius: 6px;">';
        echo '<div style="text-align: center; color: #8b949e;">';
            echo '<i class="fa fa-box-open" style="font-size: 60px; margin-bottom: 20px; opacity: 0.2;"></i>';
            echo '<h3>Gestor de Packs</h3><p>Próximamente...</p>';
        echo '</div>';
    echo '</div>';

    // VIEW WEBSHOP (Inventory)
    echo '<div id="view_webshop" class="ws-workspace hidden">';
        echo '<div class="ws-sidebar-left">';
            echo '<div class="cat-header">Filtrar Shop</div>';
            echo '<div class="cat-list">';
                echo '<button class="cat-btn active" onclick="filterShopCategory(-1, this)"><i class="fa fa-layer-group"></i> Todos</button>';
                foreach($categories as $id => $name) {
                    $icon = isset($catIcons[$id]) ? $catIcons[$id] : 'fa-dot-circle';
                    echo '<button class="cat-btn" onclick="filterShopCategory(' . $id . ', this)">';
                    echo '<i class="fa-solid ' . $icon . '"></i> ' . $name;
                    echo '</button>';
                }
            echo '</div>';
        echo '</div>';
        
        echo '<div class="ws-center-panel" style="border-radius: 6px;">';
            echo '<div class="center-toolbar">';
                echo '<span style="font-weight:bold; color:#fff; font-size:13px;"><i class="fa fa-shopping-cart"></i> Items en Venta</span>';
                echo '<div style="margin-left: auto; font-size: 12px; color: #666;">Clic derecho para eliminar</div>';
            echo '</div>';
            echo '<div class="item-grid-container" id="shopGrid"></div>';
            echo '<div class="loading-overlay" id="shopLoader"><div class="spinner"></div><span style="color:#fff; font-size:12px; font-weight:600;">CARGANDO SHOP...</span></div>';
        echo '</div>';
    echo '</div>';

echo '</div>'; // End Container

// Modals
echo '<div id="user-tooltip"></div>';
echo '<div id="customDeleteModal" class="modal-overlay" style="display:none;">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header"><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</div>';
        echo '<div class="modal-body"><p>¿Estás seguro de que deseas eliminar este item del Shop?</p><span class="modal-subtext">Esta acción no se puede deshacer.</span></div>';
        echo '<div class="modal-footer"><button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancelar</button><a href="#" id="btnConfirmDelete" class="btn-modal-confirm">Eliminar</a></div>';
    echo '</div>';
echo '</div>';

echo '<div id="customSuccessModal" class="modal-overlay" style="display:none;">';
    echo '<div class="modal-box">';
        echo '<div class="modal-header"><i class="fa fa-check-circle" style="color: #238636;"></i> Item Agregado</div>';
        echo '<div class="modal-body"><p>El item se ha agregado correctamente al WebShop.</p></div>';
        echo '<div class="modal-footer"><button class="btn-modal-ok" onclick="closeSuccessModal()">OK</button></div>';
    echo '</div>';
echo '</div>';

// JavaScript Logic
echo '<script>
    let currentItems = [];
    let selectedItem = null;
    let shopItemsData = [];
    let itemToDelete = null;

    // HARMONY DEFINITIONS
    const harmonyOpts = {
        1: [ // Weapons
            {v:1, n:"Min Attack Increase", minLvl:0, vals:[2,3,4,5,6,7,9,11,12,14,15,16,17,20,20,20]},
            {v:2, n:"Max Attack Increase", minLvl:0, vals:[3,4,5,6,7,8,10,12,14,17,20,23,26,29,29,29]},
            {v:3, n:"Req. Strength Decrease", minLvl:0, vals:[6,8,10,12,14,16,20,23,26,29,32,35,37,40,40,40]},
            {v:4, n:"Req. Agility Decrease", minLvl:0, vals:[6,8,10,12,14,16,20,23,26,29,32,35,37,40,40,40]},
            {v:5, n:"Attack Increase (Min,Max)", minLvl:6, vals:[0,0,0,0,0,0,7,8,9,11,12,14,16,19,19,19]},
            {v:6, n:"Critical Dmg Increase", minLvl:6, vals:[0,0,0,0,0,0,12,14,16,18,20,22,24,30,30,30]},
            {v:7, n:"Skill Attack Increase", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,12,14,16,18,22,22,22]},
            {v:8, n:"Attack Success Rate (PVP)", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,5,7,9,11,14,14,14]},
            {v:9, n:"SD Decrement Increase", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,3,5,7,9,10,10,10]},
            {v:10, n:"SD Bypass Rate Increase", minLvl:13, vals:[0,0,0,0,0,0,0,0,0,0,0,0,0,10,10,10]}
        ],
        5: [ // Staffs
            {v:1, n:"Wizardry Increase", minLvl:0, vals:[6,8,10,12,14,16,17,18,19,21,23,25,27,31,31,31]},
            {v:2, n:"Req. Strength Decrease", minLvl:0, vals:[6,8,10,12,14,16,20,23,26,29,32,35,37,40,40,40]},
            {v:3, n:"Req. Agility Decrease", minLvl:0, vals:[6,8,10,12,14,16,20,23,26,29,32,35,37,40,40,40]},
            {v:4, n:"Skill Attack Increase", minLvl:6, vals:[0,0,0,0,0,0,7,10,13,16,19,22,25,30,30,30]},
            {v:5, n:"Critical Dmg Increase", minLvl:6, vals:[0,0,0,0,0,0,10,12,14,16,18,20,22,28,28,28]},
            {v:6, n:"SD Decrement Increase", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,4,6,8,10,13,13,13]},
            {v:7, n:"Attack Success Rate (PVP)", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,5,7,9,11,14,14,14]},
            {v:8, n:"SD Bypass Rate Increase", minLvl:13, vals:[0,0,0,0,0,0,0,0,0,0,0,0,0,15,15,15]}
        ],
        6: [ // Defense
            {v:1, n:"Defense Increase", minLvl:0, vals:[3,4,5,6,7,8,10,12,14,16,19,20,22,25,25,25]},
            {v:2, n:"Max AG Increase", minLvl:0, vals:[0,0,0,4,6,8,10,12,14,16,18,20,22,25,25,25]},
            {v:3, n:"Max HP Increase", minLvl:0, vals:[0,0,0,7,9,11,13,15,17,19,21,23,25,30,30,30]},
            {v:4, n:"Life Auto Increment", minLvl:0, vals:[0,0,0,0,0,0,0,1,2,3,4,5,6,7,8,8,8]},
            {v:5, n:"Mana Auto Increment", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,1,2,3,4,5,5,5]},
            {v:6, n:"Defense Success Rate (PVP)", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,3,4,5,6,8,8,8]},
            {v:7, n:"Damage Decrement", minLvl:9, vals:[0,0,0,0,0,0,0,0,0,3,4,5,6,7,7,7]},
            {v:8, n:"SD Ratio Increase", minLvl:13, vals:[0,0,0,0,0,0,0,0,0,0,0,0,0,5,5,5]}
        ]
    };

    const socketSeeds = [
        {v:0, n:"Fire (Level based Dmg)"}, {v:1, n:"Fire (Attack Speed)"}, {v:2, n:"Fire (Max Atk/Wiz)"}, 
        {v:3, n:"Fire (Min Atk/Wiz)"}, {v:4, n:"Fire (Atk/Wiz)"}, {v:5, n:"Fire (AG Cost Dec)"},
        {v:10, n:"Water (Block Rate)"}, {v:11, n:"Water (Defense)"}, {v:12, n:"Water (Shield Prot)"}, 
        {v:13, n:"Water (Dmg Reduct)"}, {v:14, n:"Water (Reflect)"},
        {v:16, n:"Ice (Mob Kill HP)"}, {v:17, n:"Ice (Mob Kill MP)"}, {v:18, n:"Ice (Skill Atk)"}, 
        {v:19, n:"Ice (Attack Rate)"}, {v:20, n:"Ice (Durability)"},
        {v:21, n:"Wind (Auto Life Rec)"}, {v:22, n:"Wind (Max Life)"}, {v:23, n:"Wind (Max Mana)"}, 
        {v:24, n:"Wind (Auto Mana Rec)"}, {v:25, n:"Wind (Max AG)"}, {v:26, n:"Wind (AG Value)"},
        {v:29, n:"Light (Exc Dmg)"}, {v:30, n:"Light (Exc Rate)"}, {v:31, n:"Light (Crit Dmg)"}, 
        {v:32, n:"Light (Crit Rate)"}, 
        {v:36, n:"Earth (Health)"}
    ];

    function switchTab(tab) {
        document.querySelectorAll(".nav-btn").forEach(b => b.classList.remove("active"));
        document.querySelectorAll(".nav-btn-special").forEach(b => b.classList.remove("active"));
        const btn = document.getElementById("tab_" + tab);
        if(btn) btn.classList.add("active");
        document.getElementById("view_individual").classList.add("hidden");
        document.getElementById("view_packs").classList.add("hidden");
        document.getElementById("view_webshop").classList.add("hidden");
        document.getElementById("view_" + tab).classList.remove("hidden");
        if(tab === "webshop") loadShopInventory();
    }

    function loadCategory(catId, btn) {
        document.querySelectorAll(".cat-btn").forEach(b => b.classList.remove("active"));
        if(btn) btn.classList.add("active");
        document.getElementById("gridLoader").style.display = "flex";
        fetch("index.php?module=my_plugins&load_plugin=webshop&ajax=load_items&category=" + catId)
            .then(r => r.json())
            .then(data => {
                document.getElementById("gridLoader").style.display = "none";
                if(data.status === "success") { currentItems = data.data; renderItems(currentItems); }
            });
    }

    function renderItems(items) {
        const grid = document.getElementById("itemGrid");
        document.getElementById("itemCount").innerText = items.length;
        grid.innerHTML = "";
        const imgBase = "' . __BASE_URL__ . '"; 
        items.forEach(item => {
            const card = document.createElement("div");
            card.className = "item-card";
            card.onclick = function() { selectItem(this, item); };
            const img = document.createElement("img");
            img.src = imgBase + item.image;
            img.className = "item-img";
            img.onerror = function() { this.src = imgBase + "img/items/no_image.gif"; this.style.opacity = "0.3"; };
            const name = document.createElement("div");
            name.className = "item-name";
            name.innerText = item.name;
            card.appendChild(img); card.appendChild(name);
            grid.appendChild(card);
        });
    }

    function selectItem(cardElement, itemData) {
        document.querySelectorAll(".item-card").forEach(c => c.classList.remove("selected"));
        cardElement.classList.add("selected");
        selectedItem = itemData;
        const imgBase = "' . __BASE_URL__ . '"; 

        let excLabels = [];
        if(itemData.group <= 5) excLabels = ["Exc Damage Rate 10%", "Increase Dmg +Level/20", "Increase Dmg +2%", "Attack Speed +7", "Life/8", "Mana/8"];
        else if (itemData.group >= 6 && itemData.group <= 11) excLabels = ["Increase Zen 40%", "Defense Success Rate 10%", "Reflect Damage 5%", "Damage Decrease 4%", "Increase Mana 4%", "Increase HP 4%"];
        else if (itemData.group == 12) excLabels = ["Ignore Defense 5%", "Return Attack 5%", "Life Recovery 5%", "Mana Recovery 5%", "Ignore Defense (Alt)", "Return (Alt)"];
        else excLabels = ["Option 1", "Option 2", "Option 3", "Option 4", "Option 5", "Option 6"];

        let socketHtml = "<option value=\'255\'>Sin Socket</option><option value=\'254\'>Ranura Vacía</option>";
        socketSeeds.forEach(s => { socketHtml += `<option value="${s.v}">${s.n}</option>`; });

        let lvlOpts = ""; for(let j=1; j<=5; j++) lvlOpts += `<option value="${j-1}">Lvl ${j}</option>`;

        const panel = document.getElementById("optionsBody");
        panel.innerHTML = `
            <div style="text-align:center; padding-bottom:15px; border-bottom:1px solid #30363d; margin-bottom:15px;">
                <img src="${imgBase + itemData.image}" style="max-height:50px;">
                <h5 style="color:#fff; font-size:13px; margin:5px 0 0 0;">${itemData.name}</h5>
            </div>
            <div class="opt-row">
                <div class="opt-col"><label class="opt-label">Nivel</label>
                    <select id="opt_level" class="form-select-dark" style="width:100%;" onchange="updateHarmonyOptions(); calcPrice();">
                        <option value="0">+0</option><option value="9">+9</option><option value="11">+11</option><option value="13">+13</option><option value="15" selected>+15</option>
                    </select>
                </div>
                <div class="opt-col"><label class="opt-label">Opción</label>
                    <select id="opt_option" class="form-select-dark" style="width:100%;">
                        <option value="0">+0</option><option value="4">+16</option><option value="7" selected>+28</option>
                    </select>
                </div>
            </div>
            <div class="switch-box"><span class="switch-label">Suerte</span><label class="switch"><input type="checkbox" id="opt_luck" checked onchange="calcPrice()"><span class="slider"></span></label></div>
            <div class="switch-box"><span class="switch-label">Skill</span><label class="switch"><input type="checkbox" id="opt_skill" checked><span class="slider"></span></label></div>
            <div class="switch-box"><span class="switch-label" style="color:#a855f7;">Opción 380</span><label class="switch"><input type="checkbox" id="opt_380" onchange="calcPrice()"><span class="slider"></span></label></div>
            
            <div class="opt-group" style="margin-top:10px;"><label class="opt-label" style="color:#f39c12;">Excellent</label><div class="exc-grid">
                ${excLabels.map((lbl, i) => `<label class="exc-check"><input type="checkbox" class="exc-opt" value="${i}" data-name="${lbl}" onchange="calcPrice()"><span>${lbl}</span></label>`).join("")}
            </div></div>

            <div class="opt-group"><label class="opt-label" style="color:#fbbf24;">Harmony</label>
                <div style="display:flex; gap:5px; flex-direction:column;">
                    <select id="opt_harmony" class="form-select-dark" style="font-size:11px;" onchange="updateHarmonyLevels()"></select>
                    <select id="opt_harmony_lvl" class="form-select-dark" style="font-size:11px; margin-top:5px;"></select>
                </div>
            </div>
            <div class="opt-group"><label class="opt-label" style="color:#a78bfa;">Sockets</label>
                ${[1,2,3,4,5].map(i => `<div class="socket-row"><select id="opt_socket_${i}" class="socket-type form-select-dark">${socketHtml}</select><select id="opt_socket_lvl_${i}" class="socket-level form-select-dark">${lvlOpts}</select></div>`).join("")}
            </div>

            <div class="price-box"><label class="opt-label">Precio</label><input type="number" id="base_price" class="form-control-dark" value="0" placeholder="0" onkeyup="calcPrice()"><div class="price-display"><span style="color:#8b949e; font-size:12px;">FINAL:</span><span class="final-price" id="final_price">0</span></div></div>
            <button class="btn-save-green" onclick="addToShop()" style="width:100%; margin-top:15px; padding:12px; font-size:13px;"><i class="fa fa-plus-circle"></i> AGREGAR AL SHOP</button>
        `;
        
        updateHarmonyOptions();
        calcPrice();
    }

    function updateHarmonyOptions() {
        if(!selectedItem) return;
        const level = parseInt(document.getElementById("opt_level").value);
        let harmType = 0;
        if(selectedItem.group <= 4) harmType = 1; 
        else if(selectedItem.group == 5) harmType = 5; 
        else if(selectedItem.group >= 6 && selectedItem.group <= 11) harmType = 6;

        const selOpt = document.getElementById("opt_harmony");
        selOpt.innerHTML = "<option value=\'0\'>Sin Harmony</option>";
        
        if(harmType > 0 && harmonyOpts[harmType]) {
            harmonyOpts[harmType].forEach(opt => {
                if(level >= opt.minLvl) {
                    let o = document.createElement("option");
                    o.value = opt.v;
                    o.text = opt.n;
                    selOpt.appendChild(o);
                }
            });
        }
        updateHarmonyLevels();
    }

    function updateHarmonyLevels() {
        if(!selectedItem) return;
        const optVal = parseInt(document.getElementById("opt_harmony").value);
        const selLvl = document.getElementById("opt_harmony_lvl");
        selLvl.innerHTML = "";

        if(optVal === 0) {
            selLvl.innerHTML = "<option value=\'0\'>-</option>";
            selLvl.disabled = true;
            return;
        }
        selLvl.disabled = false;

        let harmType = 0;
        if(selectedItem.group <= 4) harmType = 1; 
        else if(selectedItem.group == 5) harmType = 5; 
        else if(selectedItem.group >= 6 && selectedItem.group <= 11) harmType = 6;

        const optionData = harmonyOpts[harmType].find(o => o.v === optVal);
        if(optionData && optionData.vals) {
            let seenValues = new Set();
            optionData.vals.forEach((val, idx) => {
                if(val > 0 && idx <= 13 && !seenValues.has(val)) {
                    seenValues.add(val);
                    let o = document.createElement("option");
                    o.value = idx; 
                    o.text = `+${val}`; 
                    selLvl.appendChild(o);
                }
            });
        }
    }

    function calcPrice() {
        if(!selectedItem) return;
        let base = parseInt(document.getElementById("base_price").value) || 0;
        let lvl = parseInt(document.getElementById("opt_level").value);
        let luck = document.getElementById("opt_luck").checked ? 50 : 0;
        let op380 = document.getElementById("opt_380").checked ? 200 : 0;
        let exc = document.querySelectorAll(".exc-opt:checked").length * 100;
        document.getElementById("final_price").innerText = (base + (lvl*10) + luck + op380 + exc).toLocaleString();
    }

    function addToShop() {
        if(!selectedItem) return;
        const btn = document.querySelector(".btn-save-green");
        btn.innerHTML = "<i class=\'fa fa-spinner fa-spin\'></i> Guardando...";
        
        let excOpts = [];
        document.querySelectorAll(".exc-opt:checked").forEach(e => excOpts.push({id: e.value, name: e.getAttribute("data-name")}));
        
        let sockets = [];
        for(let i=1; i<=5; i++) {
            let sVal = parseInt(document.getElementById(`opt_socket_${i}`).value);
            let sLvl = parseInt(document.getElementById(`opt_socket_lvl_${i}`).value); 
            let sTxt = document.getElementById(`opt_socket_${i}`).options[document.getElementById(`opt_socket_${i}`).selectedIndex].text;
            let finalVal = sVal;
            if(sVal < 254) {
                finalVal = sVal + (sLvl * 50);
                sTxt += " (Lvl " + (sLvl+1) + ")";
            }
            sockets.push({val: finalVal, name: sTxt});
        }

        let harmType = parseInt(document.getElementById("opt_harmony").value);
        let harmLvl = parseInt(document.getElementById("opt_harmony_lvl").value);
        let harmTxt = document.getElementById("opt_harmony").options[document.getElementById("opt_harmony").selectedIndex].text;
        
        let finalHarmVal = 0;
        if(harmType > 0) {
            finalHarmVal = (harmType << 4) | harmLvl;
            let harmValTxt = document.getElementById("opt_harmony_lvl").options[document.getElementById("opt_harmony_lvl").selectedIndex].text;
            harmTxt += " " + harmValTxt;
        }

        const payload = new FormData();
        payload.append("ajax_action", "save_item");
        payload.append("group", selectedItem.group);
        payload.append("id", selectedItem.id);
        payload.append("name", selectedItem.name);
        payload.append("image", selectedItem.image);
        payload.append("level", document.getElementById("opt_level").value);
        payload.append("option", document.getElementById("opt_option").value);
        payload.append("luck", document.getElementById("opt_luck").checked ? 1 : 0);
        payload.append("skill", document.getElementById("opt_skill").checked ? 1 : 0);
        payload.append("op380", document.getElementById("opt_380").checked ? 1 : 0);
        payload.append("harmony", JSON.stringify({val: finalHarmVal, name: harmTxt}));
        payload.append("exc_opts", JSON.stringify(excOpts));
        payload.append("sockets", JSON.stringify(sockets));
        payload.append("price", document.getElementById("final_price").innerText.replace(/,/g, ""));
        payload.append("currency", document.getElementById("currencySelect").value);

        fetch(window.location.href, { method: "POST", body: payload })
        .then(r => r.json())
        .then(res => {
            if(res.status === "success") {
                openSuccessModal();
                btn.innerHTML = "<i class=\'fa fa-check\'></i> Agregado";
            } else {
                alert("Error al guardar.");
            }
        });
    }

    function loadShopInventory() {
        const loader = document.getElementById("shopLoader");
        const grid = document.getElementById("shopGrid");
        loader.style.display = "flex";
        fetch("index.php?module=my_plugins&load_plugin=webshop&ajax=load_shop")
        .then(r => r.json())
        .then(res => {
            loader.style.display = "none";
            if(res.status === "success") {
                shopItemsData = res.data;
                renderShopGrid(shopItemsData);
            }
        });
    }

    function filterShopCategory(catId, btn) {
        document.querySelectorAll("#view_webshop .cat-btn").forEach(b => b.classList.remove("active"));
        if(btn) btn.classList.add("active");
        if(catId == -1) renderShopGrid(shopItemsData);
        else {
            const filtered = shopItemsData.filter(i => i.group == catId);
            renderShopGrid(filtered);
        }
    }

    function renderShopGrid(items) {
        const grid = document.getElementById("shopGrid");
        grid.innerHTML = "";
        const imgBase = "' . __BASE_URL__ . '"; 
        if(items.length === 0) {
            grid.innerHTML = "<div style=\'grid-column:1/-1; color:#666; text-align:center; margin-top:50px;\'>No hay items en el Shop.</div>";
            return;
        }
        items.forEach(item => {
            const card = document.createElement("div");
            card.className = "item-card";
            card.onmouseenter = function(e) { showUserTooltip(e, item); };
            card.onmousemove = function(e) { moveUserTooltip(e); };
            card.onmouseleave = function() { hideUserTooltip(); };
            card.oncontextmenu = function(e) { e.preventDefault(); openDeleteModal(item.uid); };
            const img = document.createElement("img");
            img.src = imgBase + item.image;
            img.className = "item-img";
            img.onerror = function() { this.src = imgBase + "img/items/no_image.gif"; this.style.opacity = "0.3"; };
            const name = document.createElement("div");
            name.className = "item-name";
            name.innerHTML = `${item.name} <span style="color:#f39c12;">+${item.level}</span>`;
            const price = document.createElement("div");
            price.style.fontSize = "11px"; price.style.color = "#fff"; price.style.marginTop = "5px";
            price.innerHTML = `${parseInt(item.price).toLocaleString()} ${item.currency}`;
            card.appendChild(img); card.appendChild(name); card.appendChild(price);
            grid.appendChild(card);
        });
    }

    function openDeleteModal(uid) {
        itemToDelete = uid;
        document.getElementById("customDeleteModal").style.display = "flex";
        document.getElementById("btnConfirmDelete").onclick = function() {
            const payload = new FormData();
            payload.append("ajax_action", "delete_item");
            payload.append("uid", itemToDelete);
            fetch(window.location.href, { method: "POST", body: payload })
            .then(r => r.json())
            .then(res => { if(res.status === "success") { loadShopInventory(); closeDeleteModal(); } });
        };
    }
    function closeDeleteModal() { document.getElementById("customDeleteModal").style.display = "none"; itemToDelete = null; }
    function openSuccessModal() { document.getElementById("customSuccessModal").style.display = "flex"; }
    function closeSuccessModal() { document.getElementById("customSuccessModal").style.display = "none"; }
    window.onclick = function(event) {
        if (event.target == document.getElementById("customDeleteModal")) closeDeleteModal();
        if (event.target == document.getElementById("customSuccessModal")) closeSuccessModal();
    }

    const tooltip = document.getElementById("user-tooltip");
    function showUserTooltip(e, item) {
        let isExc = (item.exc_opts && item.exc_opts.length > 0);
        let nameClass = isExc ? "ut-name-exc" : "ut-name-common";
        let prefix = isExc ? "Excellent " : "";
        let levelTxt = (item.level > 0) ? " <span class=\'ut-level\'>+" + item.level + "</span>" : "";
        let content = `<div class="${nameClass}">${prefix}${item.name}${levelTxt}</div>`;
        if(item.op380 == 1) content += `<span class="ut-380">SD Recovery Rate +20 (380 Opt)</span>`;
        if(item.harmony && item.harmony.val != 0) content += `<span class="ut-harm">${item.harmony.name}</span>`;
        if(item.skill == 1) content += `<span class="ut-skill">Skill Attack Power</span>`;
        if(item.luck == 1) {
            content += `<span class="ut-luck">Luck (success rate +25%)</span>`;
            content += `<span class="ut-luck">Critical damage rate +5%</span>`;
        }
        if(item.option > 0) {
            let addName = (item.group <= 5 || item.group == 12) ? "Additional Damage" : "Additional Defense";
            content += `<span class="ut-opt">${addName} +${item.option*4}</span>`;
        }
        if(item.exc_opts && item.exc_opts.length > 0) { item.exc_opts.forEach(ex => { content += `<span class="ut-exc">${ex.name}</span>`; }); }
        if(item.sockets && item.sockets.length > 0) {
            let hasSock = false;
            let sockHtml = "";
            item.sockets.forEach((s, idx) => {
                if(s.val != 255) {
                    hasSock = true;
                    let color = (s.val == 254) ? "#aaa" : "#d698fd";
                    sockHtml += `<span style="color:${color}; display:block;">Socket ${idx+1}: ${s.name}</span>`;
                }
            });
            if(hasSock) content += `<div style="margin-top:5px; border-top:1px solid #444; padding-top:5px;">${sockHtml}</div>`;
        }
        content += `<div class="ut-price">Precio: ${parseInt(item.price).toLocaleString()} ${item.currency}</div>`;
        tooltip.innerHTML = content;
        tooltip.style.display = "block";
        moveUserTooltip(e);
    }
    function moveUserTooltip(e) {
        const x = e.clientX + 15;
        const y = e.clientY + 15;
        tooltip.style.left = x + "px";
        tooltip.style.top = y + "px";
    }
    function hideUserTooltip() { tooltip.style.display = "none"; }

    document.getElementById("itemSearch").addEventListener("keyup", function(e) {
        const term = e.target.value.toLowerCase();
        const filtered = currentItems.filter(item => item.name.toLowerCase().includes(term));
        renderItems(filtered);
    });
</script>';
?>