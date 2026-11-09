<?php
/**
 * News List Module - MuServerMaker CMS
 * Logic Only - Styles are in templates/classic/css/news.css
 */
if(!defined('access')) die();

// --- 1. CARGA DE DATOS ---
$settingsFile = __PATH_INCLUDES__ . 'config/custom_settings.json';
$newsFile     = __PATH_INCLUDES__ . 'config/server_news.json';

$config = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$newsData = file_exists($newsFile) ? json_decode(file_get_contents($newsFile), true) : [];

// Layout dinámico desde AdminCP
$layoutMode = $config['news_layout_type'] ?? 'cascada'; 

// Helper para limpiar el texto de la preview
function cleanNewsPreview($html, $limit = 100) {
    $text = strip_tags($html);
    if(mb_strlen($text) > $limit) {
        $text = mb_substr($text, 0, $limit) . '...';
    }
    return $text;
}
?>

<div class="news-container">
    
    <div class="news-header">
        <h1 class="news-h1"><i class="fa fa-newspaper"></i> Noticias Recientes</h1>
        <div class="news-search-box">
            <i class="fa fa-search news-search-icon"></i>
            <input type="text" id="newsSearch" class="news-search-input" placeholder="Buscar noticia..." onkeyup="filterNews()">
        </div>
    </div>

    <div id="newsGrid" class="layout-<?php echo $layoutMode; ?>">
        <?php if(empty($newsData)): ?>
            <div style="text-align: center; width: 100%; padding: 50px; color: #666; grid-column: 1/-1;">
                <i class="fa fa-bullhorn" style="font-size: 50px; margin-bottom: 15px; opacity: 0.3;"></i>
                <p>No hay noticias publicadas.</p>
            </div>
        <?php else: ?>
            <?php 
            // Ordenar por fecha desc
            usort($newsData, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

            foreach($newsData as $news): 
                $img = !empty($news['image']) ? __BASE_URL__ . $news['image'] : __BASE_URL__ . 'img/news/no_image.gif';
            ?>
                <div class="news-card" onclick='openNewsReader(<?php echo json_encode($news); ?>)'>
                    <div class="news-img-box">
                        <img src="<?php echo $img; ?>" class="news-img" onerror="this.src='https://placehold.co/600x400/111/444?text=Noticia'">
                    </div>
                    <div class="news-body">
                        <div class="news-date"><i class="fa fa-calendar-day"></i> <?php echo $news['date']; ?></div>
                        <div class="news-item-title"><?php echo htmlspecialchars($news['title']); ?></div>
                        <?php if($layoutMode != 'expandida'): ?>
                            <div class="news-desc">
                                <?php echo cleanNewsPreview($news['content'], $layoutMode == 'full' ? 300 : 120); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<div id="newsReader" class="news-modal-overlay">
    <div class="news-modal">
        <div class="nm-header">
            <div class="nm-title" id="nmTitle">Título de la Noticia</div>
            <div class="nm-close" onclick="closeNewsReader()"><i class="fa fa-times"></i></div>
        </div>
        <div class="nm-content-scroll">
            <img src="" id="nmBanner" class="nm-banner">
            <div id="nmDate" style="color: var(--news-accent); font-weight:bold; font-size: 13px; margin-bottom: 15px;"></div>
            <div id="nmBody"></div>
        </div>
    </div>
</div>

<script>
    function filterNews() {
        let input = document.getElementById('newsSearch').value.toLowerCase();
        let cards = document.querySelectorAll('.news-card');
        
        cards.forEach(card => {
            let title = card.querySelector('.news-item-title').innerText.toLowerCase();
            if (title.includes(input)) {
                card.style.display = (document.getElementById('newsGrid').className === 'layout-cascada') ? "block" : "flex";
            } else {
                card.style.display = "none";
            }
        });
    }

    function openNewsReader(news) {
        document.getElementById('nmTitle').innerText = news.title;
        document.getElementById('nmDate').innerHTML = "<i class='fa fa-calendar-alt'></i> Publicado el: " + news.date;
        document.getElementById('nmBody').innerHTML = news.content;
        
        const banner = document.getElementById('nmBanner');
        if(news.image && news.image !== "") {
            banner.src = "<?php echo __BASE_URL__; ?>" + news.image;
            banner.style.display = "block";
        } else {
            banner.style.display = "none";
        }

        document.getElementById('newsReader').style.display = 'flex';
        document.body.style.overflow = 'hidden'; 
    }

    function closeNewsReader() {
        document.getElementById('newsReader').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    window.onclick = function(event) {
        let modal = document.getElementById('newsReader');
        if (event.target == modal) {
            closeNewsReader();
        }
    }
    
    document.addEventListener('keydown', function(e) {
        if(e.key === "Escape") closeNewsReader();
    });
</script>