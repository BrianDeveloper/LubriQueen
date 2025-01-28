document.addEventListener('DOMContentLoaded', function() {
    // Seleccionar todos los botones de toggle de contraseña
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Encontrar el campo de contraseña más cercano al botón
            const passwordField = this.closest('.password-field');
            const passwordInput = passwordField.querySelector('input[type="password"], input[type="text"]');

            if (passwordInput) {
                // Cambiar el tipo de input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Cambiar el ícono
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
                
                // Actualizar el título del botón
                this.setAttribute('title', type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
            }
        });
    });
});
