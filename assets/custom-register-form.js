document.addEventListener("DOMContentLoaded", function() {
    // Toggle between login and registration forms
    const toggleLogin = document.getElementById("toggle-login");
    const toggleRegister = document.getElementById("toggle-register");
    const formRegistro = document.getElementById("form-registro");
    const formLogin = document.getElementById("form-login");

    if (toggleLogin && formRegistro && formLogin) {
        toggleLogin.addEventListener("click", function(event) {
            event.preventDefault();
            formRegistro.style.display = "none";
            formLogin.style.display = "block";
        });
    }

    if (toggleRegister && formRegistro && formLogin) {
        toggleRegister.addEventListener("click", function(event) {
            event.preventDefault();
            formLogin.style.display = "none";
            formRegistro.style.display = "block";
        });
    }

    // Handle the registration form submission with AJAX
    const registerForm = document.getElementById("registerform");
    if (registerForm) {
        registerForm.addEventListener("submit", function(event) {
            event.preventDefault();

            // Validate email before submitting
            const emailField = document.getElementById("user_email").value;
            const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

            if (!emailPattern.test(emailField)) {
                alert("Por favor ingresa un email válido.");
                return; // Stop execution if email is invalid
            }

            const formData = new FormData(this);

            fetch(ajaxurl, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("registerform").style.display = "none";
                    const successMessage = document.createElement("div");
                    successMessage.innerHTML = data.data.message; // Display success message as HTML
                    document.querySelector(".registro-o-login").appendChild(successMessage);
                } else {
                    const errorMessage = document.createElement("p");
                    errorMessage.style.color = "red";
                    errorMessage.innerText = data.data.message || "Hubo un error al intentar registrarte.";
                    document.querySelector(".registro-o-login").appendChild(errorMessage);
                }
            })
            .catch(() => {
                const errorMessage = document.createElement("p");
                errorMessage.style.color = "red";
                errorMessage.innerText = "Error en la solicitud. Inténtalo de nuevo.";
                document.querySelector(".registro-o-login").appendChild(errorMessage);
            });
        });
    }
});