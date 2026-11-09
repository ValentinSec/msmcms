<?php
/**
 * MuServerMaker CMS - AdminCP Functions
 * https://muservermaker.com/
 */

function admincp_base($module="") {
	if(check_value($module)) return __PATH_ADMINCP_HOME__ . "?module=" . $module;
	return __PATH_ADMINCP_HOME__;
}

function enabledisableCheckboxes($name,$checked,$e_txt,$d_txt) {
	echo '<div class="radio">';
	echo '<label class="radio">';
	if($checked == 1) {
		echo '<input type="radio" name="'.$name.'" value="1" checked>';
	} else {
		echo '<input type="radio" name="'.$name.'" value="1">';
	}
	echo $e_txt;
	echo '</label>';
	echo '<label class="radio">';
	if($checked == 0) {
		echo '<input type="radio" name="'.$name.'" value="0" checked>';
	} else {
		echo '<input type="radio" name="'.$name.'" value="0">';
	}
	echo $d_txt;
	echo '</label>';
	echo '</div>';
}

function tableExists($table_name, $db) {
	$tableExists = $db->query_fetch_single("SELECT * FROM sysobjects WHERE xtype = 'U' AND name = ?", array($table_name));
	if(!$tableExists) return false;
	return true;
}

function checkVersion() {
    // 1. CAMBIO CLAVE: Apuntar a TU panel de licencias
    $url = 'https://licencias.muservermaker.com/api/check_update.php';
    
    // 2. Datos que le enviamos a tu panel
    $fields = array(
        'version' => urlencode(__MUSERVERMAKER_VERSION__), // La versi¨®n que tiene el cliente
        'baseurl' => urlencode(__BASE_URL__),              // Su dominio (sirve para validar licencia)
        'license' => defined('__LICENSE_KEY__') ? __LICENSE_KEY__ : '' // Opcional: si usas key
    );
    
    // Preparar datos para enviar
    $fieldsArray = array();
    foreach($fields as $key => $value) {
        $fieldsArray[] = $key . '=' . $value;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $fieldsArray));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 segundos es suficiente
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MuServerMaker-Updater'); // Tu propio agente
    
    // Si tu panel de licencias no tiene SSL certificado a¨²n, descomenta esto:
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $result = curl_exec($ch);
    curl_close($ch);
    
    if(!$result) return;
    
    $resultArray = json_decode($result, true);
    if($resultArray == null) return;
    
    // Si tu API dice que hay update, devolvemos true
    if(isset($resultArray['update']) && $resultArray['update'] == true) {
        return true; 
        // O podr¨ªas devolver $resultArray['version_nueva'] si quer¨¦s mostrar el n¨²mero
    }
    
    return false;
}

function getDownloadsList() {
	$db = config('SQL_USE_2_DB',true) ? Connection::Database('Me_MuOnline') : Connection::Database('MuOnline');
	
	// Verificar si la constante existe antes de usarla, sino usar fallback
	$table = defined('MUSERVERMAKER_DOWNLOADS') ? MUSERVERMAKER_DOWNLOADS : 'WEBENGINE_DOWNLOADS';
	
	$result = $db->query_fetch("SELECT * FROM ".$table." ORDER BY download_type ASC, download_id ASC");
	if(!is_array($result)) return;
	return $result;
}

function addDownload($title, $description='', $link, $size=0, $type=1) {
	$db = config('SQL_USE_2_DB',true) ? Connection::Database('Me_MuOnline') : Connection::Database('MuOnline');
	if(!check_value($title)) return;
	if(!check_value($link)) return;
	if(!check_value($size)) return;
	if(!check_value($type)) return;
	if(strlen($title) > 100) return;
	if(strlen($description) > 100) return;
	
	$table = defined('MUSERVERMAKER_DOWNLOADS') ? MUSERVERMAKER_DOWNLOADS : 'WEBENGINE_DOWNLOADS';
	
	$result = $db->query("INSERT INTO ".$table." (download_title, download_description, download_link, download_size, download_type) VALUES (?, ?, ?, ?, ?)", array($title, $description, $link, $size, $type));
	if(!$result) return;
	
	@updateDownloadsCache();
	return true;
}

function editDownload($id, $title, $description='', $link, $size=0, $type=1) {
	$db = config('SQL_USE_2_DB',true) ? Connection::Database('Me_MuOnline') : Connection::Database('MuOnline');
	if(!check_value($id)) return;
	if(!check_value($title)) return;
	if(!check_value($link)) return;
	if(!check_value($size)) return;
	if(!check_value($type)) return;
	if(strlen($title) > 100) return;
	if(strlen($description) > 100) return;
	
	$table = defined('MUSERVERMAKER_DOWNLOADS') ? MUSERVERMAKER_DOWNLOADS : 'WEBENGINE_DOWNLOADS';
	
	$result = $db->query("UPDATE ".$table." SET download_title = ?, download_description = ?, download_link = ?, download_size = ?, download_type = ? WHERE download_id = ?", array($title, $description, $link, $size, $type, $id));
	if(!$result) return;
	
	@updateDownloadsCache();
	return true;
}

function deleteDownload($id) {
	$db = config('SQL_USE_2_DB',true) ? Connection::Database('Me_MuOnline') : Connection::Database('MuOnline');
	if(!check_value($id)) return;
	
	$table = defined('MUSERVERMAKER_DOWNLOADS') ? MUSERVERMAKER_DOWNLOADS : 'WEBENGINE_DOWNLOADS';
	
	$result = $db->query("DELETE FROM ".$table." WHERE download_id = ?", array($id));
	if(!$result) return;
	
	@updateDownloadsCache();
	return true;
}

function updateDownloadsCache() {
	$db = config('SQL_USE_2_DB',true) ? Connection::Database('Me_MuOnline') : Connection::Database('MuOnline');
	
	$table = defined('MUSERVERMAKER_DOWNLOADS') ? MUSERVERMAKER_DOWNLOADS : 'WEBENGINE_DOWNLOADS';
	
	$downloadsData = $db->query_fetch("SELECT * FROM ".$table." ORDER BY download_type ASC, download_id ASC");
	$cacheData = encodeCache($downloadsData);
	updateCacheFile('downloads.cache', $cacheData);
	return true;
}

function weekDaySelectOptions($selected='Monday') {
	$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
	$result = '';
	foreach($days as $row) {
		if($selected == $row) {
			$result .= '<option value="'.$row.'" selected>'.$row.'</option>';
		} else {
			$result .= '<option value="'.$row.'">'.$row.'</option>';
		}
	}
	return $result;
}

function webengine_send_mail($to, $subject, $message_content, $username = "Jugador") {
    // 1. Cargar configuraciÃ³n SMTP
    $smtpFile = __DIR__ . '/../../includes/config/smtp_settings.json';
    if(!file_exists($smtpFile)) return false;
    
    $smtp = json_decode(file_get_contents($smtpFile), true);
    if(!is_array($smtp) || !isset($smtp['smtp_active']) || !$smtp['smtp_active']) return false;

    // 2. Cargar librerÃ­as (verificando que existan)
    if(file_exists(__PATH_CLASSES__ . 'phpmailer/PHPMailer.php')) {
        require_once(__PATH_CLASSES__ . 'phpmailer/PHPMailer.php');
        require_once(__PATH_CLASSES__ . 'phpmailer/SMTP.php');
        require_once(__PATH_CLASSES__ . 'phpmailer/Exception.php');

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtp['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['smtp_user'];
            $mail->Password   = $smtp['smtp_pass'];
            $mail->SMTPSecure = $smtp['smtp_crypto'];
            $mail->Port       = $smtp['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            $server_name = config('server_name', true);
            $mail->setFrom($smtp['smtp_user'], $server_name);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;

            $mail->Body = $message_content;
            $mail->AltBody = strip_tags($message_content);

            return $mail->send();

        } catch (Exception $e) {
            error_log("Mail Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    return false;
}