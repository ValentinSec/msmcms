<?php
/**
 * UserCP Guides Module
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */
if(!defined('access')) die();

// --- 1. CARGA DE DATOS ---
$settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$guidesFile   = __PATH_INCLUDES__ . 'config/server_guides.json';

$config = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$guides = file_exists($guidesFile) ? json_decode(file_get_contents($guidesFile), true) : [];

// Layout por defecto
$layoutMode = isset($config['guides_layout_type']) ? $config['guides_layout_type'] : 'cascada'; 

// Helper para limpiar texto
function cleanPreview($html, $limit = 100) {
    $text = strip_tags($html);
    if(strlen($text) > $limit) {
        $text = substr($text, 0, $limit) . '...';
    }
    return $text;
}

// Variables de entorno para HTML
$baseUrl = __BASE_URL__;

// --- 2. RENDERIZADO (CONSTRUCCIÓN CON PHP PURO) ---

// A. ESTILOS CSS
echo '<style>
    :root {
        --guide-card-bg: #131519;
        --guide-border: #2a2e35;
        --guide-accent: #f39c12;
        --guide-text: #a0a0a0;
        --guide-title: #ffffff;
    }
    .guides-container { padding: 0; min-height: 500px; position: relative; }
    .guides-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--guide-border); }
    .guides-title { font-size: 24px; font-weight: 700; color: var(--guide-title); text-transform: uppercase; letter-spacing: 1px; }
    .guides-title i { color: var(--guide-accent); margin-right: 10px; }
    .guides-search-box { position: relative; width: 300px; }
    .guides-search-input { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--guide-border); color: #fff; padding: 10px 15px 10px 40px; border-radius: 50px; outline: none; transition: 0.3s; }
    .guides-search-input:focus { border-color: var(--guide-accent); box-shadow: 0 0 10px rgba(243, 156, 18, 0.2); }
    .guides-search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #666; }
    
    /* LAYOUT CASCADA */
    .layout-cascada { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .layout-cascada .guide-card { background: var(--guide-card-bg); border: 1px solid var(--guide-border); border-radius: 8px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; display: flex; flex-direction: column; }
    .layout-cascada .guide-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.4); border-color: var(--guide-accent); }
    .layout-cascada .guide-img-box { height: 160px; overflow: hidden; position: relative; }
    .layout-cascada .guide-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
    .layout-cascada .guide-card:hover .guide-img { transform: scale(1.1); }
    .layout-cascada .guide-body { padding: 15px; flex: 1; display: flex; flex-direction: column; }
    .layout-cascada .guide-date { font-size: 11px; color: var(--guide-accent); margin-bottom: 5px; font-weight: 600; }
    .layout-cascada .guide-h { font-size: 16px; color: #fff; margin-bottom: 8px; font-weight: 700; line-height: 1.3; }
    .layout-cascada .guide-desc { font-size: 13px; color: var(--guide-text); line-height: 1.5; flex: 1; }

    /* LAYOUT FULL */
    .layout-full { display: flex; flex-direction: column; gap: 30px; }
    .layout-full .guide-card { background: var(--guide-card-bg); border: 1px solid var(--guide-border); border-radius: 12px; overflow: hidden; display: flex; flex-direction: row; height: 250px; cursor: pointer; transition: 0.3s; }
    .layout-full .guide-card:hover { border-color: var(--guide-accent); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    .layout-full .guide-img-box { width: 45%; position: relative; overflow: hidden; }
    .layout-full .guide-img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .layout-full .guide-card:hover .guide-img { transform: scale(1.05); }
    .layout-full .guide-body { width: 55%; padding: 30px; display: flex; flex-direction: column; justify-content: center; }
    .layout-full .guide-h { font-size: 22px; margin-bottom: 10px; }
    .layout-full .guide-desc { font-size: 14px; margin-bottom: 20px; }

    /* LAYOUT EXPANDIDA */
    .layout-expandida { display: flex; flex-direction: column; gap: 10px; }
    .layout-expandida .guide-card { background: #1a1d24; border: 1px solid var(--guide-border); border-radius: 6px; padding: 10px 15px; display: flex; align-items: center; cursor: pointer; transition: 0.2s; }
    .layout-expandida .guide-card:hover { background: #20242b; border-color: #444; transform: translateX(5px); }
    .layout-expandida .guide-img-box { width: 50px; height: 50px; border-radius: 4px; overflow: hidden; margin-right: 15px; flex-shrink: 0; }
    .layout-expandida .guide-img { width: 100%; height: 100%; object-fit: cover; }
    .layout-expandida .guide-body { flex: 1; }
    .layout-expandida .guide-h { font-size: 14px; margin: 0; color: #fff; font-weight: 600; }
    .layout-expandida .guide-date { font-size: 11px; color: #666; margin-top: 2px; }
    .layout-expandida .guide-icon-go { margin-left: auto; color: var(--guide-accent); opacity: 0; transition: 0.2s; }
    .layout-expandida .guide-card:hover .guide-icon-go { opacity: 1; transform: translateX(5px); }

    /* MODAL */
    .guide-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 99999; backdrop-filter: blur(5px); align-items: center; justify-content: center; }
    .guide-modal { background: #111; width: 800px; max-width: 95%; height: 85vh; border-radius: 12px; border: 1px solid #333; display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0,0,0,0.8); animation: modalUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes modalUp { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .gm-header { padding: 20px 30px; border-bottom: 1px solid #222; display: flex; justify-content: space-between; align-items: center; background: #161616; border-radius: 12px 12px 0 0; }
    .gm-title { font-size: 20px; font-weight: bold; color: #fff; font-family: "Cinzel", serif; }
    .gm-close { color: #666; cursor: pointer; font-size: 20px; transition: 0.2s; }
    .gm-close:hover { color: #fff; }
    .gm-content-scroll { padding: 30px; overflow-y: auto; color: #ccc; font-family: "Roboto", sans-serif; line-height: 1.8; font-size: 15px; }
    .gm-content-scroll img { max-width: 100%; height: auto; border-radius: 4px; margin: 10px 0; }
    .gm-content-scroll h1, .gm-content-scroll h2, .gm-content-scroll h3 { color: var(--guide-accent); margin-top: 20px; }
    .gm-banner { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 20px; border: 1px solid #333; display:none; }
    @media (max-width: 768px) {
        .layout-full .guide-card { flex-direction: column; height: auto; }
        .layout-full .guide-img-box { width: 100%; height: 180px; }
        .layout-full .guide-body { width: 100%; }
        .guides-header { flex-direction: column; gap: 15px; align-items: flex-start; }
        .guides-search-box { width: 100%; }
    }
</style>';

// B. HEADER HTML
echo '<div class="guides-container">';
echo '<div class="guides-header">';
echo '<div class="guides-title"><i class="fa fa-book-open"></i> Guías del Servidor</div>';
echo '<div class="guides-search-box">';
echo '<i class="fa fa-search guides-search-icon"></i>';
echo '<input type="text" id="guideSearch" class="guides-search-input" placeholder="Buscar guía..." onkeyup="filterGuides()">';
echo '</div></div>';

// C. GRID DE GUÍAS
echo '<div id="guidesGrid" class="layout-' . $layoutMode . '">';

if(empty($guides)) {
    echo '<div style="text-align: center; width: 100%; padding: 50px; color: #666; grid-column: 1/-1;">';
    echo '<i class="fa fa-scroll" style="font-size: 50px; margin-bottom: 15px; opacity: 0.5;"></i>';
    echo '<p>No hay guías publicadas por el momento.</p></div>';
} else {
    foreach($guides as $guide) {
        $img = !empty($guide['image']) ? $baseUrl . $guide['image'] : $baseUrl . 'img/news/no_image.gif';
        // Limpiamos la preview según el layout
        $preview = cleanPreview($guide['content'], $layoutMode == 'full' ? 250 : 100);
        
        // JSON encode seguro para evitar conflictos de comillas en el onclick
        $guideJson = json_encode($guide, JSON_HEX_APOS | JSON_HEX_QUOT);
        
        echo '<div class="guide-card" onclick=\'openGuideReader(' . $guideJson . ')\'>';
        
        echo '<div class="guide-img-box">';
        echo '<img src="' . $img . '" class="guide-img" onerror="this.src=\'https://placehold.co/600x400/111/444?text=Guia\'">';
        echo '</div>';
        
        echo '<div class="guide-body">';
        echo '<div class="guide-date"><i class="fa fa-calendar-alt"></i> ' . $guide['date'] . '</div>';
        echo '<div class="guide-h">' . $guide['title'] . '</div>';
        
        if($layoutMode != 'expandida') {
            echo '<div class="guide-desc">' . $preview . '</div>';
        }
        
        if($layoutMode == 'expandida') {
            echo '<i class="fa fa-chevron-right guide-icon-go"></i>';
        }
        
        echo '</div></div>'; // Cierre guide-body y guide-card
    }
}

echo '</div>'; // Cierre guidesGrid
echo '</div>'; // Cierre guides-container

// D. MODAL DE LECTURA
echo '<div id="guideReader" class="guide-modal-overlay">';
echo '<div class="guide-modal">';
echo '<div class="gm-header">';
echo '<div class="gm-title" id="gmTitle">Título de la Guía</div>';
echo '<div class="gm-close" onclick="closeReader()"><i class="fa fa-times"></i></div>';
echo '</div>';
echo '<div class="gm-content-scroll">';
echo '<img src="" id="gmBanner" class="gm-banner">';
echo '<div id="gmDate" style="color: #666; font-size: 13px; margin-bottom: 15px; border-bottom: 1px solid #222; padding-bottom: 10px;"></div>';
echo '<div id="gmBody"></div>';
echo '</div></div></div>';

// E. JAVASCRIPT
echo '<script>
    function filterGuides() {
        let input = document.getElementById("guideSearch").value.toLowerCase();
        let cards = document.querySelectorAll(".guide-card");
        cards.forEach(card => {
            let title = card.querySelector(".guide-h").innerText.toLowerCase();
            if (title.includes(input)) {
                card.style.display = "flex"; 
            } else {
                card.style.display = "none";
            }
        });
    }

    function openGuideReader(guide) {
        document.getElementById("gmTitle").innerText = guide.title;
        document.getElementById("gmDate").innerText = "Publicado el: " + guide.date;
        document.getElementById("gmBody").innerHTML = guide.content;
        
        const banner = document.getElementById("gmBanner");
        if(guide.image && guide.image !== "") {
            banner.src = "' . $baseUrl . '" + guide.image;
            banner.style.display = "block";
        } else {
            banner.style.display = "none";
        }

        document.getElementById("guideReader").style.display = "flex";
        document.body.style.overflow = "hidden";
    }

    function closeReader() {
        document.getElementById("guideReader").style.display = "none";
        document.body.style.overflow = "auto";
    }

    window.onclick = function(event) {
        let modal = document.getElementById("guideReader");
        if (event.target == modal) {
            closeReader();
        }
    }
    document.addEventListener("keydown", function(e) {
        if(e.key === "Escape") closeReader();
    });
</script>';
?>