<?php
/**
 * API: Character Unstuck
 * Ubicación: api/character_unstuck.php
 */

define('access', 'api');
require_once(__DIR__ . '/../includes/muservermaker.php');

if (!isLoggedIn()) die('Error: Debes iniciar sesión.');

$configFile = __PATH_INCLUDES__ . 'config/mychar_config.json';
$myCharConfig = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

if (!isset($myCharConfig['enable_unstuck']) || $myCharConfig['enable_unstuck'] != 1) {
    die('Error: Módulo desactivado.');
}

if (!isset($_POST['char_name'])) die('Error: Falta nombre.');

$charName = $_POST['char_name'];
$username = $_SESSION['username'];

try {
    $db = Connection::Database('Me_MuOnline');

    // Verificar Propiedad
    $checkOwner = $db->query_fetch_single("SELECT Name FROM Character WHERE Name = ? AND AccountID = ?", [$charName, $username]);

    if (!$checkOwner) die('Error: Personaje inválido.');

    // Mover a Lorencia (0, 125, 125)
    $db->query("UPDATE Character SET MapNumber = 0, MapPosX = 125, MapPosY = 125 WHERE Name = ?", [$charName]);

    echo "ok";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>