// Funcionalidad del menú móvil
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    // Función para manejar el cambio de tamaño de la ventana
    function handleResize() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            sidebar.style.transform = 'none';
        } else {
            sidebar.style.transform = sidebar.classList.contains('active') ? 'translateX(0)' : 'translateX(-100%)';
        }
    }

    // Inicializar el estado del menú
    handleResize();

    // Escuchar cambios en el tamaño de la ventana
    window.addEventListener('resize', handleResize);

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            handleResize();
        });

        // Cerrar el menú al hacer clic fuera de él
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                const isClickInsideMenu = sidebar.contains(event.target);
                const isClickOnToggle = mobileMenuToggle.contains(event.target);

                if (!isClickInsideMenu && !isClickOnToggle && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    handleResize();
                }
            }
        });

        // Prevenir que los clics dentro del sidebar cierren el menú
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Responsive tables
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const wrapper = document.createElement('div');
        wrapper.classList.add('table-responsive');
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const requiredInputs = form.querySelectorAll('[required]');
        
        requiredInputs.forEach(input => {
            input.addEventListener('invalid', function(e) {
                e.preventDefault();
                this.classList.add('input-error');
            });

            input.addEventListener('input', function() {
                this.classList.remove('input-error');
            });
        });
    });

    // Responsive images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
    });

    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    });
});
