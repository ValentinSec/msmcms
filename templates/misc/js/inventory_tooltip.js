document.addEventListener('DOMContentLoaded', function() {
    const tooltip = document.createElement('div');
    tooltip.id = 'mu-tooltip';
    // Importante: Asegúrate de que en tu CSS mu-tooltip tenga position: fixed o absolute
    document.body.appendChild(tooltip);

    const slots = document.querySelectorAll('.inv-slot');
    
    slots.forEach(slot => {
        slot.addEventListener('mouseenter', function(e) {
            const rawContent = this.getAttribute('data-tip');
            if (!rawContent) return;
            
            const txt = document.createElement("textarea");
            txt.innerHTML = rawContent;
            tooltip.innerHTML = txt.value;
            
            tooltip.style.visibility = 'visible';
        });

        slot.addEventListener('mousemove', function(e) {
            let leftPos = e.clientX + 15;
            let topPos = e.clientY + 15;

            // 1. Obtener dimensiones del tooltip y de la ventana
            const tooltipRect = tooltip.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;

            // 2. Detección de borde inferior: 
            // Si la posición actual + la altura del tooltip superan el alto de la ventana
            if (e.clientY + tooltipRect.height + 20 > viewportHeight) {
                // Invertimos la posición para que aparezca arriba del cursor
                topPos = e.clientY - tooltipRect.height - 15;
            }

            // 3. Detección de borde derecho (por si el tooltip es muy ancho)
            if (e.clientX + tooltipRect.width + 20 > viewportWidth) {
                leftPos = e.clientX - tooltipRect.width - 15;
            }

            // 4. Aplicar posiciones
            tooltip.style.left = leftPos + 'px';
            tooltip.style.top = topPos + 'px';
        });

        slot.addEventListener('mouseleave', function() {
            tooltip.style.visibility = 'hidden';
            tooltip.style.left = '-9999px';
            tooltip.style.top = '-9999px';
        });
    });
});