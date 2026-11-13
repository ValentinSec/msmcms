<?php
/**
 * Contador de visitas del sitio publico.
 * Guarda solo contadores por dia en includes/cache/visits.json.
 * No guarda direcciones IP ni datos personales.
 */

if(!defined('access')) die();

if(!function_exists('msm_visits_file')) {
    function msm_visits_file() {
        return __PATH_INCLUDES__ . 'cache/visits.json';
    }
}

if(!function_exists('msm_count_visit')) {
    function msm_count_visit() {
        // Solo cargas normales de pagina
        if(!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') return;
        if(isset($_GET['ajax'])) return;
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) return;

        // Filtro basico de robots
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
        if($ua === '') return;
        if(preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|scrapy|headless|facebookexternalhit|whatsapp|telegram/i', $ua)) return;

        // Una visita por sesion y por dia
        $today = date('Y-m-d');
        $isNewVisit = false;
        if(session_status() === PHP_SESSION_ACTIVE) {
            if(!isset($_SESSION['msm_visit_day']) || $_SESSION['msm_visit_day'] !== $today) {
                $_SESSION['msm_visit_day'] = $today;
                $isNewVisit = true;
            }
        }

        $fp = @fopen(msm_visits_file(), 'c+');
        if(!$fp) return;
        // Si otro pedido tiene el archivo tomado, esta carga no se cuenta (no se hace esperar al visitante)
        if(!flock($fp, LOCK_EX | LOCK_NB)) { fclose($fp); return; }

        $data = json_decode((string)stream_get_contents($fp), true);
        if(!is_array($data)) $data = array();
        if(!isset($data[$today]) || !is_array($data[$today])) $data[$today] = array('visits' => 0, 'views' => 0);

        $data[$today]['views'] = (int)$data[$today]['views'] + 1;
        if($isNewVisit) $data[$today]['visits'] = (int)$data[$today]['visits'] + 1;

        // Se conservan los ultimos 30 dias
        if(count($data) > 30) {
            ksort($data);
            $data = array_slice($data, -30, null, true);
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

if(!function_exists('msm_visits_read')) {
    function msm_visits_read($days = 7) {
        $data = array();
        $file = msm_visits_file();
        if(file_exists($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if(is_array($decoded)) $data = $decoded;
        }
        $out = array();
        for($i = $days - 1; $i >= 0; $i--) {
            $key = date('Y-m-d', strtotime("-$i days"));
            $out[$key] = array(
                'visits' => isset($data[$key]['visits']) ? (int)$data[$key]['visits'] : 0,
                'views'  => isset($data[$key]['views'])  ? (int)$data[$key]['views']  : 0
            );
        }
        return $out;
    }
}