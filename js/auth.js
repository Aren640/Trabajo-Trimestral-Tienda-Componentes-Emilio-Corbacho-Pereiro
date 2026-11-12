const formulario = document.getElementById('formularioRegistro');

if (formulario) {
    const mensaje = document.getElementById('mensajeRegistro');
    const boton = formulario.querySelector('button[type="submit"]');

    formulario.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        const clave = document.getElementById('claveRegistro').value;
        const repetir = document.getElementById('confirmarClaveRegistro').value;
        mensaje.className = 'alert d-none';

        if (clave !== repetir) {
            mensaje.className = 'alert alert-danger';
            mensaje.textContent = 'Las contraseñas no coinciden.';
            return;
        }

        boton.disabled = true;
        boton.textContent = 'Creando cuenta...';

        try {
            const respuesta = await fetch('php/registro.php', {
                method: 'POST',
                body: new FormData(formulario)
            });
            const datos = await respuesta.json();

            mensaje.className = respuesta.ok ? 'alert alert-success' : 'alert alert-danger';
            mensaje.textContent = datos.mensaje;

            if (respuesta.ok) {
                formulario.reset();
                await verSesion();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRegistro')).hide();
            }
        } catch (error) {
            mensaje.className = 'alert alert-danger';
            mensaje.textContent = 'No se pudo enviar el registro.';
        } finally {
            boton.disabled = false;
            boton.textContent = 'Crear cuenta';
        }
    });

    boton.disabled = false;
}

const acceso = document.getElementById('formularioAcceso');

if (acceso) {
    const mensaje = document.getElementById('mensajeAcceso');
    const boton = acceso.querySelector('button[type="submit"]');

    acceso.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        mensaje.className = 'alert d-none';
        boton.disabled = true;

        try {
            const respuesta = await fetch('php/login.php', {
                method: 'POST',
                body: new FormData(acceso)
            });
            const datos = await respuesta.json();

            mensaje.className = respuesta.ok ? 'alert alert-success' : 'alert alert-danger';
            mensaje.textContent = datos.mensaje;

            if (respuesta.ok) {
                acceso.reset();
                await verSesion();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAcceso')).hide();
            }
        } catch (error) {
            mensaje.className = 'alert alert-danger';
            mensaje.textContent = 'No se pudo iniciar sesion.';
        } finally {
            boton.disabled = false;
        }
    });

    boton.disabled = false;
}

verSesion();

// Los botones cambian segun la sesion que confirme PHP
async function verSesion() {
    const registro = document.getElementById('botonRegistro');
    const entrada = document.getElementById('botonAcceso');
    const cuenta = document.getElementById('botonPerfil');
    const perfil = document.getElementById('perfilNombre');
    const aviso = document.getElementById('avisoSesion');

    try {
        const respuesta = await fetch('php/login.php');
        if (!respuesta.ok) {
            throw new Error('No se pudo comprobar la sesion.');
        }
        const datos = await respuesta.json();
        aviso.classList.add('d-none');

        if (datos.nombre) {
            registro.classList.add('d-none');
            entrada.classList.add('d-none');
            cuenta.classList.remove('d-none');

            if (perfil) {
                perfil.textContent = datos.nombre;
            }
        } else if (perfil) {
            window.location.replace('index.html');
        }
    } catch (error) {
        aviso.textContent = 'No se pudo comprobar tu sesion. Recarga la pagina.';
        aviso.classList.remove('d-none');
    }
}
