<?php
/**
 * ARCHIVO: admincp/modules/tools/tools_character.php
 * PREPARED FOR OBFUSCATION (Pure PHP Version)
 */

// Lógica para procesar el envío del formulario
if(isset($_POST['search_character']) && !empty($_POST['character_name'])) {
    $charName = htmlspecialchars($_POST['character_name']);
    // Redirigir al módulo editchar
    echo '<script>window.location="index.php?module=editchar&name=' . $charName . '";</script>';
}

// --- RENDERIZADO HTML (PHP PURO) ---

echo '<style>
/* --- DISEÑO FINAL DARK --- */
.search-tool-container {
    background-color: #1a1a1a !important;
    padding: 60px 20px;
    border-radius: 8px;
    border: 1px solid #333;
    margin-top: 20px;
    font-family: "Segoe UI", sans-serif;
    color: #fff;
    text-align: center;
}

.st-title {
    font-size: 24px;
    font-weight: 300;
    margin-bottom: 30px;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.st-input-wrapper {
    position: relative;
    max-width: 500px;
    margin: 0 auto;
}

.st-input {
    width: 100%;
    padding: 15px 20px;
    font-size: 18px;
    background-color: #000 !important;
    border: 2px solid #00b894 !important; /* El verde que buscabas */
    color: #fff !important;
    border-radius: 5px;
    outline: none;
}

/* LISTA DE RESULTADOS (GOOGLE SUGGEST STYLE) */
.st-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: #1a1a1a;
    border: 1px solid #333;
    z-index: 99999;
    max-height: 250px;
    overflow-y: auto;
    display: none;
    text-align: left;
    border-radius: 0 0 5px 5px;
}

.st-item {
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 1px solid #252525;
    color: #ccc;
}

.st-item:hover {
    background-color: #00b894;
    color: #fff;
    padding-left: 25px;
}

.st-btn {
    margin-top: 25px;
    padding: 12px 40px;
    background-color: #00b894;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: bold;
    text-transform: uppercase;
}
</style>';

echo '<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="search-tool-container">
                <h2 class="st-title">Buscador de Personajes</h2>
                <form action="" method="post" autocomplete="off">
                    <div class="st-input-wrapper">
                        <input type="text" id="LiveSearchInput" name="character_name" class="st-input" placeholder="Escriba el nombre del personaje..." autocomplete="off">
                        <div id="LiveSearchResults" class="st-results"></div>
                    </div>
                    <button type="submit" name="search_character" class="st-btn">
                        Editar Personaje
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>';

echo '<script>
$(document).ready(function() {
    var inputField = $("#LiveSearchInput");
    var resultsDiv = $("#LiveSearchResults");

    inputField.on("keyup", function() {
        var query = $(this).val();
        if (query.length >= 1) {
            $.ajax({
                url: "ajax_search_character.php",
                method: "POST",
                data: { term: query },
                dataType: "json",
                success: function(data) {
                    resultsDiv.empty();
                    if (data && data.length > 0) {
                        $.each(data, function(index, name) {
                            resultsDiv.append("<div class=\'st-item\'>" + name + "</div>");
                        });
                        resultsDiv.show();
                    } else {
                        resultsDiv.hide();
                    }
                }
            });
        } else {
            resultsDiv.hide();
        }
    });

    $(document).on("click", ".st-item", function() {
        var selectedName = $(this).text();
        inputField.val(selectedName);
        resultsDiv.hide();
        window.location.href = "index.php?module=editchar&name=" + selectedName;
    });

    $(document).click(function(e) {
        if (!$(e.target).closest(".st-input-wrapper").length) {
            resultsDiv.hide();
        }
    });
});
</script>';
?>