<?php
if (!defined("access") or !access) { die(); }
include "includes/usercp.data.php";

if (!isLoggedIn()) {
    header("Location: " . __BASE_URL__ . "login");
    exit();
}

/* --- 1. CARGA DE CONFIGURACIONES --- */
// A. Configuración General
$customSettingsPath = 'includes/config/custom_settings.json';
$cSettings = file_exists($customSettingsPath) ? json_decode(file_get_contents($customSettingsPath), true) : [];

// Lógica VIP
$vipModuleActive = (isset($cSettings['module_vip_active']) && $cSettings['module_vip_active'] === 'on');

// Lógica Votos
$votesActive = (isset($cSettings['module_votes_active']) && $cSettings['module_votes_active'] === 'on');

// Lógica Referidos
$referralActive = (isset($cSettings['referral_active']) && $cSettings['referral_active'] == 1);

// Lógica WebShop
$webshopActive = (isset($cSettings['module_webshop_active']) && $cSettings['module_webshop_active'] === 'on');

// Lógica Casino Goblin
$casinoActive = (isset($cSettings['module_casino_active']) && $cSettings['module_casino_active'] === 'on');

// Lógica Black Market (NUEVO)
$bmConfigFile = __PATH_INCLUDES__ . 'config/black_market_config.json';
$bmData = file_exists($bmConfigFile) ? json_decode(file_get_contents($bmConfigFile), true) : [];
$blackMarketActive = (isset($bmData['module_active']) && $bmData['module_active'] === 'on');

// B. Configuración Donaciones (Coins)
$donCfgFile = __PATH_INCLUDES__ . 'config/donation_config.json';
$donCfg = file_exists($donCfgFile) ? json_decode(file_get_contents($donCfgFile), true) : [];
$dVal = $donCfg['module_active'] ?? 0;
$donationModuleActive = ($dVal == 1 || $dVal === 'on' || $dVal === true);                                 


/* --- 2. LÓGICA ONLINE --- */
$isUserOnline = false;
try {
    $dbMe = Connection::Database('Me_MuOnline');
    $onlineData = $dbMe->query_fetch_single("SELECT ConnectStat FROM MEMB_STAT WHERE memb___id = ?", [$_SESSION['username']]);
    if (is_array($onlineData) && $onlineData['ConnectStat'] == 1) {
        $isUserOnline = true;
    }
} catch (Exception $e) { $isUserOnline = false; }

/* --- 3. HELPER FUNCTIONS --- */
if (!function_exists("getClassName")) {
    function getClassName($code) {
        $class = [ 0 => "Dark Wizard", 1 => "Soul Master", 2 => "Grand Master", 16 => "Dark Knight", 17 => "Blade Knight", 18 => "Blade Master", 32 => "Elf", 33 => "Muse Elf", 34 => "High Elf", 48 => "Magic Gladiator", 50 => "Duel Master", 64 => "Dark Lord", 66 => "Lord Emperor", 80 => "Summoner", 81 => "Bloody Summoner", 82 => "Dimension Master", 96 => "Rage Fighter", 98 => "Fist Master" ];
        return $class[$code] ?? "Unknown";
    }
}
if (!function_exists("getClassIcon")) {
    function getClassIcon($code) {
        if ($code < 16) return "fa-wand-sparkles";
        if ($code < 32) return "fa-gavel";
        if ($code < 48) return "fa-leaf";
        return "fa-bolt";
    }
}

// --- FIX: DETECCIÓN REAL DEL TEMPLATE DESDE LA CONFIGURACIÓN ---
$isMorpheus = false;
$sysConfigPath = __PATH_INCLUDES__ . 'config/muservermaker.json';
if (file_exists($sysConfigPath)) {
    $sysConf = json_decode(file_get_contents($sysConfigPath), true);
    if (isset($sysConf['website_template']) && $sysConf['website_template'] === 'morpheus') {
        $isMorpheus = true;
    }
}
// ---------------------------------------------------------------

// --- RENDERIZADO HTML SEGURO PARA OFUSCACIÓN (PURE PHP ECHO) ---
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Gestión - ' . custom_config("server_name") . '</title>';
    
if($isMorpheus) {
    echo '
    <link rel="icon" type="image/x-icon" href="' . __BASE_URL__ . 'templates/morpheus/img/favicon.ico?v=' . time() . '">
    <link rel="shortcut icon" type="image/x-icon" href="' . __BASE_URL__ . 'templates/morpheus/img/favicon.ico?v=' . time() . '">';
}

echo '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="' . __BASE_URL__ . 'templates/misc/css/usercp.css" rel="stylesheet">
    ' . ($isMorpheus ? '' : '<link href="' . __BASE_URL__ . 'templates/misc/css/navbar.css" rel="stylesheet">') . '
    
    <style>
        /* --- ESTILOS CORREGIDOS PARA BOTONES DEL SIDEBAR --- */
        .quick-access-buttons {
            display: flex;
            gap: 15px; /* Separación entre botones */
            padding: 0 15px;
            margin-bottom: 20px;
        }

        .btn-quick {
            flex: 1; /* Ocupan 50% cada uno */
            display: flex;
            flex-direction: column; /* ICONO ARRIBA, TEXTO ABAJO */
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.4); /* Fondo oscuro */
            border: 1px solid rgba(215, 73, 33, 0.3); /* Borde naranja sutil */
            color: #ccc;
            transition: all 0.3s ease;
            text-decoration: none;
            min-height: 80px; /* Altura fija para que se vean grandes */
        }

        .btn-quick:hover {
            background: rgba(215, 73, 33, 0.1);
            border-color: rgba(215, 73, 33, 0.8);
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-quick i {
            font-size: 1.5rem; /* Icono grande */
            margin-bottom: 8px; /* Espacio entre icono y texto */
            color: rgb(255, 138, 59); /* Color naranja del icono */
        }

        .btn-quick span {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        /* Variante OFFLINE (Borde Rojo) */
        .btn-quick.offline-mode {
            border-color: rgba(220, 53, 69, 0.3);
        }
        .btn-quick.offline-mode i { color: #dc3545; } /* Icono Rojo */
        .btn-quick.offline-mode:hover { border-color: #dc3545; background: rgba(220, 53, 69, 0.1); }

        /* Estilos generales previos */
        .payment-icon-box { width: 50px; height: 40px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: rgba(255, 255, 255, 0.03); border-radius: 4px; padding: 4px; }        
        .modal-vip-content { background: #15171c; border: 1px solid #2d323e; color: #fff; border-radius: 8px; box-shadow: 0 0 50px rgba(0,0,0,0.8); }
        .modal-vip-header { border-bottom: 1px solid #2d323e; padding: 20px; background: rgba(0,0,0,0.2); }
        .modal-vip-body { padding: 0; background: #0f1217; min-height: 400px; }
        .vip-tabs { border-bottom: 1px solid #2d323e; background: #111318; padding: 0 20px; display: flex; gap: 5px; overflow-x: auto; }
        .vip-tab-link { background: transparent; border: none; color: #888; padding: 15px 20px; font-weight: bold; text-transform: uppercase; font-size: 12px; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; white-space: nowrap; }
        .vip-tab-link:hover { color: #fff; background: rgba(255,255,255,0.02); }
        .vip-tab-link.active { color: #f39c12; border-bottom-color: #f39c12; background: linear-gradient(to top, rgba(243, 156, 18, 0.1), transparent); }
        .vip-tab-pane { display: none; padding: 30px; animation: fadeIn 0.4s ease; }
        .vip-tab-pane.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .vip-pack-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 20px; text-align: center; transition: all 0.3s ease; position: relative; margin-bottom: 20px; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
        .vip-pack-card:hover { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.2); transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        .pack-badge { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 10px; color: #000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .pack-days { font-size: 28px; font-weight: bold; color: #fff; line-height: 1; margin-bottom: 10px; }
        .pack-days span { font-size: 12px; color: #888; display: block; text-transform: uppercase; font-weight: normal; margin-top: 5px; }
        .pack-price { font-size: 22px; color: #f39c12; font-weight: bold; margin: 15px 0; font-family: \'Cinzel\', serif; }
        .btn-pack-buy { width: 100%; padding: 10px; border: none; border-radius: 6px; font-weight: bold; text-transform: uppercase; color: #fff; background: #2d323e; transition: 0.3s; cursor: pointer; font-size: 13px; }
        .btn-pack-buy:hover { background: #f39c12; color: #000; box-shadow: 0 0 15px rgba(243, 156, 18, 0.4); }
    </style>
</head>
<body class="usercp-body' . ($isMorpheus ? ' page-usercp-morpheus' : '') . '">';

if (!$isMorpheus) {
    include $_SERVER['DOCUMENT_ROOT'] . '/templates/misc/inc/modules/navbar.php'; 
} else {
    include $_SERVER['DOCUMENT_ROOT'] . '/templates/morpheus/inc/modules/navbar.php';
}

echo '
    <div class="usercp-wrapper container-fluid g-0" style="' . ($isMorpheus ? 'padding-top: 0px;' : 'padding-top: 50px;') . '">
        <div class="row g-0 min-vh-100">
            <aside class="col-lg-2 usercp-sidebar d-flex flex-column">
                <div class="sidebar-top">
                    <div class="user-profile-header">
                        <div class="avatar-wrapper">
                            <div class="user-avatar"><i class="fa fa-user"></i></div>
                            <span class="online-status-dot"></span>
                        </div>
                        <div class="header-info">
                            <div class="account-label">Cuenta</div>
                            <h3 class="text-truncate" title="' . $_SESSION["username"] . '">
                                ' . $_SESSION["username"] . '
                            </h3>
                        </div>
                    </div>

                    <div class="quick-access-buttons">';
                        
                        if($isUserOnline) {
                            echo '<button class="btn-quick ' . (!$vipModuleActive ? 'w-100' : '') . '" style="cursor: default;">
                                <i class="fa fa-clock"></i> <span>Online</span>
                            </button>';
                        } else {
                            echo '<button class="btn-quick offline-mode ' . (!$vipModuleActive ? 'w-100' : '') . '" style="cursor: default;">
                                <i class="fa fa-power-off"></i> <span>Offline</span>
                            </button>';
                        }
                      
                        if($vipModuleActive) {
                            echo '<button type="button" class="btn-quick" data-bs-toggle="modal" data-bs-target="#modalBuyVip">
                                <i class="fa fa-star"></i> <span>VIP</span>
                            </button>';
                        }
                      
echo '
                    </div>
                </div>
                
                <div class="sidebar-divider"></div>

                <nav class="usercp-nav-menu flex-grow-1 nav nav-pills flex-column" id="usercpTab" role="tablist">
                    <button class="nav-link nav-card active" id="index-tab" data-bs-toggle="pill" data-bs-target="#pane-index" type="button" role="tab">
                        <div class="icon-box"><i class="fa fa-user-circle"></i></div>
                        <div class="text-box"><h4>Cuenta</h4><p>Información de tu cuenta y...</p></div>
                        <i class="fa fa-chevron-right arrow-icon"></i>
                    </button>

                    <button class="nav-link nav-card" id="characters-tab" data-bs-toggle="pill" data-bs-target="#pane-characters" type="button" role="tab">
                        <div class="icon-box"><i class="fa fa-users"></i></div>
                        <div class="text-box"><h4>Personajes</h4><p>Tus personajes en el servidor</p></div>
                        <i class="fa fa-chevron-right arrow-icon"></i>
                    </button>';

                    if($webshopActive) {
                        echo '<button class="nav-link nav-card" id="webshop-tab" data-bs-toggle="pill" data-bs-target="#pane-webshop" type="button" role="tab">
                            <div class="icon-box"><i class="fa fa-shopping-cart"></i></div>
                            <div class="text-box"><h4>WebShop</h4><p>Tienda de Items</p></div>
                            <i class="fa fa-chevron-right arrow-icon"></i>
                        </button>';
                    }

                    if($donationModuleActive) {
                        echo '<button class="nav-link nav-card" id="donate-tab" data-bs-toggle="pill" data-bs-target="#pane-donate" type="button" role="tab">
                            <div class="icon-box"><i class="fa fa-credit-card"></i></div>
                            <div class="text-box"><h4>Donaciones</h4><p>Apoya al servidor y obtén...</p></div>
                            <i class="fa fa-chevron-right arrow-icon"></i>
                        </button>';
                    }                  

                    if($votesActive) {
                        echo '<button class="nav-link nav-card" id="votes-tab" data-bs-toggle="pill" data-bs-target="#pane-votes" type="button" role="tab">
                            <div class="icon-box"><i class="fa fa-thumbs-up"></i></div>
                            <div class="text-box"><h4>Votos</h4><p>Vota por el servidor y gana...</p></div>
                            <i class="fa fa-chevron-right arrow-icon"></i>
                        </button>';
                    }

                    if($referralActive) {
                        echo '<button class="nav-link nav-card" id="referral-tab" data-bs-toggle="pill" data-bs-target="#pane-referral" type="button" role="tab">
                            <div class="icon-box"><i class="fa fa-users-viewfinder"></i></div>
                            <div class="text-box"><h4>Referidos</h4><p>Invita y gana recompensas...</p></div>
                            <i class="fa fa-chevron-right arrow-icon"></i>
                        </button>';
                    }    

                    if($casinoActive) {
                        echo '<button class="nav-link nav-card" id="casino-tab" data-bs-toggle="pill" data-bs-target="#pane-casino" type="button" role="tab">
                            <div class="icon-box"><i class="fas fa-dice" style="color: #2ecc71;"></i></div>
                            <div class="text-box"><h4>Casino Goblin</h4><p>Prueba tu suerte...</p></div>
                            <i class="fa fa-chevron-right arrow-icon"></i>
                        </button>';
                    }
                    
                    if($blackMarketActive) {
                        echo '<button class="nav-link nav-card" id="bm-tab" data-bs-toggle="pill" data-bs-target="#pane-black-market" type="button" role="tab">
                            <div class="icon-box"><i class="fas fa-user-secret" style="color: #9b59b6;"></i></div>
                            <div class="text-box"><h4>Black Market</h4><p>Mercado de usuarios...</p></div>
                            <i class="fa fa-chevron-right arrow-icon"></i>
                        </button>';
                    }
                    
echo '
                    <button class="nav-link nav-card" id="settings-tab" data-bs-toggle="pill" data-bs-target="#pane-settings" type="button" role="tab">
                        <div class="icon-box"><i class="fa fa-cog"></i></div>
                        <div class="text-box"><h4>Configuración</h4><p>Gestiona la seguridad de...</p></div>
                        <i class="fa fa-chevron-right arrow-icon"></i>
                    </button>
                </nav>

                <div class="sidebar-divider"></div>

                <div class="logout-section">
                    <a href="' . __BASE_URL__ . 'logout" class="btn-logout-custom text-center">
                        <i class="fa fa-power-off me-2"></i><span>Cerrar Sesión</span>
                    </a>
                </div>
            </aside>
            <main class="col-lg-10 usercp-content p-5">
                <div class="tab-content" id="usercpTabContent">
                    <div class="tab-pane fade show active" id="pane-index" role="tabpanel">'; include 'modules/usercp/overview.php'; echo '</div>
                    <div class="tab-pane fade" id="pane-characters" role="tabpanel">'; include 'modules/usercp/characters.php'; echo '</div>';
                    
                    if($webshopActive) {
                        echo '<div class="tab-pane fade" id="pane-webshop" role="tabpanel">'; include 'modules/usercp/webshop.php'; echo '</div>';
                    }

                    if($donationModuleActive) {
                        echo '<div class="tab-pane fade" id="pane-donate" role="tabpanel">'; include 'modules/usercp/donate.php'; echo '</div>';
                    }
                    
                    if($votesActive) {
                        echo '<div class="tab-pane fade" id="pane-votes" role="tabpanel">'; include 'modules/usercp/vote.php'; echo '</div>';
                    }                               
                    
                    if($referralActive) {
                        echo '<div class="tab-pane fade" id="pane-referral" role="tabpanel">'; include 'modules/usercp/referral.php'; echo '</div>';
                    }       

                    if($casinoActive) {
                        echo '<div class="tab-pane fade" id="pane-casino" role="tabpanel">'; include 'modules/usercp/casino.php'; echo '</div>';
                    }

                    if($blackMarketActive) {
                        echo '<div class="tab-pane fade" id="pane-black-market" role="tabpanel">'; include 'modules/usercp/black_market.php'; echo '</div>';
                    }

echo '
                    <div class="tab-pane fade" id="pane-settings" role="tabpanel">'; include 'modules/usercp/config.php'; echo '</div>
                </div>
            </main>
        </div>
    </div>';

if($vipModuleActive) include 'modules/usercp/modal_vip.php';

echo '
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener(\'DOMContentLoaded\', function() {
            // Navegación Sidebar
            var sidebarButtons = document.querySelectorAll(\'#usercpTab button\');
            sidebarButtons.forEach(function(triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl);
                triggerEl.addEventListener(\'click\', function(event) {
                    event.preventDefault();
                    tabTrigger.show();
                    sidebarButtons.forEach(el => el.classList.remove(\'active\'));
                    this.classList.add(\'active\');
                });
            });

            // Métodos de Pago (Donaciones)
            var donationContainer = document.getElementById(\'pane-donate\');
            if(donationContainer) {
                donationContainer.addEventListener(\'click\', function(e) {
                    var btn = e.target.closest(\'button[data-bs-toggle="pill"]\');
                    if(btn && document.getElementById(\'donationMethods\').contains(btn)) {
                        e.preventDefault();
                        const targetId = btn.getAttribute(\'data-bs-target\');
                        const emptyState = document.getElementById(\'pack-empty\');
                        if(emptyState) { emptyState.classList.remove(\'show\', \'active\'); emptyState.style.display = \'none\'; }
                        document.querySelectorAll(\'#donationPacks .tab-pane\').forEach(pane => { pane.classList.remove(\'show\', \'active\'); pane.style.display = \'none\'; });
                        const targetPane = document.querySelector(targetId);
                        if(targetPane) { targetPane.classList.add(\'show\', \'active\'); targetPane.style.display = \'block\'; }
                        document.querySelectorAll(\'#donationMethods button\').forEach(b => b.classList.remove(\'active\'));
                        btn.classList.add(\'active\');
                    }
                });
            }
        });
        
        function selectDonationPack(btn, methodId, packId) {
            const container = btn.closest(\'.tab-pane\');
            container.querySelectorAll(\'.donation-pack-btn\').forEach(b => b.classList.remove(\'selected\'));
            btn.classList.add(\'selected\');
            const payBtn = document.getElementById(\'btn-pay-\' + methodId);
            if(payBtn) {
                payBtn.disabled = false; payBtn.style.opacity = "1";
                payBtn.onclick = function() { window.location.href = "' . __BASE_URL__ . 'api/process_donation.php?method=" + methodId + "&pack_id=" + packId; };
            }
        }

        function filterPacks(btn, methodId, coinName) {
            var container = btn.closest(\'.nav-pills-coins\');
            container.querySelectorAll(\'.nav-link\').forEach(b => b.classList.remove(\'active\'));
            btn.classList.add(\'active\');
            var packs = document.querySelectorAll(\'.pack-item-\' + methodId);
            packs.forEach(pack => {
                if (coinName === \'all\' || pack.getAttribute(\'data-coin\') === coinName) { pack.style.display = \'block\'; } else { pack.style.display = \'none\'; }
            });
        }
    </script>
</body>
</html>';
?>