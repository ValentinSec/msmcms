<?php

# Define CMS access
if(!defined('access')) define('access', 'index');

try {
    # Load WebEngine
    if(!@include_once('includes/muservermaker.php')) {
        throw new Exception('Could not load MuServerMaker CMS.');
    }
} catch (Exception $ex) {
    if (ob_get_length()) ob_clean();
    $errorFile = 'includes/error.html';
    if(file_exists($errorFile)) {
        $errorPage = file_get_contents($errorFile);
        echo str_replace("{ERROR_MESSAGE}", $ex->getMessage(), $errorPage);
    } else {
        echo '<h3>CMS Error</h3>';
        echo $ex->getMessage();
    }
}