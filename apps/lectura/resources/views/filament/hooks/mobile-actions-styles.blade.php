<style>
    /* Estilos para acciones de encabezado en móviles */
    @media (max-width: 768px) {
        /* Contenedor principal de header con acciones */
        .fi-header {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 1rem !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
        
        /* Contenedor de acciones */
        .fi-header > div:last-child {
            width: 100% !important;
        }
        
        /* Grupo de acciones - asegurar ancho completo */
        .fi-ac {
            width: 100% !important;
        }
        
        .fi-ac > .flex {
            width: 100% !important;
            flex-direction: column !important;
        }
        
        /* Botones de acción */
        .fi-header .fi-btn {
            width: 100% !important;
            justify-content: center !important;
        }
    }
</style>
