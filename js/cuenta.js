const historial = document.getElementById('historialPedidos');
const mensajeHistorial = document.getElementById('mensajeHistorial');
const actualizarHistorial = document.getElementById('actualizarHistorial');

actualizarHistorial.addEventListener('click', cargarHistorial);

async function cargarHistorial() {
    actualizarHistorial.disabled = true;
    historial.replaceChildren();
    mensajeHistorial.textContent = 'Cargando pedidos...';
    mensajeHistorial.className = 'alert alert-secondary';

    try {
        const respuesta = await fetch('php/pedidos.php');
        const pedidos = await respuesta.json();
        if (!respuesta.ok) {
            mensajeHistorial.textContent = pedidos.mensaje;
            mensajeHistorial.className = 'alert alert-danger';
            return;
        }
        if (pedidos.length === 0) {
            mensajeHistorial.textContent = 'Todavia no has realizado pedidos.';
            return;
        }

        for (const pedido of pedidos) {
            const tarjeta = document.createElement('article');
            tarjeta.className = 'card mb-3';
            tarjeta.innerHTML = `
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h3 class="h5 text-break mb-0">Pedido <span class="referencia"></span></h3>
                        <span class="estado badge text-bg-secondary"></span>
                    </div>
                    <p class="fecha text-body-secondary"></p>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <caption class="visually-hidden">Productos del pedido</caption>
                            <thead><tr><th scope="col">Producto</th><th scope="col">Cantidad</th><th scope="col">Precio por unidad</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <p class="fw-bold text-end mb-0">Total: <span class="total texto-acento"></span></p>
                </div>`;
            tarjeta.querySelector('.referencia').textContent = pedido.id;
            tarjeta.querySelector('.estado').textContent = pedido.estado;
            tarjeta.querySelector('.fecha').textContent = new Date(pedido.fecha).toLocaleString('es-ES');
            tarjeta.querySelector('.total').textContent = pedido.total.toLocaleString('es-ES', {style: 'currency', currency: 'EUR'});

            for (const producto of pedido.productos) {
                const fila = document.createElement('tr');
                fila.innerHTML = '<td class="nombre text-break"></td><td class="cantidad"></td><td class="precio"></td>';
                fila.querySelector('.nombre').textContent = producto.nombre;
                fila.querySelector('.cantidad').textContent = producto.cantidad;
                fila.querySelector('.precio').textContent = producto.precio.toLocaleString('es-ES', {style: 'currency', currency: 'EUR'});
                tarjeta.querySelector('tbody').appendChild(fila);
            }
            historial.appendChild(tarjeta);
        }
        mensajeHistorial.classList.add('d-none');
    } catch (error) {
        historial.replaceChildren();
        mensajeHistorial.textContent = 'No se pudieron cargar tus pedidos. Intentalo mas tarde.';
        mensajeHistorial.className = 'alert alert-danger';
    } finally {
        actualizarHistorial.disabled = false;
    }
}

const formularioClave = document.getElementById('formularioClave');
const mensajeClave = document.getElementById('mensajeClave');

formularioClave.addEventListener('submit', async function (evento) {
    evento.preventDefault();
    const boton = formularioClave.querySelector('button[type="submit"]');
    if (document.getElementById('claveNueva').value !== document.getElementById('repetirClave').value) {
    mensajeClave.textContent = 'Las contraseñas nuevas no coinciden.';
        mensajeClave.className = 'alert alert-danger';
        return;
    }
    boton.disabled = true;

    try {
        const respuesta = await fetch('php/clave.php', {
            method: 'POST',
            body: new FormData(formularioClave)
        });
        const datos = await respuesta.json();
        mensajeClave.textContent = datos.mensaje;
        if (respuesta.ok) {
            mensajeClave.className = 'alert alert-success';
            formularioClave.reset();
        } else {
            mensajeClave.className = 'alert alert-danger';
        }
    } catch (error) {
        mensajeClave.textContent = 'No se pudo comprobar el cambio. Vuelve a iniciar sesion antes de intentarlo de nuevo.';
        mensajeClave.className = 'alert alert-danger';
    } finally {
        boton.disabled = false;
    }
});

document.getElementById('modalClave').addEventListener('hidden.bs.modal', function () {
 formularioClave.reset();
    mensajeClave.classList.add('d-none');
});
