<?php

// Shortcode para mostrar el formulario de registro o login
function registro_o_login_shortcode( $atts ) {

    // Encolar el archivo CSS personalizado con la ruta correcta
    wp_enqueue_style( 'custom-register-form-css', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/custom-register-form.css' );

    // Encolar el archivo JavaScript personalizado
    wp_enqueue_script( 'custom-register-form-js', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/custom-register-form.js', array(), false, true );

    wp_localize_script( 'custom-register-form-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );

    // Extrae los atributos del shortcode (en este caso, quiz_id)
    $atts = shortcode_atts( array(
        'quiz_id' => 0, // Valor por defecto es 0, lo que significa que no hay ID de quiz
    ), $atts );

    // Formulario de registro (se mostrará por defecto)
    $formulario = '<div class="registro-o-login">';

    $formulario .= '<div id="form-registro">';
    $formulario .= '<h3>Regístrate</h3>';
    $formulario .= '<p>o si ya eres miembro <a href="#" id="toggle-login" style="color:#d9534f;">haz login</a></p>';
    $formulario .= '<form name="registerform" id="registerform" action="' . esc_url( admin_url('admin-ajax.php') ) . '" method="post" novalidate="novalidate">';
    $formulario .= '<input type="hidden" name="action" value="register_user">';
    $formulario .= '<input type="hidden" name="quiz_id" value="' . esc_attr( $atts['quiz_id'] ) . '">'; 

    // Nombre y Apellido
    $formulario .= '<p>';
    $formulario .= '<label for="first_name">Nombre<br />';
    $formulario .= '<input type="text" name="first_name" id="first_name" class="input" value="" size="20" required /></label>';
    $formulario .= '</p>';
    $formulario .= '<p>';
    $formulario .= '<label for="last_name">Apellido<br />';
    $formulario .= '<input type="text" name="last_name" id="last_name" class="input" value="" size="20" required /></label>';
    $formulario .= '</p>';

    // Email
    $formulario .= '<p>';
    $formulario .= '<label for="user_email">Email<br />';
    $formulario .= '<input type="email" name="user_email" id="user_email" class="input" value="" size="25" required /></label>';
    $formulario .= '</p>';
    
    // Clave
    $formulario .= '<p>';
    $formulario .= '<label for="user_pass">Clave<br />';
    $formulario .= '<input type="password" name="user_pass" id="user_pass" class="input" value="" size="25" required /></label>';
    $formulario .= '</p>';
    
    $formulario .= '<input type="hidden" name="redirect_to" value="' . esc_url( get_permalink() ) . '" />';
    $formulario .= '<p class="submit">';
    $formulario .= '<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Registrarme" />';
    $formulario .= '</p>';
    $formulario .= '</form>';
    $formulario .= '</div>';

    // Formulario de login (oculto por defecto)
    $formulario .= '<div id="form-login" style="display:none;">';
    $formulario .= '<h3>Iniciar sesión</h3>';
    $formulario .= '<p>¿No tienes cuenta? <a href="#" id="toggle-register" style="color:#d9534f;">Regístrate aquí</a></p>';
    $formulario .= wp_login_form( array(
        'echo' => false, 
        'redirect' => get_permalink(), 
        'label_username' => 'Nombre de usuario o correo electrónico', 
        'label_password' => 'Clave',
        'label_remember' => 'Recordarme',
        'label_log_in'   => 'Iniciar sesión',
    )); 
    $formulario .= '</div>';

    $formulario .= '</div>';

    return $formulario;
}
add_shortcode( 'registro_o_login', 'registro_o_login_shortcode' );

// Función para procesar el registro del usuario
function personalizar_registro() {
    // Obtener los campos enviados
    $first_name = sanitize_text_field( $_POST['first_name'] );
    $last_name = sanitize_text_field( $_POST['last_name'] );
    $user_email = sanitize_email( $_POST['user_email'] );
    $quiz_id = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0; 

    // Validar que el email tenga el formato correcto
    if ( !is_email( $user_email ) ) {
        wp_send_json_error( array( 'message' => 'Por favor, introduce una dirección de correo válida.' ) );
        return;
    }

    // Comprobar si el email ya está registrado
    if ( email_exists( $user_email ) ) {
        wp_send_json_error( array( 'message' => 'Este correo ya está registrado.' ) );
        return;
    }

    // Combinar Nombre y Apellido para generar el nombre de usuario
    $sanitized_user_login = strtolower( $first_name . '.' . $last_name );

    // Si ya existe el nombre de usuario, añadir un número aleatorio
    while ( username_exists( $sanitized_user_login ) ) {
        $sanitized_user_login .= rand( 1, 100 );
    }

    // Procesar la contraseña
    if ( isset( $_POST['user_pass'] ) ) {
        $password = $_POST['user_pass'];

        // Validar que la contraseña tenga al menos 8 caracteres
        if ( strlen( $password ) < 8 ) {
            wp_send_json_error( array( 'message' => 'La contraseña debe tener al menos 8 caracteres.' ) );
            return;
        }

        // Generar clave de confirmación
        $key = wp_generate_password( 20, false );

        // Almacenar los datos del usuario temporalmente
        $user_data = array(
            'user_login' => $sanitized_user_login,
            'user_pass'  => $password,
            'user_email' => $user_email,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'confirm_key' => $key, 
        );
        set_transient( 'temp_user_' . $key, $user_data, DAY_IN_SECONDS );

        // Enviar email de confirmación
        $to = $user_email;
        $subject = 'Confirma tu cuenta';
        $message = 'Haz clic en el enlace para confirmar tu cuenta: ' . home_url( '/?confirm_user=' . $key . '&quiz_id=' . $quiz_id );
        wp_mail( $to, $subject, $message );

        // Mensaje de éxito con HTML sin escapar
        wp_send_json_success( array( 'message' => '<p class="success-message">Revisa tu correo para confirmar tu cuenta.<p>Haz click en el link <br>y serás redirigido a la evaluación.</p></p>' ) );

    }
}
add_action( 'wp_ajax_nopriv_register_user', 'personalizar_registro' );
add_action( 'wp_ajax_register_user', 'personalizar_registro' );

// Función para confirmar la cuenta del usuario
function confirmar_usuario() {
    if ( isset( $_GET['confirm_user'] ) ) {
        $key = $_GET['confirm_user'];
        $user_data = get_transient( 'temp_user_' . $key );

        if ( $user_data ) {
            // Crear el usuario
            $user_id = wp_create_user( $user_data['user_login'], $user_data['user_pass'], $user_data['user_email'], '', array(
                'first_name' => $user_data['first_name'],
                'last_name' => $user_data['last_name'],
            ));

            if ( ! is_wp_error( $user_id ) ) {
                // Eliminar los datos temporales del usuario
                delete_transient( 'temp_user_' . $key );

                // Iniciar sesión automáticamente al usuario
                wp_set_current_user( $user_id, $user_data['user_login'] );
                wp_set_auth_cookie( $user_id );
                do_action( 'wp_login', $user_data['user_login'] );

                // Verifica si hay un quiz_id en la URL
                if ( isset( $_GET['quiz_id'] ) && intval( $_GET['quiz_id'] ) > 0 ) {
                    $quiz_id = intval( $_GET['quiz_id'] );
                    $quiz_url = get_permalink( $quiz_id ); // Obtener la URL del quiz de LearnDash
                    if ( $quiz_url ) {
                        wp_redirect( $quiz_url ); // Redirigir al quiz
                        exit;
                    }
                }

                // Redirección por defecto si no hay quiz_id o si el quiz no existe
                wp_redirect( home_url() );
                exit;
            } else {
                wp_die( 'Error al crear la cuenta de usuario.' );
            }
        } else {
            wp_die( 'Enlace de confirmación inválido o ha expirado.' );
        }
    }
}
add_action( 'init', 'confirmar_usuario' );
