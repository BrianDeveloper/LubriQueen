function addToCart(productoId, cantidad) {
    fetch('client/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `producto_id=${productoId}&cantidad=${cantidad}`
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 401) {
                Swal.fire({
                    title: 'Debe iniciar sesión',
                    text: 'Para añadir productos al carrito debe iniciar sesión',
                    icon: 'error',
                    confirmButtonText: 'Ir al login',
                    confirmButtonColor: '#dc3545',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'login.php';
                    }
                });
                return Promise.reject('No autenticado');
            }
            return Promise.reject('Error en la solicitud');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            Swal.fire({
                title: 'Error',
                text: data.error,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        } else {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Producto agregado al carrito correctamente',
                icon: 'success',
                confirmButtonColor: '#dc3545',
                timer: 1500,
                showConfirmButton: false
            });
        }
    })
    .catch(error => {
        if (error !== 'No autenticado' && error !== 'Error en la solicitud') {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al procesar la solicitud',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}
