<?php
/**
 * register.php - OBFUSCATION SAFE VERSION
 * Converted to Pure PHP Echo
 */

if(isLoggedIn()) redirect();

// --- 1. LÓGICA DE CAPTURA DE REFERIDO (AL INICIO) ---
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $refName = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['ref']);
    $_SESSION['wb_referral'] = $refName;
} 

// Variables para HTML
$cssPath = __PATH_TEMPLATE_CSS__;
$serverName = custom_config("server_name");
$baseUrl = __BASE_URL__;

// --- INICIO RENDERIZADO ---

echo '<link href="' . $cssPath . 'register.css?v=20261112a" rel="stylesheet">';

echo '<div id="downloads-page-balduneta">';
    
    // Header
    echo '<div class="rank-header text-center mb-5">';
    echo '<h1 class="rank-title">REGISTRATE</h1>';
    echo '<p class="rank-subtitle">Únete a ' . $serverName . ' y comienza tu aventura épica</p>';
    echo '</div>';

echo '<div id="register-page-balduneta">';
    echo '<div class="register-main-container p-5">';
        
        // Alerta de Referido
        if(isset($_SESSION['wb_referral'])) {
            echo '<div class="alert alert-info text-center" style="background: rgba(0, 208, 132, 0.1); border: 1px solid #00d084; color: #fff;">';
            echo '<i class="fa fa-user-plus"></i> Has sido invitado por: <b>' . htmlspecialchars($_SESSION['wb_referral']) . '</b>';
            echo '</div>';
        }

        try {
            if(!mconfig('active')) throw new Exception(lang('error_17',true));
            
            // PROCESAMIENTO DEL FORMULARIO
            if(isset($_POST['msmRegister_submit'])) {
                try {
                    // --- VALIDACIÓN DE SEGURIDAD RECAPTCHA ---
                    $secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
                    if(file_exists($secConfigFile)) {
                        $secConfig = json_decode(file_get_contents($secConfigFile), true);
                        if(isset($secConfig['recaptcha_active']) && $secConfig['recaptcha_active'] == 1) {
                            if(!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
                                throw new Exception("Por favor, completa el captcha de seguridad.");
                            }
                        }
                    }
            
                    $Account = new Account();
                    $user  = $_POST['msmRegister_user'];
                    $pass  = $_POST['msmRegister_pwd'];
                    $passc = $_POST['msmRegister_pwdc'];
                    $email = $_POST['msmRegister_email'];
                    $idCode = isset($_POST['register_id_code']) ? $_POST['register_id_code'] : '';
            
                    // 1. REGISTRO DE CUENTA
                    $Account->registerAccount($user, $pass, $passc, $email, $idCode);
                    
                    // 2. LÓGICA DE PREMIOS Y REFERIDOS
                    try {
                        $regConfig = mconfig(); 
                        $customPath = __PATH_INCLUDES__ . 'config/custom_settings.json';
                        $custom = file_exists($customPath) ? json_decode(file_get_contents($customPath), true) : [];
                        $database = Connection::Database('Me_MuOnline');
            
                        $database->query("UPDATE MEMB_INFO SET appl_days = GETDATE() WHERE memb___id = ?", [$user]);

                        $refActive = (isset($custom['referral_active']) && $custom['referral_active'] == 1);
                        if ($refActive && isset($_SESSION['wb_referral']) && !empty($_SESSION['wb_referral'])) {
                            $referrerName = $_SESSION['wb_referral'];
                            if (strtolower($referrerName) !== strtolower($user)) {
                                $checkRef = $database->query_fetch_single("SELECT memb___id FROM MEMB_INFO WHERE memb___id = ?", [$referrerName]);
                                if ($checkRef) {
                                    $database->query("UPDATE MEMB_INFO SET referral_by = ? WHERE memb___id = ?", [$referrerName, $user]);
                                }
                            }
                        }

                        // COINS
                        $cAmount = (int)$regConfig['register_coins_amount'];
                        if($cAmount > 0) {
                            $cTable  = (string)(isset($custom['table_coins']) ? $custom['table_coins'] : 'CashShopData');
                            $cColumn = (string)(isset($custom['col_coin_1']) ? $custom['col_coin_1'] : 'WCoinC');
                            $cId = ($cTable == "CashShopData") ? "AccountID" : "memb___id";
                            $check = $database->query_fetch_single("SELECT $cId FROM $cTable WHERE $cId = ?", [$user]);
                            if(!$check) {
                                $database->query("INSERT INTO $cTable ($cId, $cColumn, WCoinP, GoblinPoint) VALUES (?, ?, 0, 0)", [$user, $cAmount]);
                            } else {
                                $database->query("UPDATE $cTable SET $cColumn = $cColumn + ? WHERE $cId = ?", [$cAmount, $user]);
                            }
                        }
            
                        // VIP
                        $vDays = (int)$regConfig['register_vip_days'];
                        $vType = (int)$regConfig['register_vip_type'];
                        if($vDays > 0 && $vType > 0) {
                            $vTable  = (string)(isset($custom['table_vip']) ? $custom['table_vip'] : 'MEMB_INFO');
                            $vColLvl = (string)(isset($custom['col_vip_level']) ? $custom['col_vip_level'] : 'AccountLevel');
                            $vColExp = (string)(isset($custom['col_vip_expire']) ? $custom['col_vip_expire'] : 'AccountExpireDate');
                            $expireDate = date("Y-m-d H:i:s", strtotime("+$vDays days"));
                            $database->query("UPDATE $vTable SET $vColLvl = ?, $vColExp = ? WHERE memb___id = ?", [$vType, $expireDate, $user]);
                        }
                    } catch (Exception $e) { }

                    // --- [BLOQUE DE AUTO-LOGIN Y REDIRECCIÓN] ---
                    if(class_exists('login')) {
                        $autoLogin = new login();
                        $autoLogin->validateLogin($user, $pass);
                    }
                    if (ob_get_length()) ob_clean();
                    header("Location: " . __BASE_URL__);
                    exit;
                    
                } catch (Exception $ex) {
                    message('error', $ex->getMessage());
                }
            }

            // FORMULARIO HTML
            echo '<form action="" method="post" class="register-form">';
            
            // Usuario
            echo '<div class="form-group mb-4">';
            echo '<label class="register-label">NOMBRE DE USUARIO</label>';
            echo '<div class="input-container">';
            echo '<i class="fa fa-user input-icon"></i>';
            echo '<input type="text" name="msmRegister_user" class="form-control-balduneta" placeholder="Usuario" required>';
            echo '</div></div>';

            // Email
            echo '<div class="form-group mb-4">';
            echo '<label class="register-label">CORREO ELECTRÓNICO</label>';
            echo '<div class="input-container">';
            echo '<i class="fa fa-envelope input-icon"></i>';
            echo '<input type="email" name="msmRegister_email" class="form-control-balduneta" placeholder="Email" required>';
            echo '</div></div>';

            // Password
            echo '<div class="form-group mb-4">';
            echo '<label class="register-label">CONTRASEÑA</label>';
            echo '<div class="input-container">';
            echo '<i class="fa fa-lock input-icon"></i>';
            echo '<input type="password" name="msmRegister_pwd" class="form-control-balduneta" placeholder="Contraseña" required>';
            echo '</div></div>';

            // Confirm Password
            echo '<div class="form-group mb-4">';
            echo '<label class="register-label">CONFIRMAR CONTRASEÑA</label>';
            echo '<div class="input-container">';
            echo '<i class="fa fa-lock input-icon"></i>';
            echo '<input type="password" name="msmRegister_pwdc" class="form-control-balduneta" placeholder="Confirmar" required>';
            echo '</div></div>';

            // ID Code (Opcional)
            if(mconfig('register_enable_idcode')) {
                echo '<div class="form-group mb-4">';
                echo '<label class="register-label">CÓDIGO PERSONAL (7 DÍGITOS)</label>';
                echo '<div class="input-container">';
                echo '<i class="fa fa-id-card input-icon"></i>';
                echo '<input type="text" name="register_id_code" class="form-control-balduneta" placeholder="Ej: 1234567" maxlength="7" pattern="\d*" required>';
                echo '</div></div>';
            }

            // Checkbox TOS
            echo '<div class="checkbox-container mb-3 mt-4">';
            echo '<label class="custom-checkbox">';
            echo '<input type="checkbox" required>';
            echo '<span class="checkmark"></span>';
            echo ' Acepto los <a href="' . $baseUrl . 'tos" target="_blank"> Términos y Condiciones</a>';
            echo '</label></div>';
            
            // Recaptcha
            $secConfigFile = __PATH_INCLUDES__ . 'config/security_config.json';
            if(file_exists($secConfigFile)) {
                $secConfig = json_decode(file_get_contents($secConfigFile), true);
                if(isset($secConfig['recaptcha_active']) && $secConfig['recaptcha_active'] == 1) {
                    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
                    echo '<div class="g-recaptcha mb-4" data-sitekey="' . $secConfig['site_key'] . '" data-theme="dark"></div>';
                }
            }

            // Botón Submit
            echo '<button type="submit" name="msmRegister_submit" class="btn-register-balduneta">REGISTRARSE</button>';
            echo '</form>';

        } catch(Exception $ex) {
            message('error', $ex->getMessage());
        }

    echo '</div>'; // Fin register-main-container
echo '</div>'; // Fin register-page-balduneta
echo '</div>'; // Fin downloads-page-balduneta (container wrapper)
?>