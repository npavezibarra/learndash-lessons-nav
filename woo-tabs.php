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
    echo mostrar_cursos_usuario(); // Mostrar los cursos del usuario
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

// Mostrar los cursos en los que el usuario está inscrito
function mostrar_cursos_usuario() {
    // Obtén el ID del usuario actual
    $user_id = get_current_user_id();

    // Asegúrate de que el usuario esté conectado
    if ( !is_user_logged_in() || !$user_id ) {
        return '<p>Debes estar conectado para ver tus cursos.</p>';
    }

    // Obtén los cursos en los que el usuario está inscrito
    $user_courses = ld_get_mycourses( $user_id );

    // Si el usuario no está inscrito en ningún curso
    if ( empty( $user_courses ) ) {
        // Mostrar el mensaje y botón para ver el catálogo de cursos
        $output = '<div class="no-courses">';
        $output .= '<p>No estás inscrito en ningún curso, visita el catálogo de cursos y una vez que te inscribas en uno podrás verlos en esta sección.</p>';
        $output .= '<a href="' . esc_url( home_url( '/courses' ) ) . '" class="button ver-cursos" style="display: inline-block; font-size: 12px; padding: 10px 20px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 5px;">VER CURSOS</a>';
        $output .= '</div>';
        return $output;
    }

    // Salida HTML para los cursos
    $output = '<div class="cursos-usuario">';
    $output .= '<h3>Mis Cursos</h3>';
    $output .= '<p>Estos son los cursos que estás cursando.</p>';

    foreach ( $user_courses as $course_id ) {
        // Obtener la información del curso
        $course_title = get_the_title( $course_id );
        $course_link = get_permalink( $course_id );
        $course_image = get_the_post_thumbnail( $course_id, 'medium' ); // Obtener la imagen destacada del curso

        // Obtener el número total de lecciones en el curso
        $total_lessons = count(learndash_get_course_steps($course_id));

        // Obtener el número de lecciones completadas por el usuario
        $completed_lessons = learndash_course_get_completed_steps_legacy( $user_id, $course_id );

        // Calcular el porcentaje completado
        if ($total_lessons > 0) {
            $percentage_complete = ($completed_lessons / $total_lessons) * 100;
        } else {
            $percentage_complete = 0;
        }

        // Mostrar el curso
        $output .= '<div class="curso-item" style="display: flex; align-items: center; margin-bottom: 20px;">';
        
        // Mostrar imagen del curso
        if ( $course_image ) {
            $output .= '<div class="curso-imagen" style="margin-right: 20px;">' . $course_image . '</div>';
        } else {
            $output .= '<div class="curso-imagen" style="width: 150px; height: 150px; background-color: #add8e6; margin-right: 20px;"></div>';
        }

        // Mostrar el título del curso y el progreso
        $output .= '<div class="curso-info" style="flex-grow: 1;">';
        $output .= '<a href="' . esc_url( $course_link ) . '">';
        $output .= '<h4 style="margin: 0 0 10px;">' . esc_html( $course_title ) . '</h4>';
        $output .= '</a>';

        // Mostrar el progreso personalizado usando <progress>
        $output .= '<div class="card__progress">';
        $output .= '<progress max="100" value="' . esc_attr( $percentage_complete ) . '"></progress>';
        $output .= '<p>' . esc_html( round($percentage_complete) ) . '% completado</p>';
        $output .= '</div>'; // Cerrar card__progress

        $output .= '</div>'; // Cerrar div curso-info
        $output .= '</div>'; // Cerrar div curso-item
    }

    $output .= '</div>'; // Cerrar div cursos-usuario

    return $output;
}









