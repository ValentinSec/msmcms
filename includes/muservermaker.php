<?php
/**
 * CORE SYSTEM & SECURITY - PREPARED FOR OBFUSCATION
 * RSA License Validation + CMS Kernel
 */

// 1. LLAVE P迆BLICA RSA (Cerradura de validaci車n)
$publicKey = "-----BEGIN PUBLIC KEY-----\n" .
"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzfVzHCymPILCcbuU4NyT\n" .
"b65tyyKcfZGhl31MP0W4cxQ/aJlQZitBEYIMN1xkc5m3Kh2dJUe/My/4P2w+8Aiq\n" .
"qX9F8LWSrjYPb8iaH4Yx6SAJhhw4krn58Bh25U6BB/u2k0G2jmPy/fRci6o2aCAf\n" .
"ExYRhLTJI8WrpWwsGytnP/TL5YSDM0EE64lwQrp9IaLkm1jzMs2hXOeY9Pfj6D6f\n" .
"crt+H887EZyvQkZ3rNb6Pq4MFwF6ozcQ1Ty95r/hLerEL067+N4odBK0PXlf9hQn\n" .
"NKwxpvJaj6ftf1thADByTBjJ8WvIBPjcOSF7N+EtOAXeuv2iRvWTc9FiqKK0SxA8\n" .
"OQIDAQAB\n" .
"-----END PUBLIC KEY-----";

// 2. Ubicaci車n del archivo licencia.key
$rutaLicencia = __DIR__ . '/../licencia.key';

// A. Verificar existencia
if (!file_exists($rutaLicencia)) {
    die('<div style="color:red; font-family:sans-serif; padding:20px; text-align:center; border:1px solid red; margin:20px;">' .
        '<h3>Error de Licencia</h3>' .
        '<p>No se encuentra el archivo <b>licencia.key</b> en la carpeta principal.</p>' .
        '</div>');
}

// B. Decodificaci車n
$contenido = file_get_contents($rutaLicencia);
$paquete = base64_decode(trim($contenido));

if (strpos($paquete, ':::') === false) {
    die('Error #101: Archivo de licencia corrupto o inv芍lido.');
}

$partesPaquete = explode(':::', $paquete);
$datosLicencia = $partesPaquete[0];
$firmaDigital = $partesPaquete[1];

// C. VALIDACI車N CRIPTOGR芍FICA RSA
$esLegitimo = openssl_verify($datosLicencia, $firmaDigital, $publicKey, OPENSSL_ALGO_SHA256);

if ($esLegitimo !== 1) {
    die('<h1 style="color:red">ALERTA DE SEGURIDAD</h1><p>Esta licencia ha sido manipulada o es falsa. El sistema se ha detenido.</p>');
}

// D. VALIDACI車N DE DATOS LOCALES
$partes = explode('||', $datosLicencia);
$dominioAsignado = $partes[0];
$fechaVencimiento = $partes[1];
$ipAsignada = isset($partes[2]) ? $partes[2] : null;

$hostActual = preg_replace('#^www\.#', '', strtolower($_SERVER['HTTP_HOST']));

if ($hostActual !== $dominioAsignado) {
    die("<h3>Licencia Inv芍lida</h3><p>Esta licencia pertenece a <b>$dominioAsignado</b>, no a <b>$hostActual</b>.</p>");
}

if ($ipAsignada !== null) {
    $serverIP = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : (isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : '0.0.0.0');
    if ($serverIP !== $ipAsignada) {
        die("<h3>Error de IP</h3><p>Esta licencia est芍 vinculada a la IP <b>$ipAsignada</b>. Tu servidor usa <b>$serverIP</b>.</p>");
    }
}

if ($fechaVencimiento !== 'PERMANENTE') {
    if (date('Y-m-d') > $fechaVencimiento) {
        die("<h3>Licencia Vencida</h3><p>Tu licencia expir車 el dia <b>$fechaVencimiento</b>.</p>");
    }
}

// E. KILL SWITCH (ONLINE CHECK)
$apiUrl = "https://licencias.muservermaker.com/index.php?api_action=check_status&domain=" . urlencode($hostActual);
$apiCtx = stream_context_create(['http' => ['timeout' => 5]]);
$apiResponse = @file_get_contents($apiUrl, false, $apiCtx);

if ($apiResponse) {
    $res = json_decode($apiResponse, true);
    if (isset($res['status'])) {
        if ($res['status'] === 'BANNED') {
            die("<h1 style='color:red'>LICENCIA SUSPENDIDA</h1><p>El administrador ha bloqueado esta licencia. Motivo: {$res['message']}</p>");
        }
        if ($res['status'] === 'INVALID_IP') {
            die("<h1 style='color:red'>ERROR DE IP (Remote)</h1><p>{$res['message']}</p>");
        }
    }
}

// =========================================================
// INICIO DEL KERNEL CMS
// =========================================================

session_name('MuServerMakerSession'); 
if(defined('access') && access != 'cron') {
    @ob_start();
    session_start();
}

define('__MUSERVERMAKER_VERSION__', '1.0.0');
@ini_set('default_charset', 'utf-8');

if(isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])){
    $_SERVER['HTTPS'] = $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ? 'on' : 'off';
}

define('HTTP_HOST', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'CLI');
define('SERVER_PROTOCOL', (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://');
define('__ROOT_DIR__', str_replace('\\','/',dirname(dirname(__FILE__))).'/'); 
define('__RELATIVE_ROOT__', (!empty($_SERVER['SCRIPT_NAME'])) ? str_ireplace(rtrim(str_replace('\\','/', realpath(str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']))), '/'), '', __ROOT_DIR__) : '/');
define('__BASE_URL__', SERVER_PROTOCOL.HTTP_HOST.__RELATIVE_ROOT__); 

define('__PATH_INCLUDES__', __ROOT_DIR__.'includes/');
define('__PATH_TEMPLATES__', __ROOT_DIR__.'templates/');
define('__PATH_LANGUAGES__', __PATH_INCLUDES__ . 'languages/');
define('__PATH_CLASSES__', __PATH_INCLUDES__.'classes/');
define('__PATH_FUNCTIONS__', __PATH_INCLUDES__.'functions/');
define('__PATH_MODULES__', __ROOT_DIR__.'modules/');
define('__PATH_MODULES_USERCP__', __PATH_MODULES__.'usercp/');
define('__PATH_EMAILS__', __PATH_INCLUDES__.'emails/');
define('__PATH_CACHE__', __PATH_INCLUDES__.'cache/');
define('__PATH_ADMINCP__', __ROOT_DIR__.'admincp/');
define('__PATH_ADMINCP_INC__', __ROOT_DIR__.'admincp/inc/');
define('__PATH_ADMINCP_MODULES__', __ROOT_DIR__.'admincp/modules/');
define('__PATH_NEWS_CACHE__', __PATH_CACHE__.'news/');
define('__PATH_NEWS_TRANSLATIONS_CACHE__', __PATH_NEWS_CACHE__.'translations/');
define('__PATH_PLUGINS__', __PATH_INCLUDES__.'plugins/');
define('__PATH_CONFIGS__', __PATH_INCLUDES__.'config/');
define('__PATH_MODULE_CONFIGS__', __PATH_CONFIGS__.'modules/');
define('__PATH_CRON__', __PATH_INCLUDES__.'cron/');
define('__PATH_LOGS__', __PATH_INCLUDES__.'logs/');
define('__PATH_GUILD_PROFILES_CACHE__', __PATH_CACHE__.'profiles/guilds/');
define('__PATH_PLAYER_PROFILES_CACHE__', __PATH_CACHE__.'profiles/players/');

define('__PATH_MODULES_RANKINGS__', __BASE_URL__.'rankings/');
define('__PATH_ADMINCP_HOME__', __BASE_URL__.'admincp/');
define('__PATH_IMG__', __BASE_URL__.'img/');
define('__PATH_COUNTRY_FLAGS__', __PATH_IMG__.'flags/');
define('__PATH_API__', __BASE_URL__.'api/');
define('__PATH_ONLINE_STATUS__', __PATH_IMG__.'online.png');
define('__PATH_OFFLINE_STATUS__', __PATH_IMG__.'offline.png');

define('MUSERVERMAKER_DATABASE_ERRORLOG', __PATH_LOGS__.'database_errors.log');
define('MUSERVERMAKER_WRITABLE_PATHS', __PATH_CONFIGS__.'writable.paths.json');
define('MUSERVERMAKER_PHP_ERRORLOG', __PATH_LOGS__.'php_errors.log');
// Crear carpeta y archivos de log si no existen (evita el error "Not Found / re-upload file")
if(!is_dir(__PATH_LOGS__)) { @mkdir(__PATH_LOGS__, 0755, true); }
foreach(array('database_errors.log', 'php_errors.log') as $__logFile) {
    $__logPath = __PATH_LOGS__ . $__logFile;
    if(!file_exists($__logPath)) { @file_put_contents($__logPath, ''); }
}
unset($__logFile, $__logPath);

ini_set('log_errors', 1);
ini_set('error_log', MUSERVERMAKER_PHP_ERRORLOG);

if(!@include_once(__PATH_CONFIGS__ . 'muservermaker.tables.php')) {
    throw new Exception('Could not load MuServerMaker CMS table definitions.');
}

if(!@include_once(__PATH_CONFIGS__ . 'timezone.php')) throw new Exception('Could not load timezone.');

if(!@include_once(__PATH_CLASSES__ . 'class.database.php')) throw new Exception('Could not load class (database).');
if(!@include_once(__PATH_CLASSES__ . 'class.common.php')) throw new Exception('Could not load class (common).');
if(!@include_once(__PATH_CLASSES__ . 'class.handler.php')) throw new Exception('Could not load class (handler).');
if(!@include_once(__PATH_CLASSES__ . 'class.validator.php')) throw new Exception('Could not load class (validator).');
if(!@include_once(__PATH_CLASSES__ . 'class.login.php')) throw new Exception('Could not load class (login).');
if(!@include_once(__PATH_CLASSES__ . 'class.vote.php')) throw new Exception('Could not load class (vote).');
if(!@include_once(__PATH_CLASSES__ . 'class.character.php')) throw new Exception('Could not load class (character).');
if(!@include_once(__PATH_CLASSES__ . 'phpmailer/autoload.php')) throw new Exception('Could not load class (phpmailer).');
if(!@include_once(__PATH_CLASSES__ . 'class.rankings.php')) throw new Exception('Could not load class (rankings).');
if(!@include_once(__PATH_CLASSES__ . 'class.news.php')) throw new Exception('Could not load class (news).');
if(!@include_once(__PATH_CLASSES__ . 'class.plugins.php')) throw new Exception('Could not load class (plugins).');
if(!@include_once(__PATH_CLASSES__ . 'class.profiles.php')) throw new Exception('Could not load class (profiles).');
if(!@include_once(__PATH_CLASSES__ . 'class.credits.php')) throw new Exception('Could not load class (credits).');
if(!@include_once(__PATH_CLASSES__ . 'class.email.php')) throw new Exception('Could not load class (email).');
if(!@include_once(__PATH_CLASSES__ . 'class.account.php')) throw new Exception('Could not load class (account).');
if(!@include_once(__PATH_CLASSES__ . 'class.connection.php')) throw new Exception('Could not load class (connection).');
if(!@include_once(__PATH_CLASSES__ . 'class.castlesiege.php')) throw new Exception('Could not load class (castlesiege).');
if(!@include_once(__PATH_CLASSES__ . 'class.cron.php')) throw new Exception('Could not load class (cron).');
if(!@include_once(__PATH_CLASSES__ . 'class.cache.php')) throw new Exception('Could not load class (cache).');
if(!@include_once(__PATH_CLASSES__ . 'paypal/PaypalIPN.php')) throw new Exception('Could not load class (PayalIPN).');

if(!@include_once(__PATH_INCLUDES__ . 'functions.php')) throw new Exception('Could not load functions.');

$config = muservermakerConfigs();

if(!isset($config['muservermaker_cms_installed']) || $config['muservermaker_cms_installed'] !== true) {
    if(isset($config['webengine_cms_installed']) && $config['webengine_cms_installed'] === true) {
    } else {
        header('Location: '.__BASE_URL__.'install/');
        die();
    }
}

if(array_key_exists('blacklisted', $config)) {
    throw new Exception('Could not load CMS.');
}

if(!@include_once(__PATH_CONFIGS__ . 'compatibility.php')) throw new Exception('Could not load file compatibility.');

global $muservermaker, $webengine;
$compatArray = isset($muservermaker) ? $muservermaker : (isset($webengine) ? $webengine : null);

if(!isset($compatArray['file_compatibility']) || !array_key_exists(strtolower($config['server_files']), $compatArray['file_compatibility'])) {
    throw new Exception('The server files configuration is not valid.');
}

if(!file_exists(__PATH_TEMPLATES__ . $config['website_template'])) throw new Exception('The default template doesn\'t exist.');

$tableFile = $compatArray['file_compatibility'][strtolower($config['server_files'])]['file'];
if(!@include_once(__PATH_CONFIGS__ . $tableFile)) throw new Exception('Could not load the table definitions.');

if(!$config['system_active'] && access != 'cron') {
    if(!array_key_exists($_SESSION['username'], $config['admins'])) {
        header('Location: ' . $config['maintenance_page']);
        die();
    }
    echo '<div style="text-align:center;border-bottom:1px solid #aa0000;padding:15px;background:#000;color:#ff0000;font-size:12pt;">OFFLINE MODE</div>';
}

if($config['error_reporting']) {
    ini_set('display_errors', true);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', false);
    error_reporting(0);
}

if($config['ip_block_system_enable']) {
    if(checkBlockedIp()) throw new Exception('Your IP address has been blocked.');
}

if($config['plugins_system_enable']) {
    $pluginsCache = loadCache('plugins.cache');
    if(is_array($pluginsCache)) {
        foreach($pluginsCache as $pluginData) {
            if(!is_array($pluginData['files'])) continue;
            foreach($pluginData['files'] as $pluginFile) {
                if(!@include_once(__PATH_PLUGINS__.$pluginData['folder'].'/'.$pluginFile)) throw new Exception('Could not load plugin.');
            }
        }
    }
}

define('__PATH_TEMPLATE_ROOT__', __PATH_TEMPLATES__ . $config['website_template'] . '/');
define('__PATH_TEMPLATE__', __BASE_URL__ . 'templates/' . $config['website_template'] . '/');
define('__PATH_TEMPLATE_IMG__', __PATH_TEMPLATE__ . 'img/');
define('__PATH_TEMPLATE_CSS__', __PATH_TEMPLATE__ . 'css/');
define('__PATH_TEMPLATE_JS__', __PATH_TEMPLATE__ . 'js/');
define('__PATH_TEMPLATE_FONTS__', __PATH_TEMPLATE__ . 'fonts/');

$handler = new Handler();
$handler->loadPage();