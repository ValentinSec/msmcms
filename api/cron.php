<?php

// access
define('access', 'api');

try {
	
	if(!@include_once(rtrim(str_replace('\\','/', dirname(__DIR__)), '/') . '/includes/muservermaker.php')) throw new Exception('Could not load MuServerMaker CMS.');
	
	// Check Status
	if(config('cron_api',true) == false) throw new Exception('Cron api disabled.');
	
	// Forzar un cron puntual exige la clave. La ejecucion normal (solo crons vencidos) no la necesita.
	$forcedId = isset($_GET['id']) ? (string)$_GET['id'] : null;
	if($forcedId !== null) {
		$configuredKey = (string)config('cron_api_key',true);
		$sentKey = (isset($_REQUEST['key']) && is_string($_REQUEST['key'])) ? $_REQUEST['key'] : '';
		if(!check_value($configuredKey)) throw new Exception('Configured cron api key is not valid.');
		if(!hash_equals($configuredKey, $sentKey)) throw new Exception('Key is not valid.');
	}
	
	// Una sola ejecucion a la vez
	$cronLock = @fopen(__PATH_INCLUDES__ . 'cache/cron.lock', 'c');
	if($cronLock && !flock($cronLock, LOCK_EX | LOCK_NB)) {
		http_response_code(200);
		header('Content-Type: application/json');
		echo json_encode(array('code' => 200, 'message' => 'Crons already running.', 'executed' => array()), JSON_PRETTY_PRINT);
		exit;
	}
	
	// Cron Manager
	$cronManager = new CronManager();
	$executedCrons = array();
	
	// Cron List
	$cronList = $cronManager->getCronList();
	if(!is_array($cronList)) throw new Exception('There are no crons.');
	
	// Encapsulation
	function loadCronFile($path) {
		include($path);
	}
	
	if($forcedId === null) {
		// Execute All Enabled Crons (solo los vencidos)
		foreach($cronList as $cron) {
			if($cron['cron_status'] != 1) continue;
			if(!check_value($cron['cron_last_run'])) {
				$lastRun = $cron['cron_run_time'];
			} else {
				$lastRun = $cron['cron_last_run']+$cron['cron_run_time'];
			}
			if(time() > $lastRun) {
				$filePath = __PATH_CRON__.$cron['cron_file_run'];
				if(file_exists($filePath)) {
					loadCronFile($filePath);
					$executedCrons[] = $cron['cron_file_run'];
				}
			}
		}
	} else {
		// Execute single cron (regardless of status and last run)
		$singleCronExecuted = false;
		foreach($cronList as $cron) {
			if((string)$cron['cron_id'] !== $forcedId) continue;
			$filePath = __PATH_CRON__.$cron['cron_file_run'];
			if(file_exists($filePath)) {
				loadCronFile($filePath);
				$executedCrons[] = $cron['cron_file_run'];
				$singleCronExecuted = true;
			}
		}
		if(!$singleCronExecuted) throw new Exception('The provided cron id is not valid.');
	}
	
	if($cronLock) { flock($cronLock, LOCK_UN); fclose($cronLock); }
	
	http_response_code(200);
	header('Content-Type: application/json');
	echo json_encode(array('code' => 200, 'message' => 'Crons successfully executed.', 'executed' => $executedCrons), JSON_PRETTY_PRINT);
	
} catch(Exception $ex) {
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode(array('code' => 500, 'error' => $ex->getMessage()), JSON_PRETTY_PRINT);
}