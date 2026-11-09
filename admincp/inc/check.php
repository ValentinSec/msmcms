<?php
/**
 * MuServerMaker CMS
 * https://muservermaker.com/
 */

$configError = array();

// Carga la lista maestra de archivos requeridos desde el JSON
$writablePaths = loadJsonFile(MUSERVERMAKER_WRITABLE_PATHS);
if(!is_array($writablePaths)) throw new Exception('Could not load CMS writable paths list.');

// --- LISTA DE EXCEPCIONES (Archivos borrados intencionalmente) ---
$ignored_paths = [
    // Archivos de Cach¨¦ (Rankings y otros)
    'cache/news/',
    'cache/news/translations/',
    'cache/profiles/guilds/',
    'cache/profiles/players/',
    'cache/blocked_ip.cache',
    'cache/character_country.cache',
    'cache/news.cache',
    'cache/plugins.cache',
    'cache/rankings_gens.cache',
    'cache/rankings_gr.cache',
    'cache/rankings_guilds.cache',
    'cache/rankings_level.cache',
    'cache/rankings_master.cache',
    'cache/rankings_online.cache',
    'cache/rankings_pk.cache',
    'cache/rankings_resets.cache',
    'cache/rankings_votes.cache'
];

// File permission check
foreach($writablePaths as $thisPath) {
    
    // Si el archivo actual est¨¢ en la lista de ignorados, saltamos la validaci¨®n
    if(in_array($thisPath, $ignored_paths)) continue;

    if(file_exists(__PATH_INCLUDES__ . $thisPath)) {
        if(!is_writable(__PATH_INCLUDES__ . $thisPath)) {
            $configError[] = "<span style=\"color:#aaaaaa;\">[Permission Error]</span> " . $thisPath . " <span style=\"color:red;\">(file must be writable)</span>";
        }
    } else {
        $configError[] = "<span style=\"color:#aaaaaa;\">[Not Found]</span> " . $thisPath. " <span style=\"color:orange;\">(re-upload file)</span>";
    }
}

// Check cURL
if(!function_exists('curl_version')) $configError[] = "<span style=\"color:#aaaaaa;\">[PHP]</span> <span style=\"color:green;\">curl not loaded (CMS requires cURL)</span>";

if(count($configError) >= 1) {
    throw new Exception("<strong>The following errors ocurred:</strong><br /><br />" . implode("<br />", $configError));
}
?>