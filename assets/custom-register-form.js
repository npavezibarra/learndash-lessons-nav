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

    // Username availability check
    const userInput = document.getElementById("reg_user_login");
    if (userInput) {
        const feedback = document.createElement("p"); // Create feedback element
        feedback.style.color = "red";
        userInput.parentNode.appendChild(feedback);

        userInput.addEventListener("input", function() {
            const username = userInput.value.toLowerCase().trim(); // Trim and convert to lowercase

            if (username.length > 0) {
                // Make the AJAX call to check username availability
                fetch(ajaxurl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `action=check_username&username=${username}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        feedback.innerText = "Este nombre de usuario ya está en uso.";
                        feedback.style.color = "red";
                    } else {
                        feedback.innerText = "Este nombre de usuario está disponible.";
                        feedback.style.color = "green";
                    }
                })
                .catch(() => {
                    feedback.innerText = "Error al verificar el nombre de usuario.";
                    feedback.style.color = "red";
                });
            } else {
                feedback.innerText = ""; // Clear feedback if input is empty
            }
        });
    }
});

// Handle the registration form submission with AJAX
document.getElementById("registerform").addEventListener("submit", function(event) {
    event.preventDefault();
    const formData = new FormData(this);

    fetch(ajaxurl, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById("registerform").style.display = "none";
            const successMessage = document.createElement("p");
            successMessage.style.color = "green";
            successMessage.innerText = data.data.message;
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
