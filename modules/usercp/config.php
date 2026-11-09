<?php
/**
 * UserCP Configuration Module
 * Logic: Change Password, Email & Personal Code (Full Security Suite)
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

if (!defined("access") or !access) die();

// --- 1. CARGAR CONFIGURACIÓN REGISTRO ---
$regXmlPath = __PATH_CONFIGS__ . 'modules/register.xml';
$idCodeActive = false;

if (file_exists($regXmlPath)) {
    $regXml = simplexml_load_file($regXmlPath);
    $idCodeActive = ((string)$regXml->register_enable_idcode === "1");
}

$pwdMsg = ''; $pwdMsgType = '';
$emailMsg = ''; $emailMsgType = '';
$codeMsg = ''; $codeMsgType = '';
$actionPerformed = false;

$db = Connection::Database('Me_MuOnline');
$myUser = $_SESSION['username'];

// =================================================================================
// LÓGICA 1: CAMBIAR CONTRASEÑA
// =================================================================================
if(isset($_POST['submit_password'])) {
    $actionPerformed = true;
    try {
        $oldPwd = $_POST['pwd_old'];
        $code   = isset($_POST['pwd_code']) ? $_POST['pwd_code'] : '';
        $newPwd = $_POST['pwd_new'];
        $conPwd = $_POST['pwd_confirm'];

        if(empty($oldPwd) || empty($newPwd) || empty($conPwd)) throw new Exception("Todos los campos son obligatorios.");
        if($newPwd !== $conPwd) throw new Exception("La nueva contraseña y confirmación no coinciden.");
        if(strlen($newPwd) < 4) throw new Exception("La contraseña es muy corta.");
        if($oldPwd === $newPwd) throw new Exception("La nueva contraseña debe ser distinta a la actual.");

        $sqlInfo = "SELECT memb___id, memb__pwd, sno__numb FROM MEMB_INFO WHERE memb___id = ?";
        $acc = $db->query_fetch_single($sqlInfo, array($myUser));
        if(!$acc) throw new Exception("Cuenta no encontrada.");

        $dbPwd = $acc['memb__pwd'];
        $useMD5 = (strlen($dbPwd) === 32 && ctype_xdigit($dbPwd));
        $inputOldHash = $useMD5 ? md5($oldPwd) : $oldPwd;

        if(strtolower($inputOldHash) !== strtolower($dbPwd) && strtoupper($inputOldHash) !== strtoupper($dbPwd)) {
            throw new Exception("La contraseña actual es incorrecta.");
        }

        if ($idCodeActive) {
            $dbCode = trim($acc['sno__numb']);
            if(!empty($dbCode) && strpos($dbCode, '1111111') === false) {
                if(empty($code)) throw new Exception("El Código Personal es obligatorio.");
                if($code !== $dbCode && substr($dbCode, -7) !== $code) throw new Exception("El Código Personal es incorrecto.");
            }
        }

        $finalNewPwd = $useMD5 ? md5($newPwd) : $newPwd;
        $db->query("UPDATE [MEMB_INFO] SET [memb__pwd] = ? WHERE [memb___id] = ?", array($finalNewPwd, $myUser));
        $pwdMsg = "¡Contraseña cambiada exitosamente!";
        $pwdMsgType = "success";
    } catch (Exception $e) {
        $pwdMsg = $e->getMessage();
        $pwdMsgType = "danger";
    }
}

// =================================================================================
// LÓGICA 2: CAMBIAR EMAIL
// =================================================================================
if(isset($_POST['submit_email'])) {
    $actionPerformed = true;
    try {
        $passForEmail = $_POST['email_pwd'];
        $codeForEmail = isset($_POST['email_code']) ? $_POST['email_code'] : '';
        $newEmail     = trim($_POST['email_new']);

        if(empty($passForEmail) || empty($newEmail)) throw new Exception("Debes ingresar tu contraseña y el nuevo email.");
        if(!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) throw new Exception("El formato del correo electrónico no es válido.");

        $sqlInfo = "SELECT memb___id, memb__pwd, sno__numb, mail_addr FROM MEMB_INFO WHERE memb___id = ?";
        $acc = $db->query_fetch_single($sqlInfo, array($myUser));
        if(!$acc) throw new Exception("Cuenta no encontrada.");
        if(strtolower($acc['mail_addr']) == strtolower($newEmail)) throw new Exception("El nuevo correo es igual al actual.");

        $checkDup = $db->query_fetch_single("SELECT memb___id FROM MEMB_INFO WHERE mail_addr = ? AND memb___id != ?", array($newEmail, $myUser));
        if($checkDup) throw new Exception("Ese correo electrónico ya está asociado a otra cuenta.");

        $dbPwd = $acc['memb__pwd'];
        $useMD5 = (strlen($dbPwd) === 32 && ctype_xdigit($dbPwd));
        $inputPassHash = $useMD5 ? md5($passForEmail) : $passForEmail;

        if(strtolower($inputPassHash) !== strtolower($dbPwd) && strtoupper($inputPassHash) !== strtoupper($dbPwd)) {
            throw new Exception("La contraseña ingresada es incorrecta.");
        }

        if ($idCodeActive) {
            $dbCode = trim($acc['sno__numb']);
            if(!empty($dbCode) && strpos($dbCode, '1111111') === false) {
                if(empty($codeForEmail)) throw new Exception("El Código Personal es obligatorio.");
                if($codeForEmail !== $dbCode && substr($dbCode, -7) !== $codeForEmail) throw new Exception("El Código Personal es incorrecto.");
            }
        }

        $db->query("UPDATE [MEMB_INFO] SET [mail_addr] = ? WHERE [memb___id] = ?", array($newEmail, $myUser));
        $emailMsg = "¡Correo electrónico actualizado correctamente!";
        $emailMsgType = "success";
    } catch (Exception $e) {
        $emailMsg = $e->getMessage();
        $emailMsgType = "danger";
    }
}

// =================================================================================
// LÓGICA 3: CAMBIAR CÓDIGO PERSONAL
// =================================================================================
if(isset($_POST['submit_code'])) {
    $actionPerformed = true;
    try {
        $passForCode = $_POST['code_pwd'];
        $currentCode = $_POST['code_old'];
        $newCode     = $_POST['code_new'];
        $conCode     = $_POST['code_confirm'];

        if(empty($passForCode) || empty($currentCode) || empty($newCode) || empty($conCode)) throw new Exception("Todos los campos son obligatorios.");
        if($newCode !== $conCode) throw new Exception("El nuevo código y la confirmación no coinciden.");
        if(!is_numeric($newCode)) throw new Exception("El nuevo código debe contener solo números.");
        if(strlen($newCode) < 4 || strlen($newCode) > 18) throw new Exception("El código debe tener entre 4 y 18 dígitos.");

        $sqlInfo = "SELECT memb___id, memb__pwd, sno__numb FROM MEMB_INFO WHERE memb___id = ?";
        $acc = $db->query_fetch_single($sqlInfo, array($myUser));
        if(!$acc) throw new Exception("Cuenta no encontrada.");

        $dbPwd = $acc['memb__pwd'];
        $useMD5 = (strlen($dbPwd) === 32 && ctype_xdigit($dbPwd));
        $inputPassHash = $useMD5 ? md5($passForCode) : $passForCode;

        if(strtolower($inputPassHash) !== strtolower($dbPwd) && strtoupper($inputPassHash) !== strtoupper($dbPwd)) {
            throw new Exception("La contraseña ingresada es incorrecta.");
        }

        $dbCode = trim($acc['sno__numb']);
        $isCurrentValid = ($currentCode === $dbCode) || (substr($dbCode, -strlen($currentCode)) === $currentCode);
        if(!empty($dbCode) && !$isCurrentValid) throw new Exception("El código personal actual es incorrecto.");

        $db->query("UPDATE [MEMB_INFO] SET [sno__numb] = ? WHERE [memb___id] = ?", array($newCode, $myUser));
        $codeMsg = "¡Código personal actualizado correctamente!";
        $codeMsgType = "success";
    } catch (Exception $e) {
        $codeMsg = $e->getMessage();
        $codeMsgType = "danger";
    }
}

// --- RENDERIZADO HTML ---

echo '<div class="module-title mb-4">';
    echo '<h2 class="text-color text-uppercase fw-bold">CONFIGURACIÓN</h2>';
    echo '<p class="text-color small">Gestiona la seguridad de tu cuenta</p>';
    echo '<div class="title-underline"></div>';
echo '</div>';

echo '<div class="donation-main-container p-4 d-flex flex-column gap-4">';
    
    // Verificación Email Box
    echo '<div class="settings-form-box border-orange">';
        echo '<div class="settings-header">';
            echo '<div class="icon-square bg-orange-soft"><i class="fa fa-envelope"></i></div>';
            echo '<h5 class="text-white text-uppercase m-0">Verificación de Email</h5>';
        echo '</div>';
        echo '<div class="alert-settings-yellow my-3"><i class="fa fa-exclamation-triangle me-2"></i> Requerido para cambiar contraseña o email</div>';
        echo '<div class="settings-benefits mb-3">';
            echo '<h6 class="text-purple small fw-bold text-uppercase">Beneficios:</h6>';
            echo '<ul class="list-unstyled small text-white"><li><i class="fa fa-check text-success me-2"></i> Cambiar contraseña de forma segura</li><li><i class="fa fa-check text-success me-2"></i> Actualizar dirección de email</li><li><i class="fa fa-check text-success me-2"></i> Recuperar cuenta si la pierdes</li><li><i class="fa fa-check text-success me-2"></i> Recibir notificaciones importantes</li></ul>';
        echo '</div>';
        echo '<button class="btn btn-primary-orange w-100 py-2"><i class="fa fa-paper-plane me-2"></i> Enviar Código</button>';
    echo '</div>';

    // Password Box
    echo '<div class="settings-form-box border-blue" id="box-password">';
        echo '<div class="settings-header"><div class="icon-square bg-blue-soft"><i class="fa fa-lock"></i></div><h5 class="text-white text-uppercase m-0">Cambiar Contraseña</h5></div>';
        if(!empty($pwdMsg)) {
            echo '<div class="alert alert-' . $pwdMsgType . ' mt-3 mb-0" style="font-size: 13px;">' . (($pwdMsgType == 'success') ? '<i class="fa fa-check-circle me-1"></i>' : '<i class="fa fa-exclamation-triangle me-1"></i>') . ' ' . $pwdMsg . '</div>';
        }
        echo '<form class="mt-4 row g-3" method="POST" action="#box-password">';
            echo '<div class="col-12"><label class="form-label small text-white">Contraseña actual</label><input type="password" name="pwd_old" class="form-control" placeholder="*********" required></div>';
            if($idCodeActive) {
                echo '<div class="col-12"><label class="form-label small text-white">Código Personal (7 Dígitos)</label><input type="text" name="pwd_code" class="form-control" placeholder="Ej: 1234567" required><div class="form-text text-white-50" style="font-size: 11px;">Requerido por seguridad.</div></div>';
            }
            echo '<div class="col-md-6"><label class="form-label small text-white">Nueva contraseña</label><input type="password" name="pwd_new" class="form-control" required></div>';
            echo '<div class="col-md-6"><label class="form-label small text-white">Confirmar contraseña</label><input type="password" name="pwd_confirm" class="form-control" required></div>';
            echo '<div class="col-12"><button type="submit" name="submit_password" class="btn btn-blue-action w-100 py-2"><i class="fa fa-save me-2"></i> Guardar Cambios</button></div>';
        echo '</form>';
    echo '</div>';

    // Email Box
    echo '<div class="settings-form-box border-purple" id="box-email">';
        echo '<div class="settings-header"><div class="icon-square bg-purple-soft"><i class="fa fa-at"></i></div><h5 class="text-white text-uppercase m-0">Cambiar Email</h5></div>';
        if(!empty($emailMsg)) {
            echo '<div class="alert alert-' . $emailMsgType . ' mt-3 mb-0" style="font-size: 13px;">' . (($emailMsgType == 'success') ? '<i class="fa fa-check-circle me-1"></i>' : '<i class="fa fa-exclamation-triangle me-1"></i>') . ' ' . $emailMsg . '</div>';
        }
        echo '<form class="mt-4 row g-3" method="POST" action="#box-email">';
            echo '<div class="col-12"><label class="form-label small text-white">Contraseña actual</label><input type="password" name="email_pwd" class="form-control" placeholder="*********" required></div>';
            if($idCodeActive) {
                echo '<div class="col-12"><label class="form-label small text-white">Código Personal (7 Dígitos)</label><input type="text" name="email_code" class="form-control" placeholder="Ej: 1234567" required></div>';
            }
            echo '<div class="col-12"><label class="form-label small text-white">Nuevo correo electrónico</label><input type="email" name="email_new" class="form-control" placeholder="nuevo@email.com" required></div>';
            echo '<div class="col-12"><button type="submit" name="submit_email" class="btn btn-purple-action w-100 py-2"><i class="fa fa-envelope me-2"></i> Guardar Cambios</button></div>';
        echo '</form>';
    echo '</div>';

    // Personal Code Box
    echo '<div class="settings-form-box border-gold" id="box-code">';
        echo '<div class="settings-header"><div class="icon-square bg-gold-soft"><i class="fa fa-shield-alt"></i></div><h5 class="text-white text-uppercase m-0">Cambiar Código Personal</h5></div>';
        if(!empty($codeMsg)) {
            echo '<div class="alert alert-' . $codeMsgType . ' mt-3 mb-0" style="font-size: 13px;">' . (($codeMsgType == 'success') ? '<i class="fa fa-check-circle me-1"></i>' : '<i class="fa fa-exclamation-triangle me-1"></i>') . ' ' . $codeMsg . '</div>';
        }
        echo '<form class="mt-4 row g-3" method="POST" action="#box-code">';
            echo '<div class="col-md-6"><label class="form-label small text-white">Contraseña actual</label><input type="password" name="code_pwd" class="form-control" placeholder="*********" required></div>';
            echo '<div class="col-md-6"><label class="form-label small text-white">Código personal actual</label><input type="text" name="code_old" class="form-control" placeholder="Ej: 1234567" required></div>';
            echo '<div class="col-md-6"><label class="form-label small text-white">Nuevo código personal</label><input type="text" name="code_new" class="form-control" placeholder="Ej: 7654321" required></div>';
            echo '<div class="col-md-6"><label class="form-label small text-white">Confirmar nuevo código</label><input type="text" name="code_confirm" class="form-control" placeholder="Ej: 7654321" required></div>';
            echo '<div class="col-12"><button type="submit" name="submit_code" class="btn btn-gold-action w-100 py-2"><i class="fa fa-key me-2"></i> Guardar Cambios</button></div>';
        echo '</form>';
    echo '</div>';

    // Logout Box
    echo '<div class="settings-form-box border-red logout-container-inner">';
        echo '<div class="d-flex align-items-center justify-content-between w-100">';
            echo '<div class="d-flex align-items-center"><div class="icon-square bg-red-soft me-3"><i class="fa fa-sign-out-alt"></i></div><div><h5 class="text-white text-uppercase m-0 small fw-bold">Cerrar Sesión</h5><p class="text-white small m-0">Salir de tu cuenta en este dispositivo</p></div></div>';
            echo '<a href="' . __BASE_URL__ . 'logout" class="btn btn-red-action px-4 py-2"><i class="fa fa-power-off me-2"></i> Cerrar Sesión</a>';
        echo '</div>';
    echo '</div>';

echo '</div>';

echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.history.replaceState) { window.history.replaceState(null, null, window.location.href); }
    var settingsTabBtn = document.getElementById("settings-tab");
    if(settingsTabBtn && !settingsTabBtn.classList.contains("active")) {
        ' . ($actionPerformed ? 'settingsTabBtn.click();' : '') . '
    }
});
</script>';