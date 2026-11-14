<?php
/**
 * Sidebar Modules List
 */

$admincpSidebar = array(
	array("Dashboard", "home", "fa-home"),
	
	array("Templates", array(
		"templates" => "Templates",
		"edit_templates" => "Editar templates",
		"popup" => "Ventanas Emergentes",
		
	), "fa-columns"),
	
	array("Configuración", array(
		"settings" => "Configuración General",
		"settings_connection" => "Configuración de Conexión",
		"settings_download" => "Configurar Descargas",
		"settings_register" => "Configurar Registro",
		"settings_tables" => "Configurar Tablas",
		"settings_smtp" => "Configurar SMTP",
		
	), "fa-cog"),
	
	array("Módulos", array(
	    "module_events" => "Módulo de Eventos",
		"module_rankings" => "Módulo de Rankings",
		"module_bosses" => "Módulo de Rankings Bosses",
		"module_info" => "Módulo de Información",
		"module_news" => "Módulo de Noticias",
		"module_guides" => "Módulo de Guías",
		"module_security" => "Módulo de Seguridad",
		"module_navbar" => "Módulo de Barra de Navegación",
		
	), "fa-cubes"),
	
	array("Plugins", array(
		"my_plugins" => "Mis Plugins",
	), "fa-plug"),
	
	array("Herramientas", array(
		"tools_account" => "Editar Cuenta",
		"tools_character" => "Editar Personaje",
		"tools_coins" => "Cargar Coins",
		"tools_ip" => "Buscar IP",
		"tools_cronjob" => "Cron Job",
		"tools_countdown" => "Cuenta regresiva",
	), "fa-wrench"),
);
?>