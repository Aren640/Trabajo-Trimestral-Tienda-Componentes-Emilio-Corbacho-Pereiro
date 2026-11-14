const listaPedidos = document.getElementById('listaPedidos');
const mensajePedidos = document.getElementById('mensajePedidos');
const actualizarPedidos = document.getElementById('actualizarPedidos');

if (actualizarPedidos) {
    actualizarPedidos.addEventListener('click', cargarPedidos);
}

async function cargarPedidos() {
    actualizarPedidos.disabled = true;
    listaPedidos.replaceChildren();
    mensajePedidos.textContent = 'Cargando pedidos...';
    mensajePedidos.className = 'alert alert-secondary';
    document.getElementById('botonAdmin').classList.add('d-none');

    try {
        const respuesta = await fetch('../php/admin.php');
        const pedidos = await respuesta.json();
        if (!respuesta.ok) {
            mensajePedidos.textContent = pedidos.mensaje;
            mensajePedidos.className = 'alert alert-danger';
            return;
        }
        document.getElementById('botonAdmin').classList.remove('d-none');
        if (pedidos.length === 0) {
            mensajePedidos.textContent = 'Todavia no hay pedidos.';
            return;
        }

        for (const pedido of pedidos) {
            const tarjeta = document.createElement('article');
            tarjeta.className = 'card mb-3';
            tarjeta.innerHTML = `
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h5 mb-0 text-break">Pedido <span class="referencia"></span></h2>
                        <span class="estado badge text-bg-secondary"></span>
                    </div>
                    <p class="mb-1">Cliente: <strong class="cliente text-break"></strong></p>
                    <p class="correo text-break mb-1"></p>
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
            tarjeta.querySelector('.cliente').textContent = pedido.nombre;
            tarjeta.querySelector('.correo').textContent = pedido.correo;
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
            listaPedidos.appendChild(tarjeta);
        }
        mensajePedidos.classList.add('d-none');
    } catch (error) {
        listaPedidos.replaceChildren();
        mensajePedidos.textContent = 'No se pudieron cargar los pedidos. Intentalo mas tarde.';
        mensajePedidos.className = 'alert alert-danger';
    } finally {
        actualizarPedidos.disabled = false;
    }
}

const formularioProducto = document.getElementById('formularioProducto');

if (listaPedidos) {
    cargarPedidos();
} else {
    prepararPanel();
}

async function prepararPanel() {
    const mensaje = document.getElementById('mensajeAdmin');
    try {
        let seccion = 'panel';
        if (formularioProducto) {
            seccion = 'productos';
        }
        const respuesta = await fetch('../php/admin.php?seccion=' + seccion);
        const datos = await respuesta.json();
        if (!respuesta.ok) {
            mensaje.textContent = datos.mensaje;
            mensaje.className = 'alert alert-danger';
            return;
        }

        document.getElementById('contenidoAdmin').classList.remove('d-none');
        document.getElementById('botonAdmin').classList.remove('d-none');
        mensaje.classList.add('d-none');
    } catch (error) {
        mensaje.textContent = 'No se pudo cargar el panel. Recarga la pagina.';
        mensaje.className = 'alert alert-danger';
    }
}

if (formularioProducto) {
    formularioProducto.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        const mensaje = document.getElementById('mensajeAdmin');
        const boton = formularioProducto.querySelector('button[type="submit"]');
        const enlace = document.getElementById('verProducto');
        boton.disabled = true;
        enlace.classList.add('d-none');

        try {
            const respuesta = await fetch('../php/admin.php?seccion=productos', {
                method: 'POST',
                body: new FormData(formularioProducto)
            });
            const datos = await respuesta.json();
            mensaje.textContent = datos.mensaje;
            if (respuesta.ok) {
                mensaje.className = 'alert alert-success';
            } else {
                mensaje.className = 'alert alert-danger';
            }
            if (respuesta.ok) {
                formularioProducto.reset();
                enlace.href = '../producto.html?id=' + encodeURIComponent(datos.id);
                enlace.classList.remove('d-none');
            } else if (respuesta.status === 401 || respuesta.status === 403) {
                document.getElementById('contenidoAdmin').classList.add('d-none');
                document.getElementById('botonAdmin').classList.add('d-none');
            }
        } catch (error) {
            mensaje.textContent = 'No se pudo comprobar el guardado. Revisa la tienda antes de volver a enviarlo.';
            mensaje.className = 'alert alert-danger';
        } finally {
            boton.disabled = false;
        }
    });
}
