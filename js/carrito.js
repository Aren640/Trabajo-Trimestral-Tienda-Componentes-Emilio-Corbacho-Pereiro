// Bloquear los botones mientras se guarda un cambio en el carrito
let carritoOcupado = false;

function mostrarMensajeCarrito(texto, tipo) {
    const aviso = document.getElementById('mensajeCarrito');
    if (aviso) {
        aviso.textContent = texto;
        aviso.classList.remove('d-none', 'alert-success', 'alert-danger');
        aviso.classList.add('alert-' + tipo);
    }
}

async function cargarCarrito(silencioso = false) {
    const lista = document.getElementById('productosCarrito');
    try {
        const respuesta = await fetch('php/carrito.php');
        const datos = await respuesta.json();
        if (silencioso && carritoOcupado) {
            return;
        }
        if (!respuesta.ok && respuesta.status !== 401) {
            throw new Error('No se pudo cargar el carrito.');
        }

        let unidades = 0;
        let productos = [];
        if (respuesta.ok) {
            unidades = datos.unidades;
            productos = datos.productos;
        }
        for (const contador of document.querySelectorAll('.contador-carrito')) {
            contador.textContent = unidades;
            contador.closest('a').setAttribute('aria-label', 'Ver carrito, ' + unidades + ' unidades');
        }

        if (!lista) {
            return;
        }

        // Si el carrito no ha cambiado actualizar el stock sin rehacer las tarjetas
        const tarjetas = lista.children;
        let mismosProductos = productos.length === tarjetas.length;
        if (mismosProductos) {
            for (let i = 0; i < productos.length; i++) {
                const id = tarjetas[i].dataset.id;
                const cantidad = Number(tarjetas[i].dataset.cantidad);
                if (productos[i].id !== id || productos[i].cantidad !== cantidad) {
                    mismosProductos = false;
                    break;
                }
            }
        }
        if (silencioso && respuesta.ok && productos.length > 0 && mismosProductos) {
            for (let i = 0; i < productos.length; i++) {
                tarjetas[i].querySelector('.cantidad').max = productos[i].cantidad + productos[i].disponibles;
                tarjetas[i].querySelector('.sumar').disabled = productos[i].disponibles <= 0;
            }
            return;
        }
        if (silencioso && lista.contains(document.activeElement) && document.activeElement.tagName === 'INPUT') {
            return;
        }
        lista.replaceChildren();
        document.getElementById('carritoVacio').classList.toggle('d-none', productos.length > 0);
        document.getElementById('resumenCarrito').classList.toggle('d-none', productos.length === 0);
        document.getElementById('accesoCarrito').classList.toggle('d-none', respuesta.ok);
        const textoVacio = document.getElementById('textoCarritoVacio');
        if (respuesta.ok) {
            textoVacio.textContent = 'Tu carrito esta vacio.';
        } else {
            textoVacio.textContent = 'Inicia sesion para ver tu carrito.';
        }

        for (const producto of productos) {
            const tarjeta = document.createElement('article');
            tarjeta.className = 'card mb-3';
            tarjeta.dataset.id = producto.id;
            tarjeta.dataset.cantidad = producto.cantidad;
            tarjeta.innerHTML = `
                <div class="card-body row g-3 align-items-center">
                    <div class="col-4 col-md-2"><div class="ratio ratio-1x1"><img class="object-fit-contain" alt=""></div></div>
                    <div class="col-8 col-md-3">
                        <h2 class="h5"><a class="nombre text-reset text-decoration-none"></a></h2>
                        <p class="precio mb-0 text-body-secondary"></p>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Cantidad</label>
                        <div class="input-group flex-nowrap">
                            <button class="btn btn-outline-secondary restar" type="button" aria-label="Restar una unidad">−</button>
                            <input class="form-control text-center cantidad" type="number" min="1" step="1" required>
                            <button class="btn btn-outline-secondary sumar" type="button" aria-label="Sumar una unidad">+</button>
                        </div>
                    </div>
                    <div class="col-6 col-md-2"><p class="subtotal texto-acento fw-bold mb-0"></p></div>
                    <div class="col-6 col-md-2 text-end"><button class="btn btn-outline-danger quitar" type="button">Quitar</button></div>
                </div>`;

            const nombre = tarjeta.querySelector('.nombre');
            nombre.textContent = producto.nombre;
            nombre.href = 'producto.html?id=' + encodeURIComponent(producto.id);
            const imagen = tarjeta.querySelector('img');
            if (producto.imagen) {
                imagen.src = producto.imagen;
                imagen.alt = producto.nombre;
            } else {
                imagen.remove();
            }
            tarjeta.querySelector('.precio').textContent = producto.precio.toLocaleString('es-ES', {style: 'currency', currency: 'EUR'}) + ' / unidad';
            tarjeta.querySelector('.subtotal').textContent = producto.subtotal.toLocaleString('es-ES', {style: 'currency', currency: 'EUR'});

            const campo = tarjeta.querySelector('.cantidad');
            campo.id = 'cantidad-' + producto.id;
            tarjeta.querySelector('label').htmlFor = campo.id;
            campo.value = producto.cantidad;
            campo.max = producto.cantidad + producto.disponibles;
            tarjeta.querySelector('.restar').disabled = producto.cantidad <= 1;
            tarjeta.querySelector('.sumar').disabled = producto.disponibles <= 0;
            tarjeta.querySelector('.restar').addEventListener('click', function () {
                cambiarCarrito('cambiar', producto.id, producto.cantidad - 1);
            });
            tarjeta.querySelector('.sumar').addEventListener('click', function () {
                cambiarCarrito('cambiar', producto.id, producto.cantidad + 1);
            });
            campo.addEventListener('change', function () {
                if (campo.reportValidity()) {
                    cambiarCarrito('cambiar', producto.id, campo.value);
                } else {
                    campo.value = producto.cantidad;
                }
            });
            tarjeta.querySelector('.quitar').addEventListener('click', function () {
                cambiarCarrito('eliminar', producto.id);
            });
            lista.appendChild(tarjeta);
        }

        document.getElementById('totalCarrito').textContent = (datos.total || 0).toLocaleString('es-ES', {style: 'currency', currency: 'EUR'});
    } catch (error) {
        if (!silencioso) {
            mostrarMensajeCarrito('No se pudo cargar el carrito. Recarga la pagina.', 'danger');
        }
    }
}

// Revisar el stock cada cinco segundos sin borrar la cantidad escrita
setInterval(function () {
    if (!carritoOcupado) {
        cargarCarrito(true);
    }
}, 5000);

async function cambiarCarrito(accion, id, cantidad = 1) {
    if (carritoOcupado) {
        return;
    }
    carritoOcupado = true;
    const botones = document.querySelectorAll('#productosCarrito button, #productosCarrito input, .anadir-carrito, #botonCarrito, #confirmarPedido');
    // Al terminar dejar desactivados los botones que ya lo estaban
    const desactivados = [];
    for (const boton of botones) {
        desactivados.push(boton.disabled);
        boton.disabled = true;
    }

    try {
        const formulario = new FormData();
        formulario.append('accion', accion);
        formulario.append('id', id);
        formulario.append('cantidad', cantidad);
        const respuesta = await fetch('php/carrito.php', {method: 'POST', body: formulario});
        const datos = await respuesta.json();
        if (respuesta.ok) {
            mostrarMensajeCarrito(datos.mensaje, 'success');
        } else {
            mostrarMensajeCarrito(datos.mensaje, 'danger');
        }

        if (respuesta.status === 401) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAcceso')).show();
        }
        await cargarCarrito();
        if (typeof cargarProducto === 'function') {
            await cargarProducto();
        }
        if (typeof cargarProductos === 'function') {
            await cargarProductos();
        }
    } catch (error) {
        mostrarMensajeCarrito('No se pudo actualizar el carrito. Revisa el carrito antes de volver a intentarlo.', 'danger');
    } finally {
        carritoOcupado = false;
        for (let i = 0; i < botones.length; i++) {
            botones[i].disabled = desactivados[i];
        }
        if (typeof actualizarCantidad === 'function') {
            actualizarCantidad();
        }
    }
}

const confirmarPedido = document.getElementById('confirmarPedido');

if (confirmarPedido) {
    confirmarPedido.addEventListener('click', async function () {
        if (carritoOcupado) {
            return;
        }
        carritoOcupado = true;
        const controles = document.querySelectorAll('#productosCarrito button, #productosCarrito input, #confirmarPedido');
        // Al terminar dejar desactivados los botones que ya lo estaban
        const desactivados = [];
        for (const control of controles) {
            desactivados.push(control.disabled);
            control.disabled = true;
        }
        confirmarPedido.textContent = 'Enviando pedido...';

        try {
            const respuesta = await fetch('php/pedidos.php', {method: 'POST'});
            const datos = await respuesta.json();
            if (respuesta.ok) {
                mostrarMensajeCarrito(datos.mensaje + ' Referencia: ' + datos.id, 'success');
            } else {
                mostrarMensajeCarrito(datos.mensaje, 'danger');
            }
            if (respuesta.status === 401) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAcceso')).show();
            }
            await cargarCarrito();
        } catch (error) {
            mostrarMensajeCarrito('No se pudo comprobar el pedido. Recarga la pagina para revisar tu carrito antes de volver a intentarlo.', 'danger');
        } finally {
            carritoOcupado = false;
            for (let i = 0; i < controles.length; i++) {
                controles[i].disabled = desactivados[i];
            }
            confirmarPedido.textContent = 'Confirmar pedido';
        }
    });
}
