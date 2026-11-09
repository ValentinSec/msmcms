<?php
/**
 * Diagnóstico de Items y Joyas
 * Ubicación: api/debug_jewels.php
 */

define('access', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Economía</h1>";

// 1. CARGA DEL NÚCLEO
$root_path = dirname(__DIR__) . '/';
require_once($root_path . 'includes/webengine.php');

if (!isLoggedIn()) die("<h3>Por favor, logueate en la web primero.</h3>");

$user = $_SESSION['username'];
echo "Usuario analizado: <b>$user</b><br><hr>";

try {
    $db = Connection::Database('MuOnline');

    // 2. BUSCAR EN INVENTARIO
    // NOTA: Inventory suele tener 12 bytes por item en versiones muy viejas, 16 en viejas, 32 en nuevas.
    $invData = $db->query_fetch_single("SELECT Inventory FROM Character WHERE AccountID = ? OR Name = ?", [$user, $user]);
    
    if (!$invData || empty($invData['Inventory'])) {
        die("No se encontró inventario para este usuario o está vacío.");
    }

    $binData = $invData['Inventory'];
    $hex = bin2hex($binData);
    $totalLen = strlen($hex); // Longitud en caracteres Hex
    $totalBytes = $totalLen / 2;

    echo "Longitud de datos Hex: $totalLen caracteres ($totalBytes bytes)<br>";
    
    // 3. PRUEBA DE TAMAÑOS
    echo "<h3>Análisis de Patrones:</h3>";
    
    // IDs de Joyas para buscar (Season 6 Standard: Group 14/12)
    // Chaos: ID 15 (0F), Group 12 (C) -> Hex empieza con 0FC...
    // Bless: ID 13 (0D), Group 14 (E) -> Hex empieza con 0DE...
    // Soul:  ID 14 (0E), Group 14 (E) -> Hex empieza con 0EE...
    
    $found32 = 0;
    $found16 = 0;
    $foundChaos = false;

    // PRUEBA 32 BYTES (Season 6+)
    $items32 = str_split($hex, 64); // 32 bytes * 2 chars
    echo "<strong>Probando estructura de 32 Bytes (Season 6+):</strong><br>";
    foreach ($items32 as $i => $item) {
        if (strtoupper(substr($item, 0, 4)) == 'FFFF') continue; // Item vacío
        $found32++;
        
        $byte0 = substr($item, 0, 2); // ID
        $byte1 = substr($item, 2, 2); // Group info
        
        // Check visual rápido
        if ($found32 <= 5) {
            echo "Item $i: [ID: $byte0] [Grup: $byte1] ... <br>";
        }
        
        // Detectar Chaos (0F C0 aprox)
        if (strtoupper($byte0) == '0F') {
            $grp = (hexdec($byte1) & 240) >> 4;
            if ($grp == 12) {
                echo "<span style='color:green'>¡CHAOS ENCONTRADO en modo 32 bytes!</span><br>";
                $foundChaos = true;
            }
        }
    }
    echo "Items detectados (no vacíos): $found32 <br><br>";

    // PRUEBA 16 BYTES (99b / Season 2)
    if (!$foundChaos) {
        echo "<strong>Probando estructura de 16 Bytes (99b/S2):</strong><br>";
        $items16 = str_split($hex, 32); // 16 bytes * 2 chars
        foreach ($items16 as $i => $item) {
            if (strtoupper(substr($item, 0, 4)) == 'FFFF') continue;
            $found16++;
            
            $byte0 = substr($item, 0, 2);
            $byte1 = substr($item, 2, 2);

            if ($found16 <= 5) {
                echo "Item $i: [ID: $byte0] [Grup: $byte1] ... <br>";
            }

            if (strtoupper($byte0) == '0F') {
                $grp = (hexdec($byte1) & 240) >> 4;
                if ($grp == 12) {
                    echo "<span style='color:green'>¡CHAOS ENCONTRADO en modo 16 bytes!</span><br>";
                }
            }
        }
        echo "Items detectados (no vacíos): $found16 <br>";
    }

} catch (Exception $e) {
    echo "Error SQL: " . $e->getMessage();
}
?>