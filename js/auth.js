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

    });
}

const acceso = document.getElementById('formularioAcceso');

if (acceso) {
    const mensaje = document.getElementById('mensajeAcceso');
    const boton = acceso.querySelector('button[type="submit"]');

    acceso.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        mensaje.className = 'alert d-none';
        boton.disabled = true;

    });
}
