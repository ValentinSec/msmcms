<?php
/**
 * API: Character Reset
 * Ubicación: api/character_reset.php
 */

define('access', 'api');

// CAMBIO: Usamos muservermaker.php
require_once(__DIR__ . '/../includes/muservermaker.php');

if (!isLoggedIn()) die('Error: Debes iniciar sesión.');

// 1. Cargar Configs
$myCharConfig = json_decode(file_get_contents(__PATH_INCLUDES__ . 'config/mychar_config.json'), true);
$webSettings  = json_decode(file_get_contents(__PATH_INCLUDES__ . 'config/custom_settings.json'), true); 

// 2. Validaciones
if (!isset($myCharConfig['enable_reset']) || $myCharConfig['enable_reset'] != 1) {
    die('Error: El sistema de resets está desactivado.');
}
if (!isset($_POST['char_name'])) die('Error: Falta el nombre del personaje.');

$charName = $_POST['char_name'];
$username = $_SESSION['username'];

// Requisitos
$reqLevel  = $webSettings['reset_level']  ?? 400;
$reqZen    = $webSettings['reset_money']  ?? 10000000;
$pntsReset = $webSettings['reset_points'] ?? 500; 
$resetType = $webSettings['reset_type']   ?? 'keep'; 
$limitReset= $webSettings['reset_limit']  ?? 999;

try {
    $db = Connection::Database('Me_MuOnline');

    // 3. Verificar Propiedad (Anti-Hack) y Datos
    // IMPORTANTE: Usamos parametros ? para evitar SQL Injection
    $charData = $db->query_fetch_single(
        "SELECT Name, cLevel, ResetCount, Money, LevelUpPoint, Class FROM Character WHERE Name = ? AND AccountID = ?", 
        [$charName, $username]
    );

    if (!$charData) die('Error: Personaje no encontrado o no te pertenece.');

    // 4. Chequeos de Juego
    if ($charData['cLevel'] < $reqLevel) die("Error: Necesitas nivel $reqLevel.");
    if ($charData['Money'] < $reqZen)    die("Error: Necesitas " . number_format($reqZen) . " Zen.");
    if ($charData['ResetCount'] >= $limitReset) die("Error: Límite de resets alcanzado.");

    // 5. Preparar Lógica
    $newResetCount = $charData['ResetCount'] + 1;
    $newMoney = $charData['Money'] - $reqZen;
    
    // Teletransporte Básico según clase
    $cClass = $charData['Class'];
    $targetMap = 0; $targetX = 125; $targetY = 125; // Lorencia Default

    // Lógica Reset (Keep vs Reset Stats)
    $sqlStats = "";
    $newLevelUpPoint = $charData['LevelUpPoint'];

    if ($resetType == 'reset') {
        $newLevelUpPoint = $pntsReset * $newResetCount;
        $baseStat = 25; 
        $sqlStats = ", Strength=$baseStat, Dexterity=$baseStat, Vitality=$baseStat, Energy=$baseStat";
        // Si es DL (Dark Lord), resetear comando también
        if ($cClass >= 64 && $cClass < 80) $sqlStats .= ", Leadership=$baseStat";
    }

    // 6. Ejecutar Update Seguro
    $query = "UPDATE Character SET 
              cLevel = 1, 
              Experience = 0, 
              ResetCount = ?, 
              Money = ?, 
              LevelUpPoint = ?,
              MapNumber = ?, MapPosX = ?, MapPosY = ?
              $sqlStats
              WHERE Name = ?";
    
    $db->query($query, [$newResetCount, $newMoney, $newLevelUpPoint, $targetMap, $targetX, $targetY, $charName]);

    echo "ok";

} catch (Exception $e) {
    die("Error del Sistema: " . $e->getMessage());
}
?>