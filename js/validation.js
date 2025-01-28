document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            const password = this.querySelector('input[name="password"]').value;
            let isValid = true;
            let errors = [];

            // Validar email
            if (!email) {
                errors.push('El correo electrónico es requerido');
                isValid = false;
            } else if (!isValidEmail(email)) {
                errors.push('Por favor, ingrese un correo electrónico válido');
                isValid = false;
            }

            // Validar contraseña
            if (!password) {
                errors.push('La contraseña es requerida');
                isValid = false;
            }

            // Si hay errores, prevenir el envío del formulario y mostrar errores
            if (!isValid) {
                e.preventDefault();
                showErrors(errors);
            }
        });
    }

    // Función para validar email
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email.toLowerCase());
    }

    // Función para mostrar errores
    function showErrors(errors) {
        // Remover contenedor de errores existente si hay
        const existingErrorContainer = document.querySelector('.error-container');
        if (existingErrorContainer) {
            existingErrorContainer.remove();
        }

        // Crear nuevo contenedor de errores
        const errorContainer = document.createElement('div');
        errorContainer.className = 'error-container';

        // Agregar cada mensaje de error
        errors.forEach(error => {
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            errorMessage.textContent = error;
            errorContainer.appendChild(errorMessage);
        });

        // Insertar el contenedor de errores antes del formulario
        loginForm.insertBefore(errorContainer, loginForm.firstChild);
    }

    // Limpiar errores cuando el usuario comienza a escribir
    const inputs = loginForm.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const errorContainer = document.querySelector('.error-container');
            if (errorContainer) {
                errorContainer.remove();
            }
            this.classList.remove('input-error');
        });
    });
});
