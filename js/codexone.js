/**
 * scripts.js - Funcionalidades principales del catálogo
 * Contempla:
 * - Lightbox para imágenes/videos
 * - Validación de formularios
 * - Protección básica contra modificaciones
 * - Soporte para videos de YouTube
 */

document.addEventListener('DOMContentLoaded', function() {

    



    console.log("codexone fullscreen listo ✅");
    // ---- Utilidades Fullscreen ----
    function requestFS(el) {
        const fn = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
        if (fn) return fn.call(el);
        return Promise.reject(new Error("Fullscreen API no disponible"));
    }

    // ---- Click para IMG y VIDEO (evento delegado, sirve para contenido dinámico) ----
    document.addEventListener(
        "click",
        function (e) {
        const target = e.target;
        if (!target) return;

        // Si clican una IMG o un VIDEO → fullscreen al elemento
        if (target.matches("img, video")) {
            target.style.cursor = "zoom-in";
            requestFS(target).catch(() => {
            // Si algún navegador viejito falla, no hacemos nada extra aquí.
            // (Opcional: podrías abrir un "lightbox" como fallback)
            });
        }
        },
        { passive: true }
    );

    // ---- IFRAmes (YouTube/Vimeo): botón overlay ⛶ para pedir fullscreen ----
    // Por diseño del navegador, el "click" dentro del iframe NO llega a tu JS,
    // así que creamos un botón encima.
    function prepareIframes(root = document) {
        const iframes = root.querySelectorAll("iframe:not([data-fs-ready])");
        iframes.forEach((frame) => {
        frame.setAttribute("data-fs-ready", "1");

        // Permitir fullscreen en el iframe (necesario para YouTube/Vimeo)
        if (!frame.hasAttribute("allowfullscreen")) frame.setAttribute("allowfullscreen", "");
        const allow = frame.getAttribute("allow") || "";
        if (!/fullscreen/i.test(allow)) frame.setAttribute("allow", (allow + "; fullscreen").trim());

        // Envolver con un contenedor para posicionar el botón
        if (!frame.parentElement) return;
        if (frame.parentElement.classList.contains("fs-wrap")) return;

        const wrap = document.createElement("div");
        wrap.className = "fs-wrap";
        wrap.style.position = "relative";
        wrap.style.display = "inline-block";
        wrap.style.maxWidth = "100%";

        frame.parentElement.insertBefore(wrap, frame);
        wrap.appendChild(frame);

        // Botón overlay ⛶
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "fs-btn";
        btn.setAttribute("aria-label", "Pantalla completa");
        Object.assign(btn.style, {
            position: "absolute",
            right: "8px",
            top: "8px",
            zIndex: "5",
            padding: "6px 10px",
            border: "0",
            borderRadius: "12px",
            cursor: "pointer",
            boxShadow: "0 2px 8px rgba(0,0,0,.25)",
            background: "rgba(0,0,0,.65)",
            color: "#fff",
            fontSize: "16px",
            lineHeight: "1",
        });
        btn.textContent = "⛶";

        btn.addEventListener("click", function (ev) {
            ev.stopPropagation();
            // Pedimos fullscreen sobre el IFRAME
            requestFS(frame).catch((err) => {
            console.warn("No se pudo entrar a fullscreen en iframe:", err);
            });
        });

        wrap.appendChild(btn);
        });
    }

    // Preparar iframes existentes al cargar
    document.addEventListener("DOMContentLoaded", function () {
        prepareIframes(document);
    });

    // Si tu página inserta iframes después (AJAX/templating), los detectamos también
    const mo = new MutationObserver((mutations) => {
        mutations.forEach((m) => {
        m.addedNodes.forEach((node) => {
            if (!(node instanceof Element)) return;
            // Si se insertó un iframe o un contenedor con iframes dentro, prepararlos
            if (node.matches?.("iframe")) {
            prepareIframes(document);
            } else if (node.querySelector?.("iframe")) {
            prepareIframes(node);
            }
        });
        });
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
    



















    // =============================================
    // LIGHTBOX PARA IMÁGENES/VIDEOS
    // =============================================
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <span class="close-lightbox">&times;</span>
        <div class="lightbox-media-container"></div>
        <button class="lightbox-nav prev">&lt;</button>
        <button class="lightbox-nav next">&gt;</button>
    `;
    document.body.appendChild(lightbox);

    let currentMediaIndex = 0;
    let mediaItems = [];

    function openLightbox(content, index) {
        const container = lightbox.querySelector('.lightbox-media-container');
        container.innerHTML = '';
        
        // Clonamos el elemento para evitar conflictos
        const mediaClone = content.cloneNode(true);
        
        // Si es video, activamos controles y autoplay
        if (mediaClone.tagName === 'VIDEO') {
            mediaClone.controls = true;
            mediaClone.autoplay = true;
            mediaClone.className = 'lightbox-video';
        } else if (mediaClone.tagName === 'IFRAME') {
            // Para YouTube, ajustamos tamaño
            mediaClone.className = 'lightbox-video';
            mediaClone.style.width = '80%';
            mediaClone.style.height = '80%';
        } else {
            mediaClone.className = 'lightbox-content';
        }
        
        container.appendChild(mediaClone);
        lightbox.style.display = 'block';
        document.body.style.overflow = 'hidden';
        currentMediaIndex = index;
    }

    function closeLightbox() {
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Pausar videos al cerrar
        const videos = lightbox.querySelectorAll('video, iframe');
        videos.forEach(video => {
            if (video.tagName === 'VIDEO') {
                video.pause();
            } else if (video.tagName === 'IFRAME') {
                // Detener videos de YouTube
                video.src = video.src; // Esto recarga el iframe
            }
        });
    }

    function navigateLightbox(direction) {
        currentMediaIndex += direction;
        
        if (currentMediaIndex >= mediaItems.length) currentMediaIndex = 0;
        if (currentMediaIndex < 0) currentMediaIndex = mediaItems.length - 1;
        
        const mediaItem = mediaItems[currentMediaIndex];
        const mediaElement = createMediaElement(mediaItem);
        openLightbox(mediaElement, currentMediaIndex);
    }

    function createMediaElement(mediaItem) {
        if (mediaItem.classList.contains('video-thumbnail')) {
            const video = document.createElement('video');
            video.src = mediaItem.querySelector('source').src;
            video.controls = true;
            return video;
        } else if (mediaItem.tagName === 'IFRAME') {
            const iframe = document.createElement('iframe');
            iframe.src = mediaItem.src;
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', '');
            return iframe;
        } else {
            const img = document.createElement('img');
            img.src = mediaItem.src;
            return img;
        }
    }

    // Eventos del lightbox
    lightbox.querySelector('.close-lightbox').addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });

    lightbox.querySelector('.prev').addEventListener('click', () => navigateLightbox(-1));
    lightbox.querySelector('.next').addEventListener('click', () => navigateLightbox(1));

    // Inicializar navegación con teclado
    document.addEventListener('keydown', function(e) {
        if (lightbox.style.display === 'block') {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') navigateLightbox(-1);
            if (e.key === 'ArrowRight') navigateLightbox(1);
        }
    });

    

    // =============================================
    // VALIDACIÓN DE FORMULARIOS
    // =============================================
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const marca = document.getElementById('marca').value.trim();
            const talla = document.getElementById('talla').value.trim();
            
            if (!marca && !talla) {
                e.preventDefault();
                alert('Por favor ingresa al menos un criterio de búsqueda (marca o talla)');
            }
        });
    }

    // Validación para newpicture.php
    const uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            const youtubeInput = this.querySelector('input[name="youtube_url"]');
            
            if (!fileInput.files.length && !youtubeInput.value.trim()) {
                e.preventDefault();
                alert('Debes seleccionar un archivo o ingresar un enlace de YouTube');
            }
        });
    }

    // =============================================
    // PROTECCIÓN BÁSICA Y UTILIDADES
    // =============================================
    // Deshabilitar clic derecho e inspección
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    document.addEventListener('keydown', function(e) {
        // Deshabilitar F12, Ctrl+Shift+I, Ctrl+Shift+C, Ctrl+U
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') || 
            (e.ctrlKey && e.shiftKey && e.key === 'C') || 
            (e.ctrlKey && e.key === 'U')) {
            e.preventDefault();
        }
    });

    // Ajustar elementos según tamaño de pantalla
    function adjustForScreenSize() {
        const screenWidth = window.innerWidth;
        
        if (screenWidth < 768) {
            // Ajustes específicos para móviles
            document.querySelectorAll('button').forEach(button => {
                button.style.fontSize = '14px';
            });
        }
    }

    // Mostrar mensajes temporales
    function showMessage(type, message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `mensaje ${type}`;
        messageDiv.textContent = message;
        
        const main = document.querySelector('main');
        if (main) {
            main.insertBefore(messageDiv, main.firstChild);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    }

    // =============================================
    // INICIALIZACIÓN
    // =============================================
    
    adjustForScreenSize();
    window.addEventListener('resize', adjustForScreenSize);

    console.log('Scripts cargados correctamente');
});