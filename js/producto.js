const ficha = document.getElementById('fichaProducto');
const mensaje = document.getElementById('mensajeProducto');

async function cargarProducto() {
    const id = new URLSearchParams(window.location.search).get('id');

    if (!id) {
        mensaje.textContent = 'Selecciona un producto desde la tienda.';
        return;
    }

    try {
        const respuesta = await fetch('php/productos.php?id=' + encodeURIComponent(id));
        const producto = await respuesta.json();

        if (!respuesta.ok) {
            mensaje.textContent = producto.mensaje;
            mensaje.classList.add('text-danger');
            return;
        }

        const imagen = document.createElement('img');
        imagen.src = producto.imagen;
        imagen.alt = producto.nombre;
        imagen.className = 'imagen-ficha d-block w-100 object-fit-contain p-4';
        document.getElementById('imagenProducto').appendChild(imagen);
        document.getElementById('nombreProducto').textContent = producto.nombre;
        document.getElementById('migaProducto').textContent = producto.nombre;
        document.getElementById('categoriaProducto').textContent = producto.categoria;
        document.getElementById('precioProducto').textContent = producto.precio.toLocaleString('es-ES', {
            style: 'currency',
            currency: 'EUR'
        });
        const stock = document.getElementById('stockProducto');
        if (producto.stock > 0) {
            stock.textContent = 'En stock';
        } else {
            stock.textContent = 'Sin stock';
        }
        document.title = producto.nombre + ' | VEXORA';
        mensaje.classList.add('d-none');
        ficha.classList.remove('d-none');
    } catch (error) {
        mensaje.textContent = 'No se pudo cargar el producto. Intentalo mas tarde.';
        mensaje.classList.add('text-danger');
    }
}

cargarProducto();
