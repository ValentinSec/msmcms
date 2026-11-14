<?php
/**
 * Disparo de tareas programadas.
 * Solo ejecuta las tareas vencidas; no recibe parametros ni claves.
 */
define('access', 'api');
header('Content-Type: application/json');
header('Cache-Control: no-store');

// Freno: como mucho un intento cada 20 segundos, sin cargar el CMS
$cronTick = dirname(__DIR__) . '/includes/cache/cron.tick';
if((int)@filemtime($cronTick) > time() - 20) {
	echo json_encode(array('code' => 200, 'message' => 'Not due.', 'executed' => array()));
	exit;
}
@touch($cronTick);

try {
	if(!@include_once(dirname(__DIR__) . '/includes/muservermaker.php')) throw new Exception('Could not load MuServerMaker CMS.');

	$cronCfg = muservermakerConfigs();
	if(is_array($cronCfg) && array_key_exists('cron_api', $cronCfg) && $cronCfg['cron_api'] === false) throw new Exception('Cron api disabled.');

	$cronManager = new CronManager();
	$executed = $cronManager->runDue();
	echo json_encode(array('code' => 200, 'message' => 'Crons successfully executed.', 'executed' => $executed));

} catch(Throwable $ex) {
	http_response_code(500);
	echo json_encode(array('code' => 500, 'error' => 'Cron error.'));
}