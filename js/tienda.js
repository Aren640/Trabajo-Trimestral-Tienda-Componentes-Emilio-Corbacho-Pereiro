// El mismo archivo sirve para la tienda y los destacados de Inicio
const destacados = document.getElementById('productosDestacados');
let lista = document.getElementById('productosTienda');
if (!lista) {
    lista = destacados;
}
const mensaje = document.getElementById('mensajeProductos');
let ruta = 'php/productos.php';

if (destacados) {
    ruta += '?destacados=1';
}

async function cargarProductos() {
    mensaje.textContent = 'Cargando productos...';
    mensaje.classList.remove('d-none', 'text-danger');

    try {
        const respuesta = await fetch(ruta);
        const productos = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(productos.mensaje);
        }

        lista.innerHTML = '';

        if (productos.length === 0) {
            mensaje.textContent = destacados ? 'No hay productos destacados.' : 'No hay productos disponibles.';
            return;
        }

        for (const producto of productos) {
            const columna = document.createElement('div');
            columna.className = 'col';
            columna.innerHTML = `
                <article class="card h-100">
                    <img class="card-img-top imagen-producto object-fit-contain p-3" alt="">
                    <div class="card-body d-flex flex-column">
                        <h3 class="card-title h5"></h3>
                        <p class="categoria-producto card-text text-body-secondary small"></p>
                        <p class="precio-producto card-text texto-acento fw-bold fs-4"></p>
                        <p class="stock-producto card-text small"></p>
                        <button class="btn btn-primary mt-auto" type="button" disabled>
                            <i class="bi bi-cart3" aria-hidden="true"></i> Añadir al carrito
                        </button>
                    </div>
                </article>`;

            const imagen = columna.querySelector('img');
            imagen.src = producto.imagen;
            imagen.alt = producto.nombre;
            columna.querySelector('h3').textContent = producto.nombre;
            columna.querySelector('.categoria-producto').textContent = producto.categoria;
            columna.querySelector('.precio-producto').textContent = producto.precio.toLocaleString('es-ES', {
                style: 'currency',
                currency: 'EUR'
            });
            const stock = columna.querySelector('.stock-producto');
            if (producto.stock > 0) {
                stock.textContent = 'En stock';
            } else {
                stock.textContent = 'Sin stock';
            }
            lista.appendChild(columna);
        }

        mensaje.classList.add('d-none');
    } catch (error) {
        mensaje.textContent = 'No se pudieron cargar los productos. Intentalo mas tarde.';
        mensaje.classList.add('text-danger');
    }
}

cargarProductos();
