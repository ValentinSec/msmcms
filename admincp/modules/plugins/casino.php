<?php
/**
 * Casino Goblin Manager - LootBox System
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

// 1. CONFIGURACIÓN Y DEPENDENCIAS
$basePath = __DIR__ . '/../../../includes/config/';
$itemsManager = $basePath . 'items_manager.php';
$casinoFile = $basePath . 'casino_boxes.json';
$configFile = $basePath . 'casino_config.json';

if (file_exists($itemsManager)) require_once($itemsManager);

$moduleUrl = "index.php?module=my_plugins&load_plugin=casino";

// Nombres de Opciones Excellent (Fallback/Estructura base)
$excNames = [ 1 => 'Mana/Zen', 2 => 'HP/DefRate', 4 => 'Speed/Reflect', 8 => '2%/DmgDec', 16 => 'Lvl/ManaInc', 32 => 'ExcDmg/HPInc' ];

// Lista Completa de Sockets
$socketSeeds = [
    255 => 'No Slot', 254 => 'Empty Slot',
    0 => 'Fire (Level based Dmg)', 1 => 'Fire (Attack Speed)', 2 => 'Fire (Max Atk/Wiz)', 3 => 'Fire (Min Atk/Wiz)', 4 => 'Fire (Atk/Wiz)', 5 => 'Fire (AG Cost Dec)',
    10 => 'Water (Block Rate)', 11 => 'Water (Defense)', 12 => 'Water (Shield Prot)', 13 => 'Water (Dmg Reduct)', 14 => 'Water (Reflect)',
    16 => 'Ice (Mob Kill HP)', 17 => 'Ice (Mob Kill MP)', 18 => 'Ice (Skill Atk)', 19 => 'Ice (Attack Rate)', 20 => 'Ice (Durability)',
    21 => 'Wind (Auto Life Rec)', 22 => 'Wind (Max Life)', 23 => 'Wind (Max Mana)', 24 => 'Wind (Auto Mana Rec)', 25 => 'Wind (Max AG)', 26 => 'Wind (AG Value)',
    29 => 'Light (Exc Dmg)', 30 => 'Light (Exc Rate)', 31 => 'Light (Crit Dmg)', 32 => 'Light (Crit Rate)',
    36 => 'Earth (Health)'
];

// 2. AJAX HANDLER
if(isset($_GET['ajax']) && $_GET['ajax'] == 'load_items_casino') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $itemsFound = [];
    for ($i = 0; $i < 512; $i++) {
        $name = ItemsManager::getItemName($category, $i);
        if ($name !== "Unknown Item" && !empty($name)) {
            $itemsFound[] = ['id' => $i, 'name' => $name];
        }
    }
    echo json_encode(['status' => 'success', 'data' => $itemsFound]);
    exit;
}

// 3. LÓGICA DE DATOS
$boxes = file_exists($casinoFile) ? json_decode(file_get_contents($casinoFile), true) : [];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    'title' => 'El Alquimista del Caos',
    'desc' => 'Bienvenido, viajero. A cambio de monedas, puedes abrir uno de mis cofres antiguos. Cada cofre contiene tesoros de distinto valor. ¡La suerte está echada!'
];

// GUARDAR CONFIGURACIÓN GENERAL
if(isset($_POST['save_config'])) {
    $config['title'] = $_POST['casino_title'];
    $config['desc'] = $_POST['casino_desc'];
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=config_saved");
    exit;
}

// Crear Caja
if(isset($_POST['create_box'])) {
    $imgName = $_POST['box_img'];
    if(strpos($imgName, '/') === false) $imgName = 'img/boxes/' . $imgName;

    $newBox = [
        'id' => uniqid(),
        'name' => $_POST['box_name'],
        'cost' => (int)$_POST['box_cost'],
        'currency' => $_POST['box_currency'],
        'img' => $imgName,
        'rewards' => [] 
    ];
    $boxes[] = $newBox;
    file_put_contents($casinoFile, json_encode($boxes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=created");
    exit;
}

// Agregar Premio
if(isset($_POST['add_reward'])) {
    $boxId = $_POST['target_box_id'];
    $group = (int)$_POST['item_group'];
    $itemId = (int)$_POST['item_id'];
    $name = ItemsManager::getItemName($group, $itemId);

    $excOpts = [];
    if(isset($_POST['exc_opt'])) { foreach($_POST['exc_opt'] as $val) { $excOpts[] = (int)$val; } }

    // Procesar Sockets
    $sockets = [];
    for($i=1; $i<=5; $i++) {
        $sType = (int)$_POST['socket_'.$i];
        $sLvl = (int)$_POST['socket_lvl_'.$i];
        
        if($sType == 255 || $sType == 254) {
            $sockets[] = $sType;
        } else {
            $sockets[] = $sType + ($sLvl * 50);
        }
    }

    // Procesar Harmony
    $harmType = (int)$_POST['harmony_type'];
    $harmLvl = (int)$_POST['harmony_level'];
    $finalHarm = ($harmType > 0) ? (($harmType << 4) | $harmLvl) : 0;

    foreach($boxes as &$box) {
        if($box['id'] === $boxId) {
            $box['rewards'][] = [
                'id' => uniqid(),
                'name' => $name,
                'group' => $group,
                'item_id' => $itemId,
                'level' => (int)$_POST['item_level'],
                'skill' => isset($_POST['item_skill']) ? 1 : 0,
                'luck' => isset($_POST['item_luck']) ? 1 : 0,
                'option' => (int)$_POST['item_option'],
                'exc_opts' => $excOpts,
                'opt_380' => isset($_POST['item_380']) ? 1 : 0,
                'harmony_full' => $finalHarm,
                'harmony_type' => $harmType,
                'harmony_level' => $harmLvl,
                'sockets' => $sockets,
                'chance' => (float)$_POST['item_chance']
            ];
        }
    }
    file_put_contents($casinoFile, json_encode($boxes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=reward_added");
    exit;
}

// Eliminar
if(isset($_GET['delete_box'])) {
    $boxes = array_filter($boxes, function($b) { return $b['id'] !== $_GET['delete_box']; });
    file_put_contents($casinoFile, json_encode(array_values($boxes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=deleted");
    exit;
}
if(isset($_GET['delete_reward'])) {
    $boxId = $_GET['box'];
    $rewId = $_GET['delete_reward'];
    foreach($boxes as &$box) {
        if($box['id'] === $boxId) {
            $box['rewards'] = array_filter($box['rewards'], function($r) use ($rewId) { return $r['id'] !== $rewId; });
        }
    }
    file_put_contents($casinoFile, json_encode(array_values($boxes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: ".$moduleUrl."&status=deleted_reward");
    exit;
}

// --- RENDERIZADO HTML (PHP PURO) ---

// CSS Styles
echo '<style>
    :root { --c-bg: #0d1117; --c-card: #161b22; --c-border: #30363d; --c-accent: #f39c12; --c-text: #c9d1d9; --c-muted: #8b949e; }
    .msm-wrapper { font-family: "Segoe UI", sans-serif; color: var(--c-text); }
    .form-control-dark, .form-select-dark { background-color: #0d1117; border: 1px solid var(--c-border); color: #fff; padding: 8px 12px; border-radius: 4px; width: 100%; font-size: 13px; outline: none; transition: 0.2s; }
    .form-control-dark:focus, .form-select-dark:focus { border-color: var(--c-accent); }
    .msm-label { color: var(--c-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; display: block; }
    .create-card { background: var(--c-card); border: 1px solid var(--c-border); border-radius: 8px; padding: 20px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .cc-title { font-size: 18px; color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--c-border); padding-bottom: 10px; }
    .boxes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(600px, 1fr)); gap: 20px; }
    
    .box-card { background: var(--c-card); border: 1px solid var(--c-border); border-radius: 8px; overflow: visible; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; position: relative; z-index: 1; }
    .box-card:hover, .box-card:focus-within { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-color: #58a6ff; z-index: 100; }

    .bc-header { background: linear-gradient(90deg, rgba(22,27,34,1) 0%, rgba(33,38,45,1) 100%); padding: 15px; border-bottom: 1px solid var(--c-border); display: flex; align-items: center; justify-content: space-between; border-top-left-radius: 8px; border-top-right-radius: 8px; }
    .bc-content { padding: 15px; flex: 1; display: flex; flex-direction: column; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
    .bc-info { display: flex; align-items: center; gap: 15px; }
    .bc-img { width: 50px; height: 50px; object-fit: contain; filter: drop-shadow(0 0 5px rgba(255,255,255,0.2)); }
    .bc-title h4 { margin: 0; font-size: 16px; color: #fff; font-weight: 700; }
    .bc-cost { font-size: 12px; color: var(--c-accent); font-weight: 600; background: rgba(243, 156, 18, 0.1); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(243, 156, 18, 0.3); }
    .bc-actions a { color: #f85149; font-size: 14px; padding: 5px; opacity: 0.7; transition: 0.2s; }
    .bc-actions a:hover { opacity: 1; transform: scale(1.1); }
    
    .add-reward-form { background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; border: 1px dashed var(--c-border); margin-bottom: 15px; }
    .rewards-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .rewards-table th { text-align: left; color: var(--c-muted); padding: 8px; border-bottom: 1px solid var(--c-border); }
    .rewards-table td { padding: 8px; color: var(--c-text); border-bottom: 1px solid #21262d; vertical-align: middle; }
    .rewards-table tr:last-child td { border-bottom: none; }
    .b-prob { color: #2ea043; font-weight: 700; }
    .b-opt { font-size: 10px; color: #8b949e; display: inline-block; margin-right: 3px; background: rgba(255,255,255,0.05); padding: 1px 4px; border-radius: 3px; }
    .b-exc { font-size: 10px; color: #00ff88; display: inline-block; margin-right: 2px; }
    .b-special { font-size: 10px; color: #e74c3c; background: rgba(231, 76, 60, 0.1); padding: 1px 4px; border-radius: 3px; display: inline-block; margin-right: 2px; }
    .b-sock { font-size: 10px; color: #9b59b6; display: inline-block; margin-right: 2px; }
    
    .btn-grad { background: linear-gradient(180deg, #1f6feb, #1158c7); border: 1px solid #1f6feb; color: #fff; padding: 8px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-grad:hover { opacity: 0.9; }
    .btn-add-item { background: #238636; border: 1px solid #238636; color: #fff; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 4px; cursor: pointer; width: 100%; transition: 0.2s; }
    .btn-add-item:hover { background: #2ea043; }

    .chk-row { display: flex; gap: 15px; margin-top: 5px; flex-wrap: wrap; }
    .chk-label { font-size: 11px; cursor: pointer; display: flex; align-items: center; gap: 4px; color: #c9d1d9; }
    
    /* DROPDOWN FLOTANTE */
    .dropdown-options-box { background: #0d1117; padding: 15px; border-radius: 6px; border: 1px solid #30363d; margin-top: 5px; box-shadow: 0 15px 40px rgba(0,0,0,0.9); position: absolute; width: 100%; left: 0; z-index: 1000; }
    .exc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
    .socket-row { display: flex; gap: 5px; margin-bottom: 5px; }
    .toggle-opt-btn { background: #21262d; border: 1px solid #30363d; color: #c9d1d9; font-size: 11px; padding: 4px 8px; border-radius: 4px; cursor: pointer; width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .toggle-opt-btn:hover { background: #30363d; }
    .btn-save-cfg { background: #8e44ad; border: 1px solid #8e44ad; color: #fff; padding: 8px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; }
</style>';

echo '<div class="msm-wrapper">';

    // 1. Configuración General
    echo '<div class="create-card">';
        echo '<div class="cc-title"><i class="fa fa-cog"></i> Configuración General</div>';
        echo '<form method="post" action="' . $moduleUrl . '">';
            echo '<div class="row">';
                echo '<div class="col-md-4"><label class="msm-label">Título</label><input type="text" name="casino_title" class="form-control-dark" value="' . htmlspecialchars($config['title']) . '" required></div>';
                echo '<div class="col-md-6"><label class="msm-label">Descripción</label><input type="text" name="casino_desc" class="form-control-dark" value="' . htmlspecialchars($config['desc']) . '" required></div>';
                echo '<div class="col-md-2"><label class="msm-label">&nbsp;</label><button type="submit" name="save_config" class="btn-save-cfg"><i class="fa fa-save"></i> Guardar</button></div>';
            echo '</div>';
        echo '</form>';
    echo '</div>';

    // 2. Crear Nueva Caja
    echo '<div class="create-card">';
        echo '<div class="cc-title"><i class="fa fa-dice"></i> Crear Nueva Caja</div>';
        echo '<form method="post" action="' . $moduleUrl . '">';
            echo '<div class="row">';
                echo '<div class="col-md-3"><label class="msm-label">Nombre</label><input type="text" name="box_name" class="form-control-dark" placeholder="Ej: Cofre Legendario" required></div>';
                echo '<div class="col-md-2"><label class="msm-label">Costo</label><input type="number" name="box_cost" class="form-control-dark" value="100" required></div>';
                echo '<div class="col-md-2"><label class="msm-label">Moneda</label><select name="box_currency" class="form-select-dark"><option value="WCoinC">WCoinC</option><option value="WCoinP">WCoinP</option><option value="GoblinPoint">GoblinPoint</option><option value="Zen">Zen</option></select></div>';
                echo '<div class="col-md-3"><label class="msm-label">Imagen</label><input type="text" name="box_img" class="form-control-dark" placeholder="box_gold.png"></div>';
                echo '<div class="col-md-2"><label class="msm-label">&nbsp;</label><button type="submit" name="create_box" class="btn-grad" style="width: 100%;">Crear</button></div>';
            echo '</div>';
        echo '</form>';
    echo '</div>';

    // 3. Grid de Cajas
    echo '<div class="boxes-grid">';
        if(empty($boxes)) {
            echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #8b949e;">No hay cajas creadas.</div>';
        } else {
            foreach($boxes as $box) {
                $boxImg = (!empty($box['img'])) ? __BASE_URL__ . $box['img'] : __BASE_URL__ . 'img/boxes/no_box.png';
                
                echo '<div class="box-card">';
                    // Header Card
                    echo '<div class="bc-header">';
                        echo '<div class="bc-info">';
                            echo '<img src="' . $boxImg . '" class="bc-img" onerror="this.src=\'https://placehold.co/50x50/333/ccc?text=BOX\'">';
                            echo '<div class="bc-title"><h4>' . htmlspecialchars($box['name']) . '</h4><span class="bc-cost">' . number_format($box['cost']) . ' ' . $box['currency'] . '</span></div>';
                        echo '</div>';
                        echo '<div class="bc-actions"><a href="' . $moduleUrl . '&delete_box=' . $box['id'] . '" onclick="return confirm(\'¿Borrar caja?\')" title="Eliminar"><i class="fa fa-trash"></i></a></div>';
                    echo '</div>';

                    // Content Card
                    echo '<div class="bc-content">';
                        // Add Reward Form
                        echo '<form method="post" action="' . $moduleUrl . '" class="add-reward-form">';
                            echo '<input type="hidden" name="target_box_id" value="' . $box['id'] . '">';
                            
                            // Row 1: Item Basic
                            echo '<div class="row no-gutters">';
                                echo '<div class="col-md-3 pr-1">';
                                    echo '<label class="msm-label">Categoría</label>';
                                    echo '<select name="item_group" id="grp_' . $box['id'] . '" class="form-select-dark" onchange="loadCasinoItems(this.value, \'' . $box['id'] . '\'); checkHarmony(this.value, \'' . $box['id'] . '\'); updateExcOptions(this.value, \'' . $box['id'] . '\')" required>';
                                        echo '<option value="">...</option>';
                                        for($i=0; $i<=15; $i++) echo "<option value='$i'>Cat $i</option>";
                                    echo '</select>';
                                echo '</div>';
                                echo '<div class="col-md-4 px-1">';
                                    echo '<label class="msm-label">Item</label>';
                                    echo '<select name="item_id" id="items_' . $box['id'] . '" class="form-select-dark" required><option value="">Esperando...</option></select>';
                                echo '</div>';
                                echo '<div class="col-md-2 px-1">';
                                    echo '<label class="msm-label">Prob %</label>';
                                    echo '<input type="number" step="0.01" name="item_chance" class="form-control-dark" placeholder="5.5" required>';
                                echo '</div>';
                                echo '<div class="col-md-3 pl-1">';
                                    echo '<label class="msm-label">Base</label>';
                                    echo '<div style="display:flex; gap:5px;">';
                                        echo '<select name="item_level" id="lvl_' . $box['id'] . '" class="form-select-dark" onchange="checkHarmony(document.getElementById(\'grp_' . $box['id'] . '\').value, \'' . $box['id'] . '\')">';
                                            for($l=0;$l<=15;$l++) echo "<option value='$l'>+$l</option>";
                                        echo '</select>';
                                        echo '<select name="item_option" class="form-select-dark"><option value="0">+0</option><option value="1">+4</option><option value="2">+8</option><option value="3">+12</option><option value="4">+16</option><option value="7">+28</option></select>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</div>';

                            // Row 2: Advanced Options (Dropdown)
                            echo '<div class="row mt-2">';
                                echo '<div class="col-md-12">';
                                    echo '<div class="dropdown" style="position:relative;">';
                                        echo '<button type="button" class="toggle-opt-btn" onclick="toggleMenu(\'opts_menu_' . $box['id'] . '\')">Opciones Avanzadas <i class="fa fa-chevron-down"></i></button>';
                                        echo '<div id="opts_menu_' . $box['id'] . '" class="dropdown-options-box" style="display:none;">';
                                            
                                            // Checkboxes
                                            echo '<div class="chk-row" style="border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:10px;">';
                                                echo '<label class="chk-label"><input type="checkbox" name="item_luck"> Luck</label>';
                                                echo '<label class="chk-label"><input type="checkbox" name="item_skill"> Skill</label>';
                                                echo '<label class="chk-label" style="color:#e74c3c;"><input type="checkbox" name="item_380"> Option 380</label>';
                                            echo '</div>';

                                            // Excellent DYNAMIC
                                            echo '<label class="msm-label" style="color:#00ff88;">Opciones Excellent</label>';
                                            echo '<div class="exc-grid" style="margin-bottom:15px;">';
                                                $excValues = [32, 16, 8, 4, 2, 1]; // Ordenadas de mayor a menor jerarquía
                                                foreach($excValues as $val) {
                                                    echo '<label class="chk-label" style="transition: 0.2s;">';
                                                    echo '<input type="checkbox" name="exc_opt[]" id="exc_chk_'.$box['id'].'_'.$val.'" value="'.$val.'" disabled> ';
                                                    echo '<span id="exc_name_'.$box['id'].'_'.$val.'" style="color:#666; font-style:italic;">Esperando categoría...</span>';
                                                    echo '</label>';
                                                }
                                            echo '</div>';

                                            // Harmony
                                            echo '<label class="msm-label" style="color:#f1c40f;">Harmony (Automático por Categoría)</label>';
                                            echo '<div class="row" style="margin-bottom:15px;">';
                                                echo '<div class="col-md-8">';
                                                    echo '<select name="harmony_type" id="harm_type_' . $box['id'] . '" class="form-select-dark" onchange="updateHarmLevels(\'' . $box['id'] . '\')">';
                                                        echo '<option value="0">Sin Harmony</option>';
                                                    echo '</select>';
                                                echo '</div>';
                                                echo '<div class="col-md-4">';
                                                    echo '<select name="harmony_level" id="harm_lvl_' . $box['id'] . '" class="form-select-dark" disabled><option value="0">-</option></select>';
                                                echo '</div>';
                                            echo '</div>';

                                            // Sockets
                                            echo '<label class="msm-label" style="color:#9b59b6;">Sockets (Seed Sphere)</label>';
                                            echo '<div style="display:flex; flex-direction:column; gap:5px;">';
                                                for($s=1; $s<=5; $s++) {
                                                    echo '<div class="socket-row">';
                                                        echo '<select name="socket_' . $s . '" class="form-select-dark" style="font-size:10px; flex:2;">';
                                                            foreach($socketSeeds as $k=>$v) echo "<option value='$k'>Slot $s: $v</option>";
                                                        echo '</select>';
                                                        echo '<select name="socket_lvl_' . $s . '" class="form-select-dark" style="font-size:10px; flex:1;">';
                                                            for($sl=1; $sl<=5; $sl++) echo "<option value='" . ($sl-1) . "'>Lvl $sl</option>";
                                                        echo '</select>';
                                                    echo '</div>';
                                                }
                                            echo '</div>';

                                        echo '</div>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</div>';

                            // Button Add
                            echo '<div class="row mt-2">';
                                echo '<div class="col-12 text-right">';
                                    echo '<button type="submit" name="add_reward" class="btn-add-item"><i class="fa fa-plus"></i> AGREGAR PREMIO</button>';
                                echo '</div>';
                            echo '</div>';
                        echo '</form>';

                        // Rewards List
                        echo '<div style="flex:1; overflow-y: auto; max-height: 250px;">';
                            echo '<table class="rewards-table">';
                                echo '<thead><tr><th>Premio</th><th>Atributos</th><th>Prob.</th><th></th></tr></thead>';
                                echo '<tbody>';
                                    if(empty($box['rewards'])) {
                                        echo '<tr><td colspan="4" class="text-center" style="font-style:italic; color:#666;">Caja vacía</td></tr>';
                                    } else {
                                        foreach($box['rewards'] as $rew) {
                                            $isExc = !empty($rew['exc_opts']);
                                            $is380 = isset($rew['opt_380']) && $rew['opt_380'];
                                            $hasHarmony = isset($rew['harmony_type']) && $rew['harmony_type'] > 0;
                                            $hasSocket = isset($rew['sockets']) && count(array_filter($rew['sockets'], function($s){ return $s!=255 && $s!=254; })) > 0;
                                            
                                            $colorStyle = ($isExc) ? 'color:#00ff88;' : '';
                                            
                                            echo '<tr>';
                                                echo '<td><span style="' . $colorStyle . '">' . $rew['name'] . ' <b style="color:#fff;">+' . $rew['level'] . '</b></span></td>';
                                                echo '<td>';
                                                    if($rew['luck']) echo '<span class="b-opt">Luck</span>';
                                                    if($rew['skill']) echo '<span class="b-opt">Skill</span>';
                                                    if($rew['option'] > 0) echo '<span class="b-opt">Opt</span>';
                                                    if($is380) echo '<span class="b-special">380</span>';
                                                    if($hasHarmony) echo '<span class="b-special" style="color:#f1c40f; background:rgba(241,196,15,0.1);">Harm</span>';
                                                    if($hasSocket) echo '<span class="b-sock">Socket</span>';
                                                    if(!empty($rew['exc_opts'])) echo '<span class="b-exc" title="' . implode(', ', $rew['exc_opts']) . '">Exc(' . count($rew['exc_opts']) . ')</span>';
                                                echo '</td>';
                                                echo '<td class="b-prob">' . $rew['chance'] . '%</td>';
                                                echo '<td style="text-align:right;"><a href="' . $moduleUrl . '&box=' . $box['id'] . '&delete_reward=' . $rew['id'] . '" style="color:#f85149;"><i class="fa fa-times"></i></a></td>';
                                            echo '</tr>';
                                        }
                                    }
                                echo '</tbody>';
                            echo '</table>';
                        echo '</div>';

                    echo '</div>'; // End bc-content
                echo '</div>'; // End box-card
            }
        }
    echo '</div>'; // End grid

echo '</div>'; // End msm-wrapper

// JS FUERA DEL PHP PARA EVITAR CONFLICTOS
?>
<script>
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

// Definiciones dinámicas de Opciones Excelentes
const excDefinitions = {
    "ATTACK": {
        32: "Exc Dmg Rate +10%",
        16: "Inc Dmg +lvl/20",
        8: "Inc Dmg +2%",
        4: "Attack Speed +7",
        2: "Life after mob +life/8",
        1: "Mana after mob +mana/8"
    },
    "DEFENSE": {
        32: "Inc Max HP +4%",
        16: "Inc Max Mana +4%",
        8: "Damage Decrease +4%",
        4: "Reflect Damage +5%",
        2: "Def Success Rate +10%",
        1: "Zen after mob +40%"
    },
    "WINGS": {
        32: "-",
        16: "-",
        8: "Full Mana Rec 5%",
        4: "Full HP Rec 5%",
        2: "Return Atk 5%",
        1: "Ignore Def 5%"
    }
};

// Función para actualizar los nombres y habilitar casillas
function updateExcOptions(cat, boxId) {
    try {
        const vals = [32, 16, 8, 4, 2, 1];
        
        if (!cat || cat === "") {
            vals.forEach(val => {
                let span = document.getElementById("exc_name_" + boxId + "_" + val);
                let chk = document.getElementById("exc_chk_" + boxId + "_" + val);
                if(span) { span.innerText = "Esperando categoría..."; span.style.color = "#666"; span.style.fontStyle = "italic"; }
                if(chk) { chk.checked = false; chk.disabled = true; }
            });
            return;
        }

        let type = "DEFENSE";
        let catNum = parseInt(cat);
        if (catNum < 6) type = "ATTACK";
        else if (catNum == 12) type = "WINGS";
        
        const mapping = excDefinitions[type];
        
        vals.forEach(val => {
            let span = document.getElementById("exc_name_" + boxId + "_" + val);
            let chk = document.getElementById("exc_chk_" + boxId + "_" + val);
            
            if(span && chk) {
                let optName = mapping[val];
                if(optName && optName !== "-") {
                    span.innerText = optName;
                    span.style.color = "#c9d1d9";
                    span.style.fontStyle = "normal";
                    chk.disabled = false;
                } else {
                    span.innerText = "No aplica a este item";
                    span.style.color = "#444";
                    span.style.fontStyle = "italic";
                    chk.checked = false;
                    chk.disabled = true;
                }
            }
        });
    } catch(e) {
        console.error("Error al actualizar opciones: ", e);
    }
}

function loadCasinoItems(cat, boxId) {
    const select = document.getElementById("items_" + boxId);
    if(!cat) { select.innerHTML = '<option value="">...</option>'; return; }
    fetch("index.php?module=my_plugins&load_plugin=casino&ajax=load_items_casino&category=" + cat)
    .then(r => r.json()).then(res => {
        let html = '<option value="">Seleccione Item...</option>';
        if(res.status === "success") res.data.forEach(i => { html += `<option value="${i.id}">${i.name}</option>`; });
        select.innerHTML = html;
    }).catch(err => { select.innerHTML = '<option value="">Error</option>'; });
}

function checkHarmony(cat, boxId) {
    const lvlEl = document.getElementById("lvl_" + boxId);
    if(!lvlEl) return;
    const level = parseInt(lvlEl.value);
    const select = document.getElementById("harm_type_" + boxId);
    select.innerHTML = '<option value="0">Sin Harmony</option>';
    
    let type = 0;
    if(cat <= 4) type = 1; 
    else if(cat == 5) type = 5; 
    else if(cat >= 6 && cat <= 11) type = 6; 

    if(type > 0 && harmonyOpts[type]) {
        harmonyOpts[type].forEach(opt => {
            if(level >= opt.minLvl) {
                let o = document.createElement("option");
                o.value = opt.v;
                o.text = opt.n;
                select.appendChild(o);
            }
        });
    }
    updateHarmLevels(boxId, type);
}

function updateHarmLevels(boxId, forcedType) {
    const select = document.getElementById("harm_type_" + boxId);
    const lvlSelect = document.getElementById("harm_lvl_" + boxId);
    if(!select || !lvlSelect) return;
    
    const optVal = parseInt(select.value);
    lvlSelect.innerHTML = "";
    
    if(optVal === 0 || isNaN(optVal)) {
        lvlSelect.innerHTML = '<option value="0">-</option>';
        lvlSelect.disabled = true;
        return;
    }
    lvlSelect.disabled = false;

    let cat = document.getElementById("grp_" + boxId).value;
    let type = forcedType;
    if(!type) {
        if(cat <= 4) type = 1;
        else if(cat == 5) type = 5;
        else if(cat >= 6 && cat <= 11) type = 6;
    }

    const optionData = harmonyOpts[type].find(o => o.v === optVal);
    if(optionData && optionData.vals) {
        let seen = new Set();
        optionData.vals.forEach((val, idx) => {
            if(val > 0 && idx <= 13 && !seen.has(val)) {
                seen.add(val);
                let o = document.createElement("option");
                o.value = idx; 
                o.text = `+${val}`; 
                lvlSelect.appendChild(o);
            }
        });
    }
}

function toggleMenu(menuId) {
    const menu = document.getElementById(menuId);
    document.querySelectorAll(".dropdown-options-box").forEach(el => { if(el.id !== menuId) el.style.display = "none"; });
    menu.style.display = (menu.style.display === "none") ? "block" : "none";
}
window.onclick = function(event) {
    if (!event.target.matches(".toggle-opt-btn") && !event.target.closest(".dropdown-options-box")) {
        document.querySelectorAll(".dropdown-options-box").forEach(el => el.style.display = "none");
    }
}
</script>