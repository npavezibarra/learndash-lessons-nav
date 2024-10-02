<?php

// Añadir nuevas pestañas a la página de "My Account" en WooCommerce
function agregar_pestanas_personalizadas( $items ) {
    // Añadir pestaña "Cursos"
    $items['cursos'] = 'Cursos';

    // Añadir pestaña "Evaluaciones"
    $items['evaluaciones'] = 'Evaluaciones';

    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'agregar_pestanas_personalizadas' );

// Añadir el contenido para la pestaña "Cursos"
function contenido_pestana_cursos() {
    echo '<h3>Mis Cursos</h3>';
    echo '<p>Aquí aparecerán los cursos que has adquirido o estás tomando.</p>';
    // Aquí puedes añadir el código para mostrar los cursos de LearnDash o cualquier otra información
}
add_action( 'woocommerce_account_cursos_endpoint', 'contenido_pestana_cursos' );

// Añadir el contenido para la pestaña "Evaluaciones"
function contenido_pestana_evaluaciones() {
    echo '<h3>Mis Evaluaciones</h3>';
    echo '<p>Aquí aparecerán las evaluaciones que has completado o que tienes pendientes.</p>';
    // Aquí puedes añadir el código para mostrar las evaluaciones de LearnDash o cualquier otra información
}
add_action( 'woocommerce_account_evaluaciones_endpoint', 'contenido_pestana_evaluaciones' );

// Crear los endpoints para las nuevas pestañas
function registrar_endpoints_personalizados() {
    add_rewrite_endpoint( 'cursos', EP_ROOT | EP_PAGES );
    add_rewrite_endpoint( 'evaluaciones', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'registrar_endpoints_personalizados' );

// Asegurarse de que los endpoints se redirijan correctamente
function flush_rewrite_rules_en_activacion() {
    registrar_endpoints_personalizados();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'flush_rewrite_rules_en_activacion' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
