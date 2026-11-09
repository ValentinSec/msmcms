<?php
/**
 * Cache Manager - Redesigned for AdminCP
 * Optimized for Cron Job compatibility & Visual Consistency
 */

// Asegurar acceso
if(!defined('access') or !access) die();

echo '<h1 class="page-header" style="display:none;">Cache Manager</h1>'; // Oculto el header default para usar el custom

// --- ESTILOS CUSTOM (Dark Theme) ---
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    .cache-container { font-family: 'Poppins', sans-serif; color: #fff; font-size: 13px; }
    
    /* Paneles */
    .settings-section { 
        background-color: #1e1e2d; 
        border-radius: 8px; 
        padding: 20px; 
        border: 1px solid #2b2b40; 
        box-shadow: 0 0 20px rgba(0,0,0,0.2); 
        margin-bottom: 20px; 
    }
    .settings-title { 
        font-size: 16px; 
        font-weight: 600; 
        margin-top: 0; 
        margin-bottom: 20px; 
        padding-bottom: 15px; 
        border-bottom: 1px solid #2b2b40; 
        color: #fff; 
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* Tablas */
    .custom-table { width: 100%; border-collapse: collapse; }
    .custom-table th { 
        text-align: left; padding: 12px; color: #6c7293; font-size: 11px; 
        text-transform: uppercase; border-bottom: 1px solid #2b2b40; font-weight: 700;
    }
    .custom-table td { padding: 12px; border-bottom: 1px dashed #2b2b40; color: #b5b5c3; vertical-align: middle; }
    .custom-table tr:last-child td { border-bottom: none; }
    .custom-table tr:hover td { background-color: rgba(255,255,255,0.02); color: #fff; }

    /* Etiquetas */
    .badge-soft-success { background-color: rgba(27, 197, 189, 0.2); color: #1bc5bd; padding: 5px 10px; border-radius: 4px; font-weight: 500; font-size: 11px; }
    .badge-soft-danger { background-color: rgba(246, 78, 96, 0.2); color: #f64e60; padding: 5px 10px; border-radius: 4px; font-weight: 500; font-size: 11px; }
    .file-name { font-weight: 600; color: #fff; font-family: monospace; font-size: 12px; }
    
    /* Botones */
    .btn-clear { 
        background: transparent; border: 1px solid #f64e60; color: #f64e60; 
        padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 600; 
        text-transform: uppercase; text-decoration: none; transition: 0.2s; 
    }
    .btn-clear:hover { background: #f64e60; color: #fff; text-decoration: none; }
    
    .btn-purge-block {
        display: block; width: 100%; text-align: center;
        background: rgba(246, 78, 96, 0.1); border: 1px dashed #f64e60; color: #f64e60;
        padding: 10px; border-radius: 6px; font-weight: 600; transition: 0.2s; margin-top: 15px;
    }
    .btn-purge-block:hover { background: #f64e60; color: #fff; text-decoration: none; }

    /* Info Stats */
    .cache-stat-row { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #2b2b40; padding-bottom: 5px; }
    .stat-label { color: #6c7293; }
    .stat-value { color: #fff; font-weight: bold; }
</style>

<div class="cache-container">

<?php
try {
	
	// Instancia del Manager Original
	$cacheManager = new CacheManager();
	$cacheFileList = $cacheManager->getCacheFileListAndData();
	
	if(!is_array($cacheFileList)) throw new Exception('No cache files found inside /includes/cache/');
	
	// --- LÓGICA DE ACCIONES (Mantenemos la original pero protegida) ---
	if(isset($_GET['action'])) {
		try {
			switch($_GET['action']) {
				case 'clear':
					if(!isset($_GET['file'])) throw new Exception("Archivo no especificado.");
					$cacheManager->setFile($_GET['file']);
					$cacheManager->clearCacheData();
					redirect(3, admincp_base('cachemanager'));
					break;
				case 'deleteguildcache':
					$cacheManager->deleteGuildCache();
					redirect(3, admincp_base('cachemanager'));
					break;
				case 'deleteplayercache':
					$cacheManager->deletePlayerCache();
					redirect(3, admincp_base('cachemanager'));
					break;
				default:
					throw new Exception('Acción no válida.');
			}
		} catch(Exception $ex) {
			echo '<div class="alert alert-danger" style="background:#f1416c; color:#fff; border:none;">'.$ex->getMessage().'</div>';
		}
	}
	
	// --- SECCIÓN 1: ARCHIVOS PRINCIPALES (SISTEMA & CRON) ---
	echo '<div class="settings-section">';
		echo '<div class="settings-title">';
			echo '<span><i class="fa fa-server" style="color:#3699ff; margin-right:10px;"></i> System Cache Files</span>';
			echo '<small style="font-size:11px; color:#6c7293; font-weight:400;">Estos archivos son generados por los Crons</small>';
		echo '</div>';
		
		echo '<div class="table-responsive">';
		echo '<table class="custom-table">';
			echo '<thead>';
				echo '<tr>';
					echo '<th>Archivo</th>';
					echo '<th>Tamaño</th>';
					echo '<th>Última Actualización</th>';
					echo '<th>Estado (Permisos)</th>';
					echo '<th class="text-right">Acciones</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			
			foreach($cacheFileList as $row) {
				// Formato visual de fecha
				$lastMod = ($row['edit'] == 'Unknown') ? '<span style="color:#666;">Nunca</span>' : date("d/m/Y H:i:s", $row['edit']);
				
				// Estado de escritura (Crucial para Crons)
				$statusBadge = ($row['write'] == 1) 
					? '<span class="badge-soft-success"><i class="fa fa-check" style="font-size:10px;"></i> Writable</span>' 
					: '<span class="badge-soft-danger"><i class="fa fa-times" style="font-size:10px;"></i> Locked</span>';
				
				echo '<tr>';
					echo '<td><span class="file-name"><i class="fa fa-file-code-o" style="color:#7e8299; margin-right:5px;"></i> '.$row['file'].'</span></td>';
					echo '<td>'.readableFileSize($row['size'], 2).'</td>';
					echo '<td>'.$lastMod.'</td>';
					echo '<td>'.$statusBadge.'</td>';
					echo '<td style="text-align:right;">';
						echo '<a href="'.admincp_base('cachemanager&action=clear&file=' . urlencode($row['file'])).'" class="btn-clear">';
						echo '<i class="fa fa-trash-o"></i> Limpiar';
						echo '</a>';
					echo '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
		echo '</table>';
		echo '</div>'; // responsive
	echo '</div>';
	
	// --- SECCIÓN 2: CACHÉ DE PERFILES (SPLIT VIEW) ---
	echo '<div class="row">';
		
		// GUILD PROFILES
		echo '<div class="col-md-6">';
			$guildProfilesCache = $cacheManager->getCacheFileListAndData('guild');
			$guildCacheTotalSize = 0;
			$guildProfilesCacheCount = 0;
			
			if(is_array($guildProfilesCache)) {
				$guildProfilesCacheCount = count($guildProfilesCache);
				foreach($guildProfilesCache as $guildCache) {
					$guildCacheTotalSize += $guildCache['size'];
				}
			}
			
			echo '<div class="settings-section">';
				echo '<div class="settings-title"><i class="fa fa-users" style="color:#f39c12; margin-right:10px;"></i> Guild Profiles Cache</div>';
				
				echo '<div class="cache-stat-row">';
					echo '<span class="stat-label">Archivos Generados:</span>';
					echo '<span class="stat-value">'.number_format($guildProfilesCacheCount).'</span>';
				echo '</div>';
				
				echo '<div class="cache-stat-row" style="border:none;">';
					echo '<span class="stat-label">Espacio en Disco:</span>';
					echo '<span class="stat-value">'.readableFileSize($guildCacheTotalSize, 2).'</span>';
				echo '</div>';
				
				if($guildProfilesCacheCount > 0) {
					echo '<a href="'.admincp_base('cachemanager&action=deleteguildcache').'" class="btn-purge-block">';
					echo '<i class="fa fa-eraser"></i> PURGAR TODO EL CACHÉ DE GUILDS';
					echo '</a>';
				} else {
					echo '<div style="text-align:center; padding:15px; color:#6c7293; font-style:italic;">No hay caché generado.</div>';
				}
			echo '</div>';
		echo '</div>';
		
		// PLAYER PROFILES
		echo '<div class="col-md-6">';
			$playerProfilesCache = $cacheManager->getCacheFileListAndData('player');
			$playerCacheTotalSize = 0;
			$playerProfilesCacheCount = 0;
			
			if(is_array($playerProfilesCache)) {
				$playerProfilesCacheCount = count($playerProfilesCache);
				foreach($playerProfilesCache as $playerCache) {
					$playerCacheTotalSize += $playerCache['size'];
				}
			}
			
			echo '<div class="settings-section">';
				echo '<div class="settings-title"><i class="fa fa-user" style="color:#8950fc; margin-right:10px;"></i> Player Profiles Cache</div>';
				
				echo '<div class="cache-stat-row">';
					echo '<span class="stat-label">Archivos Generados:</span>';
					echo '<span class="stat-value">'.number_format($playerProfilesCacheCount).'</span>';
				echo '</div>';
				
				echo '<div class="cache-stat-row" style="border:none;">';
					echo '<span class="stat-label">Espacio en Disco:</span>';
					echo '<span class="stat-value">'.readableFileSize($playerCacheTotalSize, 2).'</span>';
				echo '</div>';
				
				if($playerProfilesCacheCount > 0) {
					echo '<a href="'.admincp_base('cachemanager&action=deleteplayercache').'" class="btn-purge-block">';
					echo '<i class="fa fa-eraser"></i> PURGAR TODO EL CACHÉ DE PJ';
					echo '</a>';
				} else {
					echo '<div style="text-align:center; padding:15px; color:#6c7293; font-style:italic;">No hay caché generado.</div>';
				}
			echo '</div>';
		echo '</div>';
		
	echo '</div>'; // End Row
	
} catch(Exception $ex) {
	message('error', $ex->getMessage());
}
?>
</div>