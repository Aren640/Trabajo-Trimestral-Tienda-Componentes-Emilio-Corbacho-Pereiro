const contacto = document.getElementById('formularioContacto');
contacto.addEventListener('submit', async function (evento) {
    evento.preventDefault();
    const mensaje = document.getElementById('resultadoContacto');
    const boton = contacto.querySelector('button[type="submit"]');
    boton.disabled = true;

    try {
        const respuesta = await fetch('php/contacto.php', {
            method: 'POST',
            body: new FormData(contacto)
        });
        const datos = await respuesta.json();
        mensaje.textContent = datos.mensaje;
        if (respuesta.ok) {
            mensaje.className = 'alert alert-success';
            contacto.reset();
        } else {
            mensaje.className = 'alert alert-danger';
        }
    } catch (error) {
        mensaje.textContent = 'No se pudo comprobar el envio. Intentalo mas tarde.';
        mensaje.className = 'alert alert-danger';
    } finally {
        boton.disabled = false;
    }
});
