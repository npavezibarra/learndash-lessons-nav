<?php

// Shortcode para mostrar el formulario de registro o login
function registro_o_login_shortcode( $atts ) {

    // Extract shortcode attributes (e.g., quiz_id)
    $atts = shortcode_atts( array(
        'quiz_id' => 0, // Default value is 0, which means no quiz ID
    ), $atts );

    // Check if the user is already logged in
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $mensaje = 'Ya has iniciado sesión como ' . $current_user->user_login . '. <a href="' . wp_logout_url( get_permalink() ) . '">Cerrar sesión</a>';
        return $mensaje;
    } else {
        // Display registration and login forms
        $formulario = '<div class="registro-o-login">';

        // Registration form
        $formulario .= '<h3>Registrarse</h3>';
        $formulario .= '<form name="registerform" id="registerform" action="' . esc_url( admin_url('admin-ajax.php') ) . '" method="post" novalidate="novalidate">';
        $formulario .= '<input type="hidden" name="action" value="register_user">';
        $formulario .= '<input type="hidden" name="quiz_id" value="' . esc_attr( $atts['quiz_id'] ) . '">'; // Pass quiz_id in hidden field

        // Add nonce for security
        $formulario .= wp_nonce_field( 'register_user_action', 'register_user_nonce', true, false );

        $formulario .= '<p>';
        $formulario .= '<label for="user_login">Nombre de usuario<br />';
        $formulario .= '<input type="text" name="user_login" id="user_login" class="input" value="" size="20" autocapitalize="off" required /></label>';
        $formulario .= '</p>';
        $formulario .= '<p>';
        $formulario .= '<label for="user_email">Correo electrónico<br />';
        $formulario .= '<input type="email" name="user_email" id="user_email" class="input" value="" size="25" required /></label>';
        $formulario .= '</p>';
        $formulario .= '<p>';
        $formulario .= '<label for="user_pass">Contraseña<br />';
        $formulario .= '<input type="password" name="user_pass" id="user_pass" class="input" value="" size="25" required /></label>';
        $formulario .= '</p>';
        $formulario .= '<input type="hidden" name="redirect_to" value="' . esc_url( get_permalink() ) . '" />';
        $formulario .= '<p class="submit">';
        $formulario .= '<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Registrarse" />';
        $formulario .= '</p>';
        $formulario .= '</form>';

        // Login form
        $formulario .= '<h3>Iniciar sesión</h3>';
        $formulario .= wp_login_form( array( 'echo' => false, 'redirect' => get_permalink() ) ); 
        $formulario .= '</div>';

        // JavaScript to handle form submission
        $formulario .= '<script>
            document.getElementById("registerform").addEventListener("submit", function(event) {
                event.preventDefault(); // Prevent default form submission
                var formData = new FormData(this); // Get the form data
                fetch("' . esc_url( admin_url('admin-ajax.php') ) . '", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide the form
                        document.getElementById("registerform").style.display = "none";
                        // Show confirmation message
                        document.getElementById("registerform").insertAdjacentHTML("afterend", "<p><strong>Check your email to confirm your account.</strong></p>");
                    }
                });
            });
        </script>';

        return $formulario;
    }
}
add_shortcode( 'registro_o_login', 'registro_o_login_shortcode' );


// Función para procesar el registro del usuario
function personalizar_registro() {
    // Verify nonce for security
    if ( ! isset( $_POST['register_user_nonce'] ) || ! wp_verify_nonce( $_POST['register_user_nonce'], 'register_user_action' ) ) {
        wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
        return;
    }

    // Proceed with user registration if nonce is valid
    $sanitized_user_login = sanitize_text_field( $_POST['user_login'] );
    $user_email = sanitize_email( $_POST['user_email'] );
    $quiz_id = isset( $_POST['quiz_id'] ) ? intval( $_POST['quiz_id'] ) : 0; // Get quiz_id from form submission

    if ( isset( $_POST['user_pass'] ) ) {
        $password = $_POST['user_pass'];

        // Validate password
        if ( strlen( $password ) < 8 ) {
            wp_send_json_error( array( 'message' => 'La contraseña debe tener al menos 8 caracteres.' ) );
            return;
        }

        // Generate a unique confirmation key
        $key = wp_generate_password( 20, false );

        // Store the user data temporarily
        $user_data = array(
            'user_login' => $sanitized_user_login,
            'user_pass'  => $password,
            'user_email' => $user_email,
            'confirm_key' => $key, 
        );
        set_transient( 'temp_user_' . $key, $user_data, DAY_IN_SECONDS );

        // Send confirmation email
        $to = $user_email;
        $subject = 'Confirm your account';
        $message = 'Please click the following link to confirm your account: ' . "\r\n\r\n";
        $message .= home_url( '/?confirm_user=' . $key . '&quiz_id=' . $quiz_id ); // Append quiz_id to confirmation link
        wp_mail( $to, $subject, $message );

        // Return a success message to the frontend
        wp_send_json_success( array( 'message' => 'Check your email to confirm your account.' ) );
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
            // Crea el usuario
            $user_id = wp_create_user( $user_data['user_login'], $user_data['user_pass'], $user_data['user_email'] );

            if ( ! is_wp_error( $user_id ) ) {
                // Elimina los datos temporales del usuario
                delete_transient( 'temp_user_' . $key );

                // Inicia sesión automáticamente al usuario
                wp_set_current_user( $user_id, $user_data['user_login'] );
                wp_set_auth_cookie( $user_id );
                do_action( 'wp_login', $user_data['user_login'] );

                // Verifica si hay un quiz_id en la URL
                if ( isset( $_GET['quiz_id'] ) ) {
                    $quiz_id = intval( $_GET['quiz_id'] );
                    $quiz_url = get_permalink( $quiz_id ); // Obtiene la URL del quiz de LearnDash
                    wp_redirect( $quiz_url );
                    exit;
                }

                // Redirección por defecto si no hay quiz_id
                wp_redirect( home_url() );
                exit;
            } else {
                wp_die( 'Error creating user account.' );
            }
        } else {
            wp_die( 'Invalid confirmation link.' );
        }
    }
}
add_action( 'init', 'confirmar_usuario' );
