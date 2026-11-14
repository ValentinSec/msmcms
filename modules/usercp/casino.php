<?php
/**
 * Casino Goblin - UserCP
 * La logica (cobro, sorteo, premio pendiente y entrega) vive en includes/classes/class.casino.php
 * ASCII puro: los acentos van como entidades HTML o como \uXXXX en JavaScript.
 */
if(!defined('access')) die();

if(!class_exists('Casino')) @include_once(__PATH_INCLUDES__ . 'classes/class.casino.php');

// --------------------------------------------------------------------
// AJAX
// --------------------------------------------------------------------
if(isset($_POST['ajax_action']) && ($_POST['ajax_action'] === 'init_spin' || $_POST['ajax_action'] === 'claim_reward')) {
    while(ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    if(!isLoggedIn()) {
        $cgRes = array('status' => 'error', 'message' => 'Tu sesi' . "\u{00F3}" . 'n ha expirado.');
    } elseif(!class_exists('Casino')) {
        $cgRes = array('status' => 'error', 'message' => 'Casino no disponible.');
    } elseif($_POST['ajax_action'] === 'init_spin') {
        $cgRes = Casino::spin($_SESSION['username'], isset($_POST['box_id']) ? (string)$_POST['box_id'] : '');
    } else {
        $cgRes = Casino::claim($_SESSION['username'], isset($_POST['pending_id']) ? (int)$_POST['pending_id'] : 0);
    }

    echo json_encode($cgRes, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// --------------------------------------------------------------------
// DATOS DE LA PANTALLA
// --------------------------------------------------------------------
$cgH = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };

$cgBoxes = class_exists('Casino') ? Casino::boxes() : array();

$cgCfgFile = __PATH_INCLUDES__ . 'config/casino_config.json';
$cgConfig = file_exists($cgCfgFile) ? json_decode((string)@file_get_contents($cgCfgFile), true) : null;
if(!is_array($cgConfig)) $cgConfig = array();
$cgTitle = (isset($cgConfig['title']) && $cgConfig['title'] !== '') ? $cgConfig['title'] : 'Casino Goblin';
$cgDesc  = (isset($cgConfig['desc']) && $cgConfig['desc'] !== '') ? $cgConfig['desc'] : 'A cambio de monedas puedes abrir uno de mis cofres. Cada cofre contiene tesoros de distinto valor.';

$cgPending = (class_exists('Casino') && isLoggedIn()) ? Casino::pending($_SESSION['username']) : array();
$cgPendingJson = json_encode($cgPending, JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if($cgPendingJson === false) $cgPendingJson = '[]';
?>

<style>
    .cg-container, .cg-modal { --cg-a: var(--ucp-accent, #f39c12); --cg-rgb: var(--ucp-accent-rgb, 243, 156, 18); }

    /* ESTRUCTURA */
    .cg-container { display: flex; flex-wrap: wrap; gap: 28px; background: #0b0d10; padding: 20px; border-radius: 12px; border: 1px solid #1f232a; min-height: 560px; }
    .cg-cover { flex: 0 0 340px; }
    .cg-frame { position: relative; height: 100%; min-height: 480px; border: 1px solid rgba(var(--cg-rgb), 0.35); border-radius: 6px; background: url('<?php echo __BASE_URL__; ?>img/boxes/Portada.png') no-repeat center center; background-size: cover; box-shadow: 0 0 20px rgba(0,0,0,0.8) inset; }
    .cg-corner { position: absolute; width: 36px; height: 36px; border: 2px solid var(--cg-a); }
    .cg-corner.tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
    .cg-corner.tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
    .cg-corner.bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
    .cg-corner.br { bottom: -2px; right: -2px; border-left: none; border-top: none; }
    .cg-content { flex: 1 1 320px; min-width: 0; display: flex; flex-direction: column; }
    .cg-intro { margin-bottom: 24px; color: #a0a6b1; font-size: 14px; line-height: 1.6; border-bottom: 1px solid #1f232a; padding-bottom: 18px; }
    .cg-intro h2 { font-family: 'Cinzel', serif; color: var(--cg-a); margin: 0 0 10px; font-size: clamp(22px, 3vw, 28px); }
    .cg-intro p { margin: 0; }
    .cg-subtitle { color: #fff; font-size: 16px; margin: 0 0 14px; border-left: 3px solid var(--cg-a); padding-left: 10px; }

    /* COFRES */
    .cg-box-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 14px; }
    .cg-box-card { background: #12161c; border: 1px solid #262c36; border-radius: 10px; padding: 16px 12px; cursor: pointer; transition: 0.25s; text-align: center; }
    .cg-box-card:hover { transform: translateY(-4px); border-color: var(--cg-a); box-shadow: 0 8px 22px rgba(var(--cg-rgb), 0.18); }
    .cg-box-card img { height: 80px; max-width: 100%; object-fit: contain; margin-bottom: 10px; }
    .cg-box-title { color: #fff; font-weight: 700; font-size: 14px; display: block; }
    .cg-box-cost { color: var(--cg-a); font-size: 12px; font-weight: 700; background: rgba(var(--cg-rgb), 0.1); border: 1px solid rgba(var(--cg-rgb), 0.3); padding: 2px 10px; border-radius: 999px; margin-top: 6px; display: inline-block; }
    .cg-box-contents { font-size: 10px; color: #6b7280; margin-top: 8px; max-height: 30px; overflow: hidden; }
    .cg-empty { color: #6b7280; font-size: 13px; padding: 30px 0; text-align: center; grid-column: 1 / -1; }

    /* PREMIOS PENDIENTES */
    .cg-pending { margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #1f232a; }
    .cg-pending h4 { color: #fff; font-size: 16px; margin: 0 0 6px; display: flex; align-items: center; gap: 8px; }
    .cg-pending h4 i { color: var(--cg-a); }
    .cg-count { background: var(--cg-a); color: #111; font-size: 11px; font-weight: 800; border-radius: 999px; padding: 1px 8px; }
    .cg-pending-help { color: #8b95a7; font-size: 12px; margin: 0 0 12px; }
    .cg-pend-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid #1a1f27; }
    .cg-pend-item img { width: 44px; height: 44px; object-fit: contain; flex-shrink: 0; }
    .cg-pend-info { flex: 1; min-width: 0; }
    .cg-pend-info b { display: block; color: #fff; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cg-pend-info span { color: #8b95a7; font-size: 11px; }

    /* BOTONES */
    .cg-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 38px; padding: 0 18px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; border: 1px solid transparent; transition: 0.2s; white-space: nowrap; }
    .cg-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .cg-btn-green { background: #238636; color: #fff; }
    .cg-btn-green:hover { background: #2ea043; }
    .cg-btn-ghost { background: transparent; border-color: #30363d; color: #a0a6b1; }
    .cg-btn-ghost:hover { border-color: #8b95a7; color: #fff; }

    /* JUEGO */
    .cg-game { display: none; text-align: center; flex: 1; flex-direction: column; justify-content: center; align-items: center; }
    .cg-game h3 { color: #fff; margin: 0 0 4px; font-size: 22px; }
    .cg-game-cost { color: #6b7280; font-size: 12px; margin: 0; }
    .cg-game-cost span { color: var(--cg-a); font-weight: 700; }
    .cg-note { margin-top: 18px; font-size: 11px; color: #5b6472; }

    .cg-roulette { position: relative; width: 100%; max-width: 640px; height: 148px; margin: 26px auto; border-radius: 14px; overflow: hidden; background: linear-gradient(180deg, #0a0c10, #12151b); border: 1px solid rgba(var(--cg-rgb), 0.55); box-shadow: 0 0 0 4px rgba(var(--cg-rgb), 0.08), 0 18px 40px rgba(0,0,0,0.6), inset 0 0 30px rgba(0,0,0,0.8); }
    .cg-roulette::before, .cg-roulette::after { content: ""; position: absolute; top: 0; bottom: 0; width: 90px; z-index: 5; pointer-events: none; }
    .cg-roulette::before { left: 0; background: linear-gradient(90deg, #0a0c10, transparent); }
    .cg-roulette::after { right: 0; background: linear-gradient(270deg, #0a0c10, transparent); }
    .cg-marker { position: absolute; top: 0; bottom: 0; left: 50%; width: 2px; margin-left: -1px; z-index: 6; background: linear-gradient(180deg, var(--cg-a), rgba(var(--cg-rgb), 0.2), var(--cg-a)); box-shadow: 0 0 12px rgba(var(--cg-rgb), 0.9); }
    .cg-marker::before, .cg-marker::after { content: ""; position: absolute; left: 50%; margin-left: -9px; border-left: 9px solid transparent; border-right: 9px solid transparent; }
    .cg-marker::before { top: 0; border-top: 11px solid var(--cg-a); }
    .cg-marker::after { bottom: 0; border-bottom: 11px solid var(--cg-a); }
    .cg-track { display: flex; height: 100%; will-change: transform; }
    .cg-r-item { flex: 0 0 120px; width: 120px; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; border-right: 1px solid rgba(255,255,255,0.05); background: radial-gradient(circle at 50% 40%, rgba(255,255,255,0.05), transparent 65%); }
    .cg-r-item img { width: 62px; height: 62px; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.7)); }
    .cg-r-item span { color: #9aa3b2; font-size: 10px; width: 88%; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cg-r-item.is-exc { background: radial-gradient(circle at 50% 40%, rgba(61,220,132,0.12), transparent 65%); }
    .cg-r-item.is-exc span { color: #3ddc84; }
    .cg-r-item.is-winner { background: radial-gradient(circle at 50% 45%, rgba(var(--cg-rgb), 0.45), transparent 70%); }
    .cg-r-item.is-winner span { color: #fff; font-weight: 700; }
    .cg-r-item.is-winner img { animation: cgPulse 0.9s ease-in-out infinite alternate; }
    @keyframes cgPulse { from { transform: scale(1); } to { transform: scale(1.18); } }

    .cg-spin { background: linear-gradient(135deg, var(--cg-a), var(--ucp-accent-deep, #d35400)); border: none; color: #fff; padding: 14px 52px; font-weight: 800; border-radius: 50px; font-size: 19px; cursor: pointer; transition: 0.25s; letter-spacing: 2px; box-shadow: 0 0 22px rgba(var(--cg-rgb), 0.4); }
    .cg-spin:hover { transform: scale(1.05); filter: brightness(1.15); }
    .cg-spin:disabled { opacity: 0.45; cursor: not-allowed; transform: none; filter: grayscale(1); }

    /* VENTANAS */
    .cg-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; padding: 16px; background: rgba(0,0,0,0.85); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .cg-modal.is-open { display: flex; }
    .cg-box { background: #12161c; border: 1px solid #2a313c; border-radius: 12px; width: 100%; max-width: 400px; padding: 26px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.8); animation: cgIn 0.2s ease; }
    .cg-box.is-accent { border-color: rgba(var(--cg-rgb), 0.7); box-shadow: 0 0 45px rgba(var(--cg-rgb), 0.25); }
    @keyframes cgIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
    .cg-box h4 { color: var(--cg-a); font-size: 17px; margin: 0 0 12px; }
    .cg-box p { color: #c9d1d9; font-size: 14px; margin: 0 0 6px; line-height: 1.5; }
    .cg-box p.cg-soft { color: #8b95a7; font-size: 12px; }
    .cg-btns { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
    .cg-win-title { color: var(--cg-a); font-family: 'Cinzel', serif; font-size: 22px; margin-bottom: 16px; }
    .cg-win-img { height: 96px; margin-bottom: 14px; filter: drop-shadow(0 0 15px rgba(255,255,255,0.3)); }
    .cg-win-name { color: #fff; font-size: 18px; font-weight: 700; display: block; margin-bottom: 14px; }
    .cg-msg-icon { font-size: 40px; margin-bottom: 10px; }
    .cg-msg-icon.is-success { color: #2ea043; }
    .cg-msg-icon.is-error { color: #f85149; }
    .cg-msg-icon.is-info { color: #58a6ff; }
    .cg-msg-title { display: block; font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px; }
    .cg-msg-text { color: #c9d1d9; font-size: 14px; line-height: 1.5; }
    /* TOOLTIP DE ITEMS */
    .cg-r-item { cursor: help; }
    .cg-tt { --cg-rgb: var(--ucp-accent-rgb, 243, 156, 18); position: fixed; z-index: 10050; display: none; max-width: 300px; padding: 10px 14px; background: rgba(8,10,14,0.97); border: 1px solid rgba(var(--cg-rgb), 0.5); border-radius: 8px; box-shadow: 0 12px 30px rgba(0,0,0,0.7); pointer-events: none; text-align: center; }
    .cg-tt, .cg-win-tip { font-size: 12px; line-height: 1.55; color: #9aadcd; }
    .cg-win-tip { margin: -4px auto 14px; }
    .cg-tt-name { color: #fff; font-weight: 700; font-size: 13px; padding-bottom: 5px; margin-bottom: 5px; border-bottom: 1px solid #333a45; }
    .cg-tt-name.is-exc { color: #2ecc71; }
    .cg-win-tip .cg-tt-name { display: none; }
    .cg-tt-380 { color: #d000d0; font-weight: 700; }
    .cg-tt-harm { color: #ffff00; }
    .cg-tt-opt { color: #9aadcd; }
    .cg-tt-exc { color: #2ecc71; }
    .cg-tt-sock { color: #cca4e3; }
    .cg-tt-sep { border-top: 1px solid #333a45; margin: 5px 0 3px; }

    @media (max-width: 991px) {
        .cg-cover { flex: 1 1 100%; }
        .cg-frame { min-height: 200px; }
    }
    @media (max-width: 575px) {
        .cg-container { padding: 14px; gap: 18px; }
        .cg-pend-item { flex-wrap: wrap; }
        .cg-pend-item .cg-btn { width: 100%; }
        .cg-roulette { height: 132px; }
        .cg-spin { padding: 12px 40px; font-size: 17px; }
    }
</style>

<div class="cg-container">
    <div class="cg-cover">
        <div class="cg-frame">
            <i class="cg-corner tl"></i><i class="cg-corner tr"></i>
            <i class="cg-corner bl"></i><i class="cg-corner br"></i>
        </div>
    </div>

    <div class="cg-content">
        <div id="cg-select">
            <div class="cg-intro">
                <h2><?php echo $cgH($cgTitle); ?></h2>
                <p><?php echo $cgH($cgDesc); ?></p>
            </div>

            <div id="cg-pending" class="cg-pending" style="display:none;">
                <h4><i class="fa fa-gift"></i> Premios pendientes <span id="cg-pending-count" class="cg-count">0</span></h4>
                <p class="cg-pending-help">Para retirarlos, tu cuenta debe estar desconectada del juego y tu ba&uacute;l vac&iacute;o.</p>
                <div id="cg-pending-list"></div>
            </div>

            <h4 class="cg-subtitle">Selecciona tu cofre</h4>
            <div class="cg-box-grid" id="cg-box-grid">
                <?php if(empty($cgBoxes)): ?>
                    <div class="cg-empty">No hay cofres disponibles por el momento.</div>
                <?php else: foreach($cgBoxes as $cgBox):
                    $cgImg = !empty($cgBox['img']) ? __BASE_URL__ . $cgBox['img'] : __BASE_URL__ . 'img/boxes/no_box.png';
                    $cgCost = number_format(isset($cgBox['cost']) ? (int)$cgBox['cost'] : 0) . ' ' . (isset($cgBox['currency']) ? $cgBox['currency'] : '');
                    $cgNames = array();
                    foreach(array_slice($cgBox['rewards'], 0, 3) as $cgRw) { if(isset($cgRw['name'])) $cgNames[] = $cgRw['name']; }
                    $cgList = implode(', ', $cgNames) . (count($cgBox['rewards']) > 3 ? '...' : '');
                ?>
                <div class="cg-box-card" data-id="<?php echo $cgH($cgBox['id']); ?>" data-name="<?php echo $cgH(isset($cgBox['name']) ? $cgBox['name'] : ''); ?>" data-cost="<?php echo $cgH($cgCost); ?>">
                    <img src="<?php echo $cgH($cgImg); ?>" alt="">
                    <span class="cg-box-title"><?php echo $cgH(isset($cgBox['name']) ? $cgBox['name'] : ''); ?></span>
                    <div class="cg-box-cost"><?php echo $cgH($cgCost); ?></div>
                    <div class="cg-box-contents"><?php echo $cgH($cgList); ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div id="cg-game" class="cg-game">
            <h3 id="cg-game-name"></h3>
            <p class="cg-game-cost">Costo: <span id="cg-game-cost"></span> (ya debitado)</p>
            <div class="cg-roulette" id="cg-roulette"><div class="cg-marker"></div><div class="cg-track" id="cg-track"></div></div>
            <button type="button" class="cg-spin" id="cg-spin">&iexcl;GIRAR!</button>
            <div class="cg-note">El premio ya est&aacute; guardado en tu cuenta. La animaci&oacute;n es solo visual.</div>
        </div>
    </div>
</div>

<div id="cg-modal-win" class="cg-modal">
    <div class="cg-box is-accent">
        <div class="cg-win-title">&iexcl;RECOMPENSA OBTENIDA!</div>
        <img src="" id="cg-win-img" class="cg-win-img" alt="">
        <span class="cg-win-name" id="cg-win-name"></span>
        <div class="cg-win-tip" id="cg-win-tip"></div>
        <p class="cg-soft">&iquest;Deseas enviar este item a tu ba&uacute;l ahora?</p>
        <div class="cg-btns">
            <button type="button" class="cg-btn cg-btn-ghost" id="cg-win-keep">Guardar para despu&eacute;s</button>
            <button type="button" class="cg-btn cg-btn-green" id="cg-win-claim">Enviar al ba&uacute;l</button>
        </div>
    </div>
</div>

<div id="cg-modal-open" class="cg-modal">
    <div class="cg-box is-accent">
        <h4><i class="fa fa-dice"></i> ABRIR COFRE</h4>
        <p>Est&aacute;s a punto de abrir <b id="cg-open-name"></b>.</p>
        <p class="cg-soft">Costo: <b id="cg-open-cost" style="color:#fff;"></b></p>
        <div class="cg-btns">
            <button type="button" class="cg-btn cg-btn-ghost" id="cg-open-no">Cancelar</button>
            <button type="button" class="cg-btn cg-btn-green" id="cg-open-yes">&iexcl;Abrir cofre!</button>
        </div>
    </div>
</div>

<div id="cg-modal-vault" class="cg-modal">
    <div class="cg-box is-accent">
        <h4><i class="fa fa-box-open"></i> REVISI&Oacute;N DE BA&Uacute;L</h4>
        <p>Para recibir el premio, tu ba&uacute;l debe estar <b>completamente vac&iacute;o</b> y tu cuenta desconectada del juego.</p>
        <p class="cg-soft">&iquest;Confirmas que tu ba&uacute;l est&aacute; vac&iacute;o?</p>
        <div class="cg-btns">
            <button type="button" class="cg-btn cg-btn-ghost" id="cg-vault-no">Cancelar</button>
            <button type="button" class="cg-btn cg-btn-green" id="cg-vault-yes">S&iacute;, est&aacute; vac&iacute;o</button>
        </div>
    </div>
</div>

<div id="cg-modal-msg" class="cg-modal">
    <div class="cg-box">
        <div class="cg-msg-icon" id="cg-msg-icon"></div>
        <span class="cg-msg-title" id="cg-msg-title"></span>
        <div class="cg-msg-text" id="cg-msg-text"></div>
        <div class="cg-btns"><button type="button" class="cg-btn cg-btn-ghost" id="cg-msg-ok">Entendido</button></div>
    </div>
</div>

<script>
(function(){
    var BASE = "<?php echo __BASE_URL__; ?>";
    var ITEM_W = 120, PRE = 40;
    var pending = <?php echo $cgPendingJson; ?>;
    var cur = null, game = null, claimId = 0, busy = false;
    var cur = null, game = null, claimId = 0, busy = false, tips = {};

    function el(id){ return document.getElementById(id); }
    function esc(s){ var d = document.createElement("div"); d.textContent = (s == null ? "" : String(s)); return d.innerHTML; }
    function img(g, i){ return BASE + "img/items/" + parseInt(g, 10) + "-" + parseInt(i, 10) + ".gif"; }
    function show(id){ el(id).classList.add("is-open"); }
    function hide(id){ el(id).classList.remove("is-open"); }

    function post(data){
        var fd = new FormData();
        for(var k in data){ if(Object.prototype.hasOwnProperty.call(data, k)) fd.append(k, data[k]); }
        return fetch(window.location.href, { method: "POST", body: fd, credentials: "same-origin" }).then(function(r){ return r.json(); });
    }

    function msg(title, html, type){
        var icons = { success: "fa-check-circle", error: "fa-times-circle", info: "fa-info-circle" };
        if(!icons[type]) type = "info";
        el("cg-msg-icon").className = "cg-msg-icon is-" + type;
        el("cg-msg-icon").innerHTML = '<i class="fa ' + icons[type] + '"></i>';
        el("cg-msg-title").textContent = title;
        el("cg-msg-text").innerHTML = html;
        show("cg-modal-msg");
    }

    function renderPending(){
        var wrap = el("cg-pending");
        if(!pending.length){ wrap.style.display = "none"; return; }
        var h = "";
        pending.forEach(function(p){
            h += '<div class="cg-pend-item"><img src="' + img(p.group, p.index) + '" alt="">'
               + '<div class="cg-pend-info"><b>' + esc(p.name) + (p.level > 0 ? " +" + parseInt(p.level, 10) : "") + '</b><span>' + esc(p.box) + '</span></div>'
               + '<button type="button" class="cg-btn cg-btn-green" data-claim="' + parseInt(p.id, 10) + '">Enviar al ba\u00FAl</button></div>';
        });
        el("cg-pending-list").innerHTML = h;
        el("cg-pending-count").textContent = pending.length;
        wrap.style.display = "block";
    }

    function backToSelect(){
        el("cg-game").style.display = "none";
        el("cg-select").style.display = "block";
    }

    function itemHtml(it, isTarget){
        return '<div class="cg-r-item' + (it.exc ? " is-exc" : "") + '" data-rid="' + esc(it.id) + '"' + (isTarget ? ' id="cg-target"' : "") + '>'
             + '<img src="' + img(it.group, it.item_id) + '" alt=""><span>' + esc(it.name) + '</span></div>';
    }

    function setupBoard(){
        el("cg-select").style.display = "none";
        el("cg-game").style.display = "flex";
        el("cg-game-name").textContent = cur.name;
        el("cg-game-cost").textContent = cur.cost;

        var pool = (game.pool && game.pool.length) ? game.pool : [game.winner];
        tips = {};
        pool.forEach(function(p){ tips[p.id] = p.tip || ""; });
        tips[game.winner.id] = game.winner.tip || "";
        var track = el("cg-track"), h = "", i;
        for(i = 0; i < PRE; i++) h += itemHtml(pool[Math.floor(Math.random() * pool.length)], false);
        h += itemHtml(game.winner, true);
        for(i = 0; i < 6; i++) h += itemHtml(pool[Math.floor(Math.random() * pool.length)], false);

        track.style.transition = "none";
        track.style.transform = "translateX(0px)";
        track.innerHTML = h;
        void track.offsetWidth;
        el("cg-spin").disabled = false;
    }

    function showWinner(){
        var w = game.winner;
        el("cg-win-name").textContent = w.name + (w.level > 0 ? " +" + w.level : "");
        el("cg-win-img").src = img(w.group, w.item_id);
        el("cg-win-tip").innerHTML = w.tip || "";
        pending.unshift({ id: game.pending_id, box: cur.name, name: w.name, group: w.group, index: w.item_id, level: w.level });
        renderPending();
        show("cg-modal-win");
    }

    el("cg-spin").onclick = function(){
        if(!game) return;
        this.disabled = true;
        var track = el("cg-track");
        var offset = Math.floor(Math.random() * 70) - 35;
        var x = (PRE * ITEM_W) + (ITEM_W / 2) + offset - (el("cg-roulette").clientWidth / 2);
        track.style.transition = "transform 6s cubic-bezier(0.12, 0.8, 0.14, 1)";
        track.style.transform = "translateX(-" + x + "px)";
        setTimeout(function(){
            var t = el("cg-target");
            if(t) t.classList.add("is-winner");
            setTimeout(showWinner, 900);
        }, 6100);
    };

    var grid = el("cg-box-grid");
    grid.addEventListener("click", function(e){
        var c = e.target.closest(".cg-box-card");
        if(!c) return;
        cur = { id: c.getAttribute("data-id"), name: c.getAttribute("data-name"), cost: c.getAttribute("data-cost") };
        el("cg-open-name").textContent = cur.name;
        el("cg-open-cost").textContent = cur.cost;
        var b = el("cg-open-yes");
        b.disabled = false;
        b.textContent = "\u00A1Abrir cofre!";
        show("cg-modal-open");
    });

    el("cg-open-no").onclick = function(){ hide("cg-modal-open"); };
    el("cg-open-yes").onclick = function(){
        if(busy || !cur) return;
        busy = true;
        this.disabled = true;
        this.textContent = "Procesando...";
        post({ ajax_action: "init_spin", box_id: cur.id }).then(function(res){
            busy = false;
            hide("cg-modal-open");
            if(res && res.status === "success"){ game = res; setupBoard(); }
            else msg("Atenci\u00F3n", (res && res.message) ? res.message : "No se pudo abrir la caja.", "error");
        }).catch(function(){
            busy = false;
            hide("cg-modal-open");
            msg("Error", "Error de conexi\u00F3n con el servidor. Si se realiz\u00F3 el cobro, el premio figura en Premios pendientes al recargar la p\u00E1gina.", "error");
        });
    };

    el("cg-pending-list").addEventListener("click", function(e){
        var b = e.target.closest("[data-claim]");
        if(!b) return;
        claimId = parseInt(b.getAttribute("data-claim"), 10);
        show("cg-modal-vault");
    });

    el("cg-win-keep").onclick = function(){
        hide("cg-modal-win");
        backToSelect();
        msg("Premio guardado", "Qued\u00F3 en tu lista de <b>Premios pendientes</b>. Pod\u00E9s enviarlo a tu ba\u00FAl cuando quieras.", "info");
    };
    el("cg-win-claim").onclick = function(){
        if(!game) return;
        claimId = parseInt(game.pending_id, 10);
        show("cg-modal-vault");
    };

    el("cg-vault-no").onclick = function(){ hide("cg-modal-vault"); };
    el("cg-vault-yes").onclick = function(){
        if(busy || !claimId) return;
        busy = true;
        var id = claimId;
        post({ ajax_action: "claim_reward", pending_id: id }).then(function(res){
            busy = false;
            hide("cg-modal-vault");
            if(res && res.status === "success"){
                pending = pending.filter(function(p){ return parseInt(p.id, 10) !== id; });
                renderPending();
                hide("cg-modal-win");
                backToSelect();
                msg("\u00A1Felicidades!", "El item fue enviado a tu ba\u00FAl.", "success");
            } else {
                msg("Atenci\u00F3n", (res && res.message) ? res.message : "No se pudo entregar el premio.", "error");
            }
        }).catch(function(){
            busy = false;
            hide("cg-modal-vault");
            msg("Error", "Error de comunicaci\u00F3n. El premio sigue en tu lista.", "error");
        });
    };

    el("cg-msg-ok").onclick = function(){ hide("cg-modal-msg"); };
        var tt = document.createElement("div");
    tt.className = "cg-tt";
    document.body.appendChild(tt);
    function ttHide(){ tt.style.display = "none"; }
    function ttShow(item, x, y){
        var html = tips[item.getAttribute("data-rid")];
        if(!html){ ttHide(); return; }
        tt.innerHTML = html;
        tt.style.display = "block";
        var w = tt.offsetWidth, h = tt.offsetHeight;
        var left = Math.min(Math.max(8, x - w / 2), window.innerWidth - w - 8);
        var top = y - h - 16;
        if(top < 8) top = y + 20;
        tt.style.left = left + "px";
        tt.style.top = top + "px";
    }
    var rl = el("cg-roulette");
    rl.addEventListener("mousemove", function(e){ var it = e.target.closest(".cg-r-item"); if(it) ttShow(it, e.clientX, e.clientY); else ttHide(); });
    rl.addEventListener("mouseleave", ttHide);
    rl.addEventListener("click", function(e){
        var it = e.target.closest(".cg-r-item");
        if(!it) return;
        var b = it.getBoundingClientRect();
        ttShow(it, b.left + b.width / 2, b.top);
        e.stopPropagation();
    });
    document.addEventListener("click", ttHide);
    window.addEventListener("scroll", ttHide, true);

    renderPending();
})();
</script>