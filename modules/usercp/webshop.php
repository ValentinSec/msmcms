<?php
/**
 * UserCP WebShop Module - FULL INTEGRAL VERSION
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 * Estética de Modales y Lógica de Compra Sincronizadas
 */

if (!defined("access") or !access) die();

if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'buy_item') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');

    if(!class_exists('WebShop')) @include_once(__PATH_INCLUDES__ . 'classes/class.webshop.php');

    if(!isLoggedIn() || !class_exists('WebShop')) {
        echo json_encode(array('status' => 'error', 'message' => 'Tu sesi&oacute;n ha expirado.'));
        exit;
    }

    $wsUid = (isset($_POST['uid']) && is_string($_POST['uid'])) ? $_POST['uid'] : '';
    echo json_encode(WebShop::buyItem($_SESSION['username'], $wsUid), JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// --- 2. FRONTEND: CARGA DE DATOS ---
$fileShopList = __PATH_INCLUDES__ . 'config/webshop_list.json';
$fileSettings = __PATH_INCLUDES__ . 'config/custom_settings.json';
$itemsManager = __PATH_INCLUDES__ . 'config/items_manager.php';
if (file_exists($itemsManager)) require_once($itemsManager);

$settings = file_exists($fileSettings) ? json_decode(file_get_contents($fileSettings), true) : array();
$shopItems = file_exists($fileShopList) ? json_decode(file_get_contents($fileShopList), true) : array();

$username = $_SESSION['username'];
$coinTable = isset($settings['table_coins']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $settings['table_coins']) : 'CashShopData';
$userCol = isset($settings['col_user']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $settings['col_user']) : 'AccountId';
if(strtolower($coinTable) == 'memb_info' && strtolower($userCol) == 'accountid') $userCol = 'memb___id';

try {
    $db = Connection::Database('Me_MuOnline');
    $userData = $db->query_fetch_single("SELECT * FROM [$coinTable] WHERE [$userCol] = ?", array($username));
} catch (Exception $e) { $userData = array(); }

$categories = array(0 => 'Swords', 1 => 'Axes', 2 => 'Maces', 3 => 'Spears', 4 => 'Bows', 5 => 'Staffs', 6 => 'Shields', 7 => 'Helms', 8 => 'Armors', 9 => 'Pants', 10 => 'Gloves', 11 => 'Boots', 12 => 'Wings', 13 => 'Pets', 14 => 'Jewels', 15 => 'Scrolls');
$catIcons = array(0 => 'fa-khanda', 1 => 'fa-hammer', 2 => 'fa-gavel', 3 => 'fa-location-arrow', 4 => 'fa-bullseye', 5 => 'fa-wand-magic-sparkles', 6 => 'fa-shield-halved', 7 => 'fa-helmet-safety', 8 => 'fa-shirt', 9 => 'fa-columns', 10 => 'fa-hand-fist', 11 => 'fa-boot', 12 => 'fa-feather-pointed', 13 => 'fa-paw', 14 => 'fa-gem', 15 => 'fa-scroll');

// --- RENDERIZADO ESTÉTICO ---
echo '<style>
    .shop-container { padding: 0; }
    .shop-header-balance { background: #111318; border-bottom: 1px solid #2d323e; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
    .balance-item { font-size: 13px; color: #888; background: #1a1d24; padding: 5px 12px; border-radius: 20px; border: 1px solid #2d323e; }
    .balance-item span { color: #f39c12; font-weight: bold; margin-left: 5px; }
    .ws-layout { display: flex; gap: 20px; min-height: 500px; color: #fff; padding-top: 20px; }
    .ws-sidebar { width: 240px; flex-shrink: 0; background: #111318; border: 1px solid #2d323e; border-radius: 8px; overflow: hidden; height: fit-content; }
    .ws-cat-head { background: #1a1d24; padding: 15px; font-weight: bold; border-bottom: 1px solid #2d323e; color: #888; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
    .ws-cat-btn { display: flex; align-items: center; width: 100%; padding: 12px 20px; background: transparent; border: none; color: #aaa; text-align: left; transition: 0.2s; font-size: 13px; cursor: pointer; }
    .ws-cat-btn:hover { background: #21262d; color: #fff; }
    .ws-cat-btn.active { background: #f39c12; color: #000; font-weight: bold; }
    .ws-cat-btn i { width: 25px; text-align: center; margin-right: 5px; }
    .ws-grid-panel { flex: 1; }
    .ws-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .shop-card { background: radial-gradient(circle at center, #1c2128 0%, #0d1117 100%); border: 1px solid #30363d; border-radius: 10px; padding: 15px; text-align: center; transition: all 0.3s ease; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px; }
    .shop-card:hover { transform: translateY(-5px); border-color: #f39c12; box-shadow: 0 10px 20px rgba(0,0,0,0.4); }
    .shop-card-img-box { height: 100px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; position: relative; }
    .shop-card-img { max-width: 100%; max-height: 100%; object-fit: contain; filter: drop-shadow(0 0 10px rgba(255,255,255,0.2)); transition: transform 0.3s; }
    .shop-card-title { font-size: 14px; font-weight: bold; color: #ccc; margin-bottom: 10px; line-height: 1.3; min-height: 36px; }
    .tag { font-size: 10px; padding: 2px 6px; border-radius: 3px; background: #21262d; color: #888; border: 1px solid #30363d; }
    .tag.exc { border-color: #238636; color: #238636; background: rgba(35, 134, 54, 0.1); }
    .shop-price { font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 10px; }
    .btn-shop-buy { background: #238636; border: 1px solid #238636; color: #fff; width: 100%; padding: 8px; border-radius: 6px; font-size: 13px; font-weight: bold; text-transform: uppercase; cursor: pointer; transition: 0.2s; }
    .btn-shop-buy:hover { background: #2ea043; }
    #user-tooltip { position: fixed; display: none; z-index: 10000; background: rgba(0, 0, 0, 0.95); border: 1px solid #555; padding: 12px; min-width: 220px; color: #fff; font-size: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.9); border-radius: 4px; pointer-events: none; text-align: center; }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10050; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .modal-shop-box { background: #161b22; width: 400px; border-radius: 8px; border: 1px solid #30363d; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.9); text-align: center; }
    .modal-shop-header { background: #21262d; padding: 15px; font-weight: 600; font-size: 16px; color: #fff; border-bottom: 1px solid #30363d; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .modal-shop-body { padding: 25px; color: #c9d1d9; font-size: 14px; line-height: 1.5; }
    .modal-shop-footer { background: #0d1117; padding: 15px; border-top: 1px solid #30363d; display: flex; justify-content: center; gap: 10px; }
    .btn-shop-ok { background: #238636; color: #fff; border: none; padding: 8px 25px; border-radius: 4px; cursor: pointer; font-weight: 600; }
    .btn-shop-close { background: transparent; border: 1px solid #30363d; color: #8b949e; padding: 8px 20px; border-radius: 4px; cursor: pointer; }
    .btn-donate-link { background: #f39c12; color: #000; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; }
</style>';

echo '<div class="shop-container">
    <div class="shop-header-balance">
        <h4 style="margin:0; color:#fff;"><i class="fa fa-shopping-cart" style="color:#ec4899; margin-right:8px;"></i> WebShop</h4>
        <div style="display:flex; gap:10px;">';
        for($i=1; $i<=3; $i++){
            $cn = isset($settings["col_coin_$i"]) ? $settings["col_coin_$i"] : '';
            if(!empty($cn)){
                $v = isset($userData[$cn]) ? number_format($userData[$cn]) : 0;
                echo '<div class="balance-item">'.htmlspecialchars($cn).': <span>'.$v.'</span></div>';
            }
        }
echo '  </div>
    </div>
    <div class="ws-layout">
        <div class="ws-sidebar">
            <div class="ws-cat-head">Categorías</div>
            <button class="ws-cat-btn active" onclick="filterShop(-1, this)"><i class="fa fa-layer-group"></i> Todos</button>';
            foreach($categories as $id => $name){
                $ic = isset($catIcons[$id]) ? $catIcons[$id] : 'fa-dot-circle';
                echo '<button class="ws-cat-btn" onclick="filterShop('.$id.', this)"><i class="fa-solid '.$ic.'"></i> '.$name.'</button>';
            }
echo '  </div>
        <div class="ws-grid-panel">
            <div class="ws-grid" id="shopContainer">';
            if(is_array($shopItems)){
                foreach($shopItems as $item){
                    $js = json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                    echo '<div class="shop-card shop-item" data-group="'.$item['group'].'" onmouseenter=\'showUserTooltip(event, '.$js.')\' onmousemove="moveUserTooltip(event)" onmouseleave="hideUserTooltip()">';
                    echo '<div><div class="shop-card-img-box"><img src="'.__BASE_URL__.$item['image'].'" class="shop-card-img" onerror="this.src=\''.__BASE_URL__.'img/items/no_image.gif\'"></div>';
                    echo '<div class="shop-card-title">'.htmlspecialchars($item['name']).' <span style="color:#f39c12">+'.$item['level'].'</span></div>';
                    echo '<div class="shop-tags">';
                    if($item['skill']) echo '<span class="tag">Skill</span>';
                    if($item['luck']) echo '<span class="tag">Luck</span>';
                    if(!empty($item['exc_opts'])) echo '<span class="tag exc">Excellent</span>';
                    echo '</div></div>';
                    echo '<div><div class="shop-price">'.number_format($item['price']).' <small>'.$item['currency'].'</small></div>';
                    echo '<button class="btn-shop-buy" onclick="initBuy(\''.$item['uid'].'\', \''.number_format($item['price']).' '.$item['currency'].'\')">Comprar</button></div>';
                    echo '</div>';
                }
            }
echo '      </div>
        </div>
    </div>
</div>';

// --- MODALES (ESTÉTICA RESTAURADA) ---
echo '<div id="user-tooltip"></div>
<div id="buyModal" class="modal-overlay"><div class="modal-shop-box"><div class="modal-shop-header"><i class="fa fa-shopping-cart"></i> Confirmar Compra</div><div class="modal-shop-body"><p>¿Deseas comprar este item por <b id="modalPrice" style="color:#f39c12"></b>?</p><small style="color:#666;">Se enviará a tu Baúl (Debe estar vacío).</small></div><div class="modal-shop-footer"><button class="btn-shop-close" onclick="closeModal(\'buyModal\')">Cancelar</button><button class="btn-shop-ok" id="btnConfirmBuy">Comprar</button></div></div></div>
<div id="modalNoFunds" class="modal-overlay"><div class="modal-shop-box"><div class="modal-shop-header" style="border-bottom-color: #b91c1c;"><i class="fa fa-times-circle" style="color: #ef4444;"></i> Saldo Insuficiente</div><div class="modal-shop-body"><p>No tienes suficientes créditos.</p><p style="font-size:12px; color:#888;">Necesitas: <span id="nf_price" style="color:#fff;"></span><br>Tienes: <span id="nf_balance" style="color:#fff;"></span></p></div><div class="modal-shop-footer"><button class="btn-shop-close" onclick="closeModal(\'modalNoFunds\')">Cerrar</button><a href="'.__BASE_URL__.'usercp/donate" class="btn-donate-link">Cargar Saldo</a></div></div></div>
<div id="modalError" class="modal-overlay"><div class="modal-shop-box"><div class="modal-shop-header"><i class="fa fa-exclamation-triangle" style="color: #f39c12;"></i> Atención</div><div id="errorMsgContent" class="modal-shop-body"></div><div class="modal-shop-footer"><button class="btn-shop-close" onclick="closeModal(\'modalError\')">Entendido</button></div></div></div>
<div id="modalSuccess" class="modal-overlay"><div class="modal-shop-box"><div class="modal-shop-header" style="border-bottom-color: #238636;"><i class="fa fa-check-circle" style="color: #238636;"></i> ¡Compra Exitosa!</div><div class="modal-shop-body"><p>El item ha sido depositado en tu Baúl.</p><small style="color:#888;">Si estás online, abre y cierra el baúl.</small></div><div class="modal-shop-footer"><button class="btn-shop-ok" onclick="closeModal(\'modalSuccess\'); location.reload();">Aceptar</button></div></div></div>';

echo '<script>
    let itemToBuy = null;
    function filterShop(catId, btn) {
        document.querySelectorAll(".ws-cat-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        document.querySelectorAll(".shop-item").forEach(el => {
            el.style.display = (catId == -1 || el.getAttribute("data-group") == catId) ? "flex" : "none";
        });
    }
    function initBuy(uid, priceTxt) {
        itemToBuy = uid;
        document.getElementById("modalPrice").innerText = priceTxt;
        document.getElementById("buyModal").style.display = "flex";
        document.getElementById("btnConfirmBuy").onclick = processPurchase;
    }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }
    function processPurchase() {
        const btn = document.getElementById("btnConfirmBuy");
        btn.disabled = true; btn.innerText = "Procesando...";
        const data = new FormData();
        data.append("ajax_action", "buy_item");
        data.append("uid", itemToBuy);
        fetch(window.location.href, { method: "POST", body: data })
        .then(r => r.text())
        .then(text => {
            closeModal("buyModal");
            btn.disabled = false; btn.innerText = "Comprar";
            let jsonStr = text;
            const start = text.indexOf("{"), end = text.lastIndexOf("}");
            if (start !== -1 && end !== -1) jsonStr = text.substring(start, end + 1);
            try {
                const res = JSON.parse(jsonStr);
                if(res.status === "success") document.getElementById("modalSuccess").style.display = "flex";
                else if (res.status === "no_funds") {
                    document.getElementById("nf_price").innerText = res.price + " " + res.currency;
                    document.getElementById("nf_balance").innerText = res.balance + " " + res.currency;
                    document.getElementById("modalNoFunds").style.display = "flex";
                } else {
                    document.getElementById("errorMsgContent").innerHTML = res.message;
                    document.getElementById("modalError").style.display = "flex";
                }
            } catch (e) {
                document.getElementById("errorMsgContent").innerHTML = "<b>Error crítico de respuesta:</b><br>" + text;
                document.getElementById("modalError").style.display = "flex";
            }
        });
    }
    const tooltip = document.getElementById("user-tooltip");
    function showUserTooltip(e, item) {
        const esc = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/\'/g, "&#039;");
        let isExc = (item.exc_opts && item.exc_opts.length > 0);
        let content = `<div style="font-weight:bold; font-size:14px; margin-bottom:5px; border-bottom:1px solid #444; padding-bottom:5px; color:${isExc ? "#00ff00" : "#fff"}">${isExc ? "Excellent " : ""}${esc(item.name)} +${item.level}</div>`;
        
        let hasSpecial = false;
        
        // Render 380 (Mapeado Seguro en JS)
        if(item.op380 == 1) {
            const op380Map = { 1: "Attack Success Rate Increase +10", 2: "Additional Damage +200", 3: "Defense Success Rate Increase (PVP) +10", 5: "Max. HP Increase +200", 6: "Max. SD Increase +700", 7: "SD Auto Recovery", 8: "SD Recovery Rate Increase +20%", 9: "Defensive Skill +200" };
            const item380Cfg = { "0-22":[2,1], "0-23":[2,1], "0-24":[2,1], "0-25":[2,1], "2-14":[2,1], "4-21":[2,1], "5-12":[2,1], "5-19":[2,1], "7-29":[8,3], "7-30":[8,3], "7-31":[8,3], "7-32":[8,3], "7-33":[8,3], "8-29":[7,3], "8-30":[7,3], "8-31":[7,3], "8-32":[7,3], "8-33":[7,3], "9-29":[9,3], "9-30":[9,3], "9-31":[9,3], "9-32":[9,3], "9-33":[9,3], "10-29":[5,3], "10-30":[5,3], "10-31":[5,3], "10-32":[5,3], "10-33":[5,3], "11-29":[6,3], "11-30":[6,3], "11-31":[6,3], "11-32":[6,3], "11-33":[6,3] };
            
            let key = item.group + "-" + item.id;
            let found380 = false;
            if(item380Cfg[key]) {
                item380Cfg[key].forEach(idx => {
                    if(op380Map[idx]) {
                        content += `<div style="color:#d000d0; font-weight:bold;">${op380Map[idx]}</div>`;
                        found380 = true;
                    }
                });
            }
            if(!found380) content += `<div style="color:#d000d0; font-weight:bold;">380 Option Active</div>`;
            hasSpecial = true;
        }
        
        // Render Harmony (Sobrescribiendo el texto genérico del JSON)
        if(item.harmony && item.harmony.val != 0) {
            let hType = 1;
            if (item.group == 5) hType = 5;
            if (item.group >= 6 && item.group <= 11) hType = 6;
            
            let optId = (item.harmony.val >> 4) & 0xF;
            let optLvl = item.harmony.val & 0xF;
            
            const hNames = {
                1: {1: "Min. attack increase", 2: "Max. attack increase", 3: "Required strength decrease", 4: "Required agility decrease", 5: "Attack increase (Min,Max)", 6: "Critical damage increase", 7: "Skill attack increase", 8: "Attack success rate increase (PVP)", 9: "SD decrement increase", 10: "SD bypass rate increase"},
                5: {1: "Wizardry increase", 2: "Required strength decrease", 3: "Required agility decrease", 4: "Skill attack increase", 5: "Critical damage increase", 6: "SD decrement increase", 7: "Attack success rate increase (PVP)", 8: "SD bypass rate increase"},
                6: {1: "Defense increase", 2: "Max. AG increase", 3: "Max. HP increase", 4: "Life auto increment increase", 5: "Mana auto increment increase", 6: "Defense success rate increase (PVP)", 7: "Damage decrement increase", 8: "SD ratio increase"}
            };
            
            let hName = (hNames[hType] && hNames[hType][optId]) ? hNames[hType][optId] : "Harmony Option";
            content += `<div style="color:#ffff00; font-weight:bold;">${hName} (Lvl ${optLvl})</div>`;
            hasSpecial = true;
        }
        
        if (hasSpecial) content += `<div style="margin-bottom: 5px;"></div>`;

        if(item.skill == 1) content += `<div style="color:#9aadcd">Skill Attack Power</div>`;
        if(item.luck == 1) content += `<div style="color:#9aadcd">Luck (Success Rate +25%)</div>`;
        if(item.option > 0) content += `<div style="color:#9aadcd">Additional Option +${item.option*4}</div>`;
        if(isExc) item.exc_opts.forEach(ex => { content += `<div style="color:#00ff00">${esc(ex.name)}</div>`; });
        if(item.sockets){
            let sHtml = "";
            item.sockets.forEach((s, i) => { if(s.val != 255) sHtml += `<div style="color:${s.val == 254 ? "#aaa" : "#d698fd"}">Socket ${i+1}: ${esc(s.name)}</div>`; });
            if(sHtml) content += `<div style="margin-top:5px; border-top:1px solid #555; padding-top:5px;">${sHtml}</div>`;
        }
        tooltip.innerHTML = content; tooltip.style.display = "block"; moveUserTooltip(e);
    }
    function moveUserTooltip(e) { tooltip.style.left = (e.clientX + 15) + "px"; tooltip.style.top = (e.clientY + 15) + "px"; }
    function hideUserTooltip() { tooltip.style.display = "none"; }
</script>';
?>