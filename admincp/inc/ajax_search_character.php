<?php
/**
 * AdminCP - busqueda de personajes en tiempo real (respuesta JSON).
 * Se carga solo desde admincp/index.php, despues de validar sesion, admin, PIN y token anti-CSRF.
 */
if(!defined('access') || access !== 'admincp') die();

$out = array();
try {
    $term = (isset($_POST['term']) && is_string($_POST['term'])) ? trim($_POST['term']) : '';
    if($term !== '' && strlen($term) <= 20) {
        // Los comodines de LIKE escritos por el usuario se buscan como texto
        $like = strtr($term, array('[' => '[[]', '%' => '[%]', '_' => '[_]'));
        $tbl = defined('_TBL_CHR_') ? _TBL_CHR_ : 'Character';
        $col = defined('_CLMN_CHR_NAME_') ? _CLMN_CHR_NAME_ : 'Name';
        $db = Connection::Database('MuOnline');
        if(is_object($db)) {
            $rows = $db->query_fetch(
                "SELECT TOP 10 $col AS Name FROM $tbl WHERE $col LIKE ? ORDER BY CASE WHEN $col LIKE ? THEN 0 ELSE 1 END, $col",
                array('%' . $like . '%', $like . '%')
            );
            if(is_array($rows)) {
                foreach($rows as $row) {
                    $name = isset($row['Name']) ? trim((string)$row['Name']) : '';
                    if($name !== '') $out[] = $name;
                }
            }
        }
    }
} catch (Throwable $e) { $out = array(); }

while(ob_get_level() > 0) { @ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_INVALID_UTF8_SUBSTITUTE);