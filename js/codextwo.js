document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('DOMContentLoaded', function() {
        // Obtener el formulario
        const form = document.getElementById('searchForm');
        
        // 1. SIEMPRE limpiar el formulario al cargar la página
        form.reset();
        
        // 2. Configurar el historial para controlar el botón atrás
        if (window.history && window.history.pushState) {
            // Agregar estado actual al historial
            window.history.pushState(null, null, window.location.href);
            
            // 3. Controlar el evento popstate (botón atrás/adelante)
            window.addEventListener('popstate', function() {
                // Limpiar el formulario cuando usen el botón atrás
                form.reset();
                
                // Volver a agregar el estado al historial para mantener la página
                window.history.pushState(null, null, window.location.href);
            });
        }
    });

    // 4. Limpiar también antes de recargar/cerrar la página
    window.addEventListener('beforeunload', function() {
        document.getElementById('searchForm').reset();
    });

    // 5. Opcional: Limpiar también cuando se envía el formulario
    document.getElementById('searchForm').addEventListener('submit', function() {
        // Puedes limpiar inmediatamente o usar un timeout
        setTimeout(() => this.reset(), 100);
    });
});


