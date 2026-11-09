<?php
/**
 * Casino Goblin - Frontend UserCP
 * FINAL VERSION: Full Modals, Configurable Texts, Secure Transactions
 * FIX: 380 Option moved to Byte 9 (Category Byte) | 0x08
 * ADDED: SQL Logging (MUSERVERMAKER_CASINO_LOG)
 */
if(!defined('access')) die();

// 1. CARGA DE CONFIGURACIÓN Y DEPENDENCIAS
$casinoFile   = __PATH_INCLUDES__ . 'config/casino_boxes.json';
$itemsManager = __PATH_INCLUDES__ . 'config/items_manager.php';
$settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$configFile   = __PATH_INCLUDES__ . 'config/casino_config.json';

if (file_exists($itemsManager)) require_once($itemsManager);

$boxes = file_exists($casinoFile) ? json_decode(file_get_contents($casinoFile), true) : [];
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    'title' => 'El Alquimista del Caos',
    'desc' => 'Bienvenido, viajero. A cambio de monedas, puedes abrir uno de mis cofres antiguos. Cada cofre contiene tesoros de distinto valor. ¡La suerte está echada!'
];

// Configuración de Tabla de Coins
function getTbl($key, $arr, $def) { return (isset($arr[$key]) && !empty($arr[$key])) ? $arr[$key] : $def; }
$tableCoins = getTbl('table_coins', $settings, 'CashShopData');
$colUser    = (in_array(strtolower($tableCoins), ['memb_info','memb_stat'])) ? 'memb___id' : 'AccountID';

// --------------------------------------------------------------------
// BACKEND: AJAX HANDLERS
// --------------------------------------------------------------------

// A. INICIAR JUEGO
if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'init_spin') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    try {
        if(!isLoggedIn()) throw new Exception("Debes iniciar sesión para jugar.");
        
        $boxId = $_POST['box_id'];
        $user  = $_SESSION['username'];
        $db    = Connection::Database('Me_MuOnline');

        // Buscar Caja
        $targetBox = null;
        foreach($boxes as $b) { if($b['id'] === $boxId) { $targetBox = $b; break; } }
        if(!$targetBox) throw new Exception("Esta caja ya no existe o fue eliminada.");
        if(empty($targetBox['rewards'])) throw new Exception("Esta caja está vacía temporalmente.");

        // Verificar Saldo
        $currency = $targetBox['currency'];
        $cost     = (int)$targetBox['cost'];
        
        $userData = $db->query_fetch_single("SELECT $currency FROM $tableCoins WHERE $colUser = ?", [$user]);
        if(!$userData) throw new Exception("No se encontró tu cuenta en la tabla de economía.");
        
        if((int)$userData[$currency] < $cost) throw new Exception("No tienes suficientes <b>$currency</b> para abrir esta caja.");

        // Sorteo (RNG)
        $rand = mt_rand(1, 10000) / 100;
        $cumulative = 0;
        $winner = null;
        shuffle($targetBox['rewards']);

        foreach($targetBox['rewards'] as $reward) {
            $cumulative += $reward['chance'];
            if($rand <= $cumulative) {
                $winner = $reward;
                break;
            }
        }
        if(!$winner) $winner = end($targetBox['rewards']);

        // Cobrar
        $db->query("UPDATE $tableCoins SET $currency = $currency - ? WHERE $colUser = ?", [$cost, $user]);

        // --- SQL LOGGING (NUEVO) ---
        $db->query("INSERT INTO MUSERVERMAKER_CASINO_LOG (account_id, box_name, cost_amount, currency_type, prize_name) VALUES (?, ?, ?, ?, ?)", 
            [$user, $targetBox['name'], $cost, $currency, $winner['name']]
        );
        // ---------------------------

        // Guardar en Sesión
        $_SESSION['casino_pending_reward'] = $winner;
        $_SESSION['casino_pending_box'] = $targetBox['name'];

        echo json_encode([
            'status' => 'success',
            'winner_id' => $winner['id'],
            'rewards_pool' => $targetBox['rewards']
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// B. RECLAMAR PREMIO
if(isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'claim_reward') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        if(!isLoggedIn()) throw new Exception("Sesión expirada.");
        if(!isset($_SESSION['casino_pending_reward'])) throw new Exception("No tienes ningún premio pendiente.");

        $user = $_SESSION['username'];
        $reward = $_SESSION['casino_pending_reward'];
        $db = Connection::Database('Me_MuOnline');

        // Verificar Baúl
        $whData = $db->query_fetch_single("SELECT Items FROM warehouse WHERE AccountId = ?", [$user]);
        if(!$whData) throw new Exception("Tu baúl no existe. Entra al juego y ábrelo una vez.");
        
        $itemsHex = bin2hex($whData['Items']);
        if (!preg_match('/^[f]+$/i', $itemsHex)) {
            throw new Exception("<b>¡Tu baúl NO está vacío!</b><br>Debes vaciarlo completamente dentro del juego.");
        }

        // Generar HEX
        $itemHex = generateRewardHex($reward);
        $paddingLength = strlen($itemsHex) - strlen($itemHex);
        
        if ($paddingLength < 0) throw new Exception("Error crítico: El item generado es incorrecto.");
        
        $newWhHex = $itemHex . str_repeat('F', $paddingLength);
        $binaryData = "0x" . $newWhHex;

        // Entregar
        $db->query("UPDATE warehouse SET Items = $binaryData WHERE AccountId = ?", [$user]);

        unset($_SESSION['casino_pending_reward']);
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- GENERADOR HEX AVANZADO ---
function generateRewardHex($item) {
    $id = (int)$item['item_id']; 
    $group = (int)$item['group']; 
    $level = (int)$item['level'];
    $skill = isset($item['skill']) ? (int)$item['skill'] : 0;
    $luck = isset($item['luck']) ? (int)$item['luck'] : 0;
    $opt = isset($item['option']) ? (int)$item['option'] : 0;
    
    // 1. Excellent Options
    $excVal = 0;
    if(isset($item['exc_opts']) && is_array($item['exc_opts'])) {
        foreach($item['exc_opts'] as $val) {
            $excVal += (int)$val;
        }
    }

    // 2. Option Base (+4, +8...)
    $optVal = 0;
    if ($skill) $optVal += 128;
    if ($luck) $optVal += 4;
    $optVal += ($opt & 3);
    if ($opt > 3) $excVal += 64; 

    // 3. Option 380 & Group (BYTE 9)
    // El grupo va en los primeros 4 bits (High Nibble)
    $byte9 = ($group << 4) & 0xF0;
    
    // Si es 380, activamos el bit 0x08 (Decimal 8) en este byte
    if(isset($item['opt_380']) && $item['opt_380'] == 1) {
        $byte9 |= 8; 
    }

    // 4. Harmony (Byte 10)
    $harmonyByte = 0;
    if(isset($item['harmony_type']) && $item['harmony_type'] > 0) {
        $hType = (int)$item['harmony_type'];
        $hLvl = (int)$item['harmony_level'];
        $harmonyByte = ($hType << 4) | $hLvl;
    } 
    elseif (isset($item['harmony']['val'])) {
        $harmonyByte = (int)$item['harmony']['val'];
    }

    // 5. Sockets (Bytes 11-15)
    $socketsHex = "";
    if(isset($item['sockets']) && is_array($item['sockets'])) {
        foreach($item['sockets'] as $s) {
            $val = is_array($s) ? (int)$s['val'] : (int)$s;
            $socketsHex .= sprintf("%02X", $val);
        }
    } else {
        $socketsHex = "FFFFFFFFFF";
    }
    while(strlen($socketsHex) < 10) $socketsHex .= "FF";

    // Construcción HEX (32 chars - Season 6 Standard)
    $hex = sprintf("%02X", $id & 255);                 
    $hex .= sprintf("%02X", $optVal | ($level << 3));  
    $hex .= "FF"; // Durability                                     
    $hex .= "00000000"; // Serial                               
    $hex .= sprintf("%02X", $excVal); // Byte 7: Exc               
    $hex .= "00"; // Byte 8: Ancient (Dejamos en 0 por ahora)
    $hex .= sprintf("%02X", $byte9); // Byte 9: Group + 380 Flag
    $hex .= sprintf("%02X", $harmonyByte); // Byte 10: Harmony
    $hex .= substr($socketsHex, 0, 10); // Bytes 11-15: Sockets
    
    return $hex;
}
?>

<style>
    /* LAYOUT & CARDS */
    .casino-container { display: flex; flex-wrap: wrap; gap: 30px; background: #0b0d10; padding: 20px; border-radius: 12px; border: 1px solid #1f232a; min-height: 600px; position: relative; overflow: hidden; }
    .casino-cover { flex: 0 0 350px; position: relative; }
    .ornamental-frame {
        position: relative; padding: 10px;
        border: 2px solid #4a3b2a; border-radius: 4px;
        background: url('<?php echo __BASE_URL__; ?>img/boxes/Portada.png') no-repeat center center;
        background-size: cover; height: 100%; min-height: 500px;
        box-shadow: 0 0 20px rgba(0,0,0,0.8) inset;
    }
    .ornament-corner { position: absolute; width: 40px; height: 40px; border: 2px solid #f39c12; transition: 0.3s; }
    .tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
    .tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
    .bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
    .br { bottom: -2px; right: -2px; border-left: none; border-top: none; }
    
    .casino-content { flex: 1; display: flex; flex-direction: column; }
    .casino-intro { margin-bottom: 30px; color: #a0a0a0; font-size: 14px; line-height: 1.6; border-bottom: 1px solid #1f232a; padding-bottom: 20px; }
    .casino-intro h2 { font-family: 'Cinzel', serif; color: #f39c12; margin-bottom: 10px; font-size: 28px; }
    
    .box-selector-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .loot-box-card {
        background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 15px; cursor: pointer;
        transition: 0.3s; text-align: center; position: relative; overflow: hidden;
    }
    .loot-box-card:hover { transform: translateY(-5px); border-color: #f39c12; box-shadow: 0 0 15px rgba(243, 156, 18, 0.2); }
    .lb-img { height: 80px; object-fit: contain; margin-bottom: 10px; filter: drop-shadow(0 0 5px rgba(255,255,255,0.1)); }
    .lb-title { color: #fff; font-weight: bold; font-size: 14px; display: block; }
    .lb-cost { color: #f39c12; font-size: 12px; font-weight: bold; background: rgba(243, 156, 18, 0.1); padding: 2px 8px; border-radius: 10px; margin-top: 5px; display: inline-block; }
    .lb-contents { font-size: 10px; color: #666; margin-top: 8px; height: 30px; overflow: hidden; }

    .game-area { display: none; text-align: center; flex: 1; flex-direction: column; justify-content: center; align-items: center; position: relative; }
    
    /* ROULETTE */
    .roulette-container {
        width: 100%; max-width: 600px; height: 120px; background: #000; border: 4px solid #f39c12; 
        border-radius: 8px; overflow: hidden; position: relative; margin: 30px auto;
        box-shadow: 0 0 30px rgba(243, 156, 18, 0.3) inset;
    }
    .roulette-track { display: flex; height: 100%; transition: transform 6s cubic-bezier(0.1, 0, 0.1, 1); will-change: transform; }
    .roulette-item { 
        min-width: 120px; height: 100%; border-right: 1px solid #333; display: flex; flex-direction: column; 
        align-items: center; justify-content: center; background: #111; position: relative;
    }
    .roulette-item img { width: 60px; height: 60px; object-fit: contain; z-index: 2; }
    .roulette-item .r-name { color: #888; font-size: 10px; margin-top: 5px; z-index: 2; text-align: center; width: 90%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .roulette-item.winner-glow::after {
        content:''; position: absolute; top:0; left:0; width:100%; height:100%;
        background: radial-gradient(circle, rgba(243, 156, 18, 0.4) 0%, rgba(0,0,0,0) 70%); z-index: 1;
    }
    .roulette-marker {
        position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 4px; height: 100%; 
        background: #ff0000; z-index: 10; box-shadow: 0 0 10px #ff0000;
    }

    .btn-spin-action { 
        background: linear-gradient(135deg, #f39c12, #d35400); border: none; color: #fff; 
        padding: 15px 50px; font-weight: bold; border-radius: 50px; font-size: 20px; 
        cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 2px;
        box-shadow: 0 0 20px rgba(243, 156, 18, 0.4);
    }
    .btn-spin-action:hover { transform: scale(1.05); filter: brightness(1.2); }
    .btn-spin-action:disabled { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); }

    /* WINNER OVERLAY */
    .winner-overlay {
        display: none; position: absolute; top:0; left:0; width:100%; height:100%;
        background: rgba(0,0,0,0.9); z-index: 50; flex-direction: column; align-items: center; justify-content: center;
        animation: fadeIn 0.5s;
    }
    .winner-card {
        background: #161b22; border: 2px solid #f39c12; border-radius: 12px; padding: 30px; text-align: center;
        box-shadow: 0 0 50px rgba(243, 156, 18, 0.3); transform: scale(0.8); animation: popIn 0.5s forwards;
    }
    @keyframes popIn { to { transform: scale(1); } }
    .win-title { color: #f39c12; font-family: 'Cinzel'; font-size: 24px; margin-bottom: 20px; }
    .win-img { height: 100px; margin-bottom: 20px; filter: drop-shadow(0 0 15px rgba(255,255,255,0.3)); }
    .win-name { color: #fff; font-size: 18px; font-weight: bold; margin-bottom: 30px; display: block; }
    .btn-claim { background: #238636; border: none; color: #fff; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
    .btn-keep { background: transparent; border: 1px solid #30363d; color: #888; padding: 10px 20px; border-radius: 6px; cursor: pointer; margin-right: 10px; }

    /* MODALES GENERICOS */
    .generic-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); align-items:center; justify-content:center; backdrop-filter: blur(4px); }
    .gm-box { background: #161b22; border-radius: 8px; width: 350px; text-align: center; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.8); border: 1px solid #30363d; animation: fadeIn 0.2s; }
    .gm-icon-box { padding: 20px 0 10px 0; font-size: 40px; }
    .gm-success i { color: #2ea043; }
    .gm-error i { color: #f85149; }
    .gm-info i { color: #58a6ff; }
    .gm-content { padding: 0 25px 25px 25px; color: #c9d1d9; font-size: 14px; line-height: 1.5; }
    .gm-title { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 10px; display: block; }
    .gm-btn { width: 100%; padding: 12px; background: #21262d; border: none; border-top: 1px solid #30363d; color: #c9d1d9; cursor: pointer; font-weight: 600; transition: 0.2s; }
    .gm-btn:hover { background: #30363d; color: #fff; }
    
    /* MODAL CONFIRMACION */
    .confirm-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); align-items:center; justify-content:center; backdrop-filter: blur(4px); }
    .cm-box { background: #161b22; border: 1px solid #f39c12; border-radius: 8px; width: 400px; text-align: center; padding: 25px; box-shadow: 0 0 40px rgba(243, 156, 18, 0.2); }
    .cm-btns { display: flex; justify-content: center; gap: 15px; margin-top: 25px; }
    .cm-btn-yes { background: #238636; color: #fff; border: none; padding: 8px 25px; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .cm-btn-yes:disabled { opacity: 0.5; cursor: not-allowed; }
    .cm-btn-no { background: transparent; color: #8b949e; border: 1px solid #30363d; padding: 8px 25px; border-radius: 4px; cursor: pointer; }
</style>

<div class="casino-container">
    <div class="casino-cover">
        <div class="ornamental-frame">
            <div class="ornament-corner tl"></div><div class="ornament-corner tr"></div>
            <div class="ornament-corner bl"></div><div class="ornament-corner br"></div>
        </div>
    </div>

    <div class="casino-content">
        <div id="selection-screen">
            <div class="casino-intro">
                <h2><?php echo htmlspecialchars($config['title']); ?></h2>
                <p><?php echo htmlspecialchars($config['desc']); ?></p>
            </div>
            <h4 style="color:#fff; margin-bottom:15px; border-left:3px solid #f39c12; padding-left:10px;">Selecciona tu Cofre</h4>
            <div class="box-selector-grid">
                <?php foreach($boxes as $box): 
                    $bImg = !empty($box['img']) ? __BASE_URL__.$box['img'] : 'https://placehold.co/100x100/333/ccc?text=BOX';
                    $rewardsList = array_map(function($r){ return $r['name']; }, array_slice($box['rewards'], 0, 3));
                    $rewardsStr = implode(', ', $rewardsList) . (count($box['rewards']) > 3 ? '...' : '');
                ?>
                <div class="loot-box-card" onclick="openConfirmModal('<?php echo $box['id']; ?>', '<?php echo $box['name']; ?>', '<?php echo number_format($box['cost']).' '.$box['currency']; ?>')">
                    <img src="<?php echo $bImg; ?>" class="lb-img">
                    <span class="lb-title"><?php echo $box['name']; ?></span>
                    <div class="lb-cost"><?php echo number_format($box['cost']) . ' ' . $box['currency']; ?></div>
                    <div class="lb-contents"><?php echo $rewardsStr; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="game-screen" class="game-area">
            <h3 id="gs-box-name" style="color: #fff; margin-bottom: 5px;">Nombre Caja</h3>
            <p style="color: #666; font-size: 12px; margin-bottom: 20px;">Costo: <span id="gs-cost" style="color:#f39c12"></span> (Ya debitado)</p>
            <div class="roulette-container"><div class="roulette-marker"></div><div class="roulette-track" id="roulette-track"></div></div>
            <button class="btn-spin-action" id="btn-spin" onclick="spinNow()">¡GIRAR!</button>
            <div style="margin-top: 20px; font-size: 11px; color: #444;">El resultado ya fue determinado. La animación es solo visual.</div>
        </div>
    </div>

    <div class="winner-overlay" id="winner-screen">
        <div class="winner-card">
            <div class="win-title">¡RECOMPENSA OBTENIDA!</div>
            <img src="" id="win-img" class="win-img">
            <span class="win-name" id="win-name"></span>
            <p style="font-size:12px; color:#aaa; margin-bottom:20px;">¿Deseas enviar este item a tu baúl ahora?</p>
            <div>
                <button class="btn-keep" onclick="keepForLater()">Guardar para después</button>
                <button class="btn-claim" onclick="checkVault()">Enviar al Baúl</button>
            </div>
        </div>
    </div>
</div>

<div id="purchase-modal" class="confirm-modal">
    <div class="cm-box">
        <h4 style="color:#f39c12; margin-bottom:10px;"><i class="fa fa-dice"></i> ABRIR COFRE</h4>
        <p style="color:#c9d1d9; font-size:14px; margin-bottom:5px;">Estás a punto de abrir <b id="pm-name"></b>.</p>
        <p style="color:#8b949e; font-size:12px;">Costo: <span id="pm-cost" style="color:#fff; font-weight:bold;"></span></p>
        <div class="cm-btns">
            <button class="cm-btn-no" onclick="closeConfirmModal()">Cancelar</button>
            <button class="cm-btn-yes" id="btn-confirm-purchase" onclick="executePurchase()">¡Abrir Cofre!</button>
        </div>
    </div>
</div>

<div id="message-modal" class="generic-modal">
    <div class="gm-box">
        <div class="gm-icon-box" id="gm-icon"></div>
        <div class="gm-content"><span class="gm-title" id="gm-title"></span><span id="gm-msg"></span></div>
        <button class="gm-btn" id="gm-btn-ok">Entendido</button>
    </div>
</div>

<div id="vault-modal" class="confirm-modal">
    <div class="cm-box">
        <h4 style="color:#f39c12; margin-bottom:10px;"><i class="fa fa-box-open"></i> REVISIÓN DE BAÚL</h4>
        <p style="color:#c9d1d9; font-size:14px; margin-bottom:5px;">Para recibir el premio, tu baúl debe estar <b>completamente vacío</b>.</p>
        <p style="color:#8b949e; font-size:12px;">¿Confirmas que tu baúl está vacío?</p>
        <div class="cm-btns">
            <button class="cm-btn-no" onclick="closeVaultModal()">Cancelar</button>
            <button class="cm-btn-yes" onclick="processClaim()">Sí, está vacío</button>
        </div>
    </div>
</div>

<script>
let gameData = null;
const ITEM_WIDTH = 120;
let modalCallback = null;
let pendingBoxId, pendingBoxName, pendingBoxCost;

function showMsg(title, msg, type = 'info', callback = null) {
    const modal = document.getElementById('message-modal');
    const iconBox = document.getElementById('gm-icon');
    const btn = document.getElementById('gm-btn-ok');
    document.getElementById('gm-title').innerText = title;
    document.getElementById('gm-msg').innerHTML = msg;
    modalCallback = callback;
    iconBox.className = 'gm-icon-box';
    if(type === 'success') { iconBox.innerHTML = '<i class="fa fa-check-circle"></i>'; iconBox.classList.add('gm-success'); }
    else if (type === 'error') { iconBox.innerHTML = '<i class="fa fa-times-circle"></i>'; iconBox.classList.add('gm-error'); }
    else { iconBox.innerHTML = '<i class="fa fa-info-circle"></i>'; iconBox.classList.add('gm-info'); }
    modal.style.display = 'flex';
    btn.onclick = function() {
        modal.style.display = 'none';
        if(typeof modalCallback === 'function') { modalCallback(); modalCallback = null; }
    }
}

function openConfirmModal(id, name, cost) {
    pendingBoxId = id; pendingBoxName = name; pendingBoxCost = cost;
    document.getElementById('pm-name').innerText = name;
    document.getElementById('pm-cost').innerText = cost;
    const btn = document.getElementById('btn-confirm-purchase');
    btn.disabled = false;
    btn.innerText = "¡Abrir Cofre!";
    document.getElementById('purchase-modal').style.display = 'flex';
}

function closeConfirmModal() { document.getElementById('purchase-modal').style.display = 'none'; }

function executePurchase() {
    const btn = document.getElementById('btn-confirm-purchase');
    btn.disabled = true;
    btn.innerText = "Procesando...";
    prepareGame(pendingBoxId, pendingBoxName, pendingBoxCost);
}

function prepareGame(boxId, boxName, boxCost) {
    const formData = new FormData();
    formData.append('ajax_action', 'init_spin');
    formData.append('box_id', boxId);
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        closeConfirmModal();
        if(res.status === 'success') {
            gameData = res;
            setupBoard(boxName, boxCost);
        } else {
            showMsg('Atención', res.message, 'error');
        }
    })
    .catch(() => {
        closeConfirmModal();
        showMsg('Error', 'Error de conexión con el servidor.', 'error');
    });
}

function setupBoard(name, cost) {
    document.getElementById('selection-screen').style.display = 'none';
    document.getElementById('game-screen').style.display = 'flex';
    document.getElementById('gs-box-name').innerText = name;
    document.getElementById('gs-cost').innerText = cost;
    const track = document.getElementById('roulette-track');
    track.innerHTML = '';
    const pool = gameData.rewards_pool;
    let itemsHtml = '';
    for(let i=0; i<40; i++) itemsHtml += createItemHtml(pool[Math.floor(Math.random() * pool.length)]);
    const winner = pool.find(i => i.id === gameData.winner_id) || pool[0];
    itemsHtml += createItemHtml(winner, true);
    for(let i=0; i<5; i++) itemsHtml += createItemHtml(pool[Math.floor(Math.random() * pool.length)]);
    track.innerHTML = itemsHtml;
}

function createItemHtml(item, isTarget = false) {
    const imgUrl = "<?php echo __BASE_URL__; ?>img/items/" + item.group + "-" + item.item_id + ".gif";
    const idAttr = isTarget ? 'id="target-item"' : '';
    return `<div class="roulette-item" ${idAttr}><img src="${imgUrl}"><span class="r-name">${item.name}</span></div>`;
}

function spinNow() {
    const btn = document.getElementById('btn-spin');
    btn.disabled = true;
    const track = document.getElementById('roulette-track');
    const target = document.getElementById('target-item');
    const containerCenter = 300;
    const targetLeft = target.offsetLeft;
    const itemHalf = ITEM_WIDTH / 2;
    const randomOffset = Math.floor(Math.random() * 80) - 40; 
    const finalPos = -(targetLeft - containerCenter + itemHalf + randomOffset);
    track.style.transform = `translateX(${finalPos}px)`;
    setTimeout(() => {
        target.classList.add('winner-glow');
        setTimeout(() => showWinnerScreen(), 1000);
    }, 6000);
}

function showWinnerScreen() {
    const winner = gameData.rewards_pool.find(i => i.id === gameData.winner_id);
    document.getElementById('win-name').innerText = winner.name + " +" + winner.level;
    document.getElementById('win-img').src = "<?php echo __BASE_URL__; ?>img/items/" + winner.group + "-" + winner.item_id + ".gif";
    document.getElementById('winner-screen').style.display = 'flex';
}

function keepForLater() {
    showMsg('Item Guardado', 'El item quedó guardado en tu sesión temporalmente. Podrás reclamarlo la próxima vez que entres a este módulo (o recargando la página).', 'info', function() {
        location.reload();
    });
    document.getElementById('winner-screen').style.display = 'none';
}

function checkVault() { document.getElementById('vault-modal').style.display = 'flex'; }
function closeVaultModal() { document.getElementById('vault-modal').style.display = 'none'; }

function processClaim() {
    const formData = new FormData();
    formData.append('ajax_action', 'claim_reward');
    fetch(window.location.href, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        closeVaultModal();
        if(res.status === 'success') {
            showMsg('¡Felicidades!', 'El item ha sido enviado a tu baúl correctamente.', 'success', function() {
                location.reload();
            });
        } else {
            showMsg('Atención', res.message, 'error');
        }
    })
    .catch(() => {
        closeVaultModal();
        showMsg('Error', 'Error de comunicación.', 'error');
    });
}
</script>