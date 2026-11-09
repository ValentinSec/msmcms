<?php
/**
 * Módulo: SISTEMA DE VOTOS
 * Ubicación: modules/usercp/vote.php
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

if (!defined("access") or !access) die();

// --- 1. LÓGICA DE CARGA DE CONFIGURACIÓN ---
$votesFileUser = 'includes/config/votes.json';
$realPath = file_exists($votesFileUser) ? $votesFileUser : __PATH_INCLUDES__ . 'config/votes.json';

$vConfig = file_exists($realPath) ? json_decode(file_get_contents($realPath), true) : array();
$vReward = isset($vConfig['vote_reward']) ? $vConfig['vote_reward'] : 0;
$vCurrency = isset($vConfig['vote_currency']) ? $vConfig['vote_currency'] : 'Coins';
$vLinks = isset($vConfig['links']) ? $vConfig['links'] : array();
$timeWait = isset($vConfig['vote_interval']) ? $vConfig['vote_interval'] : 12;

// --- 2. RENDERIZADO (ESTÉTICA ORIGINAL PRESERVADA) ---

echo '<div class="module-title mb-4">';
    echo '<h2 class="text-color text-uppercase fw-bold">SISTEMA DE VOTOS</h2>';
    echo '<p class="text-color small">Apoya al servidor y obtén recompensas</p>';
    echo '<div class="title-underline"></div>';
echo '</div>';

echo '<div class="donation-main-container p-4 d-flex flex-column gap-4">';
    
    // Cuadro de Recompensa
    echo '<div class="settings-form-box border-gold">';
        echo '<div class="settings-header">';
            echo '<div class="icon-square bg-gold-soft"><i class="fa fa-gift"></i></div>';
            echo '<h5 class="text-white text-uppercase m-0">Tu Recompensa</h5>';
        echo '</div>';
        echo '<div class="alert-settings-yellow my-3">';
            echo '<i class="fa fa-info-circle me-2"></i> Recibirás <strong>' . $vReward . ' ' . $vCurrency . '</strong> por cada voto validado.';
        echo '</div>';
    echo '</div>';

    if(!empty($vLinks)) {
        echo '<div class="row g-3">';
        foreach($vLinks as $domainKey => $link) {
            if(isset($link['active']) && $link['active'] == 1) {
                
                $logoUrl = "https://www.google.com/s2/favicons?domain=" . $domainKey . "&sz=64";

                echo '<div class="col-md-6 col-lg-4">';
                    echo '<div class="settings-form-box border-blue h-100 d-flex flex-column" style="position: relative; overflow: hidden;">';
                        
                        // Badge de Tiempo
                        echo '<div style="position: absolute; top: 0; right: 0; background: rgba(59, 130, 246, 0.2); color: #3b82f6; font-size: 10px; font-weight: bold; padding: 4px 8px; border-bottom-left-radius: 6px; border-left: 1px solid rgba(59, 130, 246, 0.3); border-bottom: 1px solid rgba(59, 130, 246, 0.3);">';
                            echo '<i class="fa fa-clock me-1"></i> ' . $timeWait . 'H';
                        echo '</div>';
                        
                        // Cuerpo del Link
                        echo '<div class="d-flex align-items-center mb-3 mt-2">';
                            echo '<div class="icon-square me-3" style="background: #fff; padding: 5px; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">';
                                echo '<img src="' . $logoUrl . '" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">';
                            echo '</div>';
                            echo '<div style="overflow: hidden;">';
                                echo '<h5 class="text-white text-uppercase m-0 fw-bold" style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">';
                                    echo htmlspecialchars($link['name']);
                                echo '</h5>';
                                echo '<p class="small text-white m-0" style="opacity: 0.5; font-size: 0.75rem;">Ranking Site</p>';
                            echo '</div>';
                        echo '</div>';
                        
                        // Botón
                        echo '<a href="' . $link['url'] . '" target="_blank" class="btn btn-blue-action w-100 py-2 mt-auto">';
                            echo '<i class="fa fa-external-link-alt me-2"></i> Votar';
                        echo '</a>';

                    echo '</div>';
                echo '</div>';
            }
        }
        echo '</div>';
    } else {
        // Estado Vacío
        echo '<div class="settings-form-box border-red">';
            echo '<div class="text-center p-4 text-white">';
                echo '<i class="fa fa-ghost fa-2x mb-3 text-danger" style="opacity: 0.5;"></i>';
                echo '<p class="m-0">No hay sitios de votación configurados en este momento.</p>';
            echo '</div>';
        echo '</div>';
    }

echo '</div>';