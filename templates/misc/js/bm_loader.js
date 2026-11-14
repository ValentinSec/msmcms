/* MuServerMaker: ventana "Procesando compra" compartida por los templates */
function bm_buy_loader(show) {
    var el = document.getElementById('bmBuyLoader');
    if (!el) {
        var color = window.BM_LOADER_COLOR || '#ffffff';
        el = document.createElement('div');
        el.id = 'bmBuyLoader';
        el.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:2147483647;align-items:center;justify-content:center;flex-direction:column;';
        el.innerHTML = '<i class="fa fa-spinner fa-spin fa-3x" style="color:' + color + ';margin-bottom:12px;"></i><span style="color:#fff;font-size:13px;font-weight:bold;letter-spacing:1px;">PROCESANDO COMPRA...</span>';
        document.body.appendChild(el);
    }
    el.style.display = show ? 'flex' : 'none';
}
window.showBuyLoader = bm_buy_loader;