# VEXORA

Tecnologías: bootstrap, html, css, javascript, php
Vamos a crear una tienda online que consta de 3 páginas.
1- Inicio/Bienvenida -> Página estética de bienvenida
2- Tienda
3- Formulario de contacto -> Formulario con el que puedas ponerte en contacto con la tienda

La tienda debe tener un botón de login, que permitirá iniciar sesión (o crear usuario si no existe). Una vez logueado, ese botón se sustituye por la imagen de usuario de la persona (si lo cambiais por una foto vuestra editable, perfecto). Esto es importante porque, a la par, una vez que un usuario compra un producto, debe marcarse de alguna forma (visualmente hablando).

Hay 2 tipos de usuario: administrador y comprador. El administrador puede modificar la tienda completa (añadir/eliminar productos, controlar el stock...) y debe aceptar las compras de cada usuario comprador.

El usuario comprador puede comprar, consultar su historial de compras y modificar su perfil

Debemos tener en cuenta que una vez que el comprador pulsa en añadir producto, debemos reducir el stock y controlarlo; si cancela una compra (o línea de producto) o lo hace el administrador, deben volver a reponerse esas unidades al stock.

No se puede comprar sin stock

NOTAS IMPORTANTES: 
Ojo con el control de stock
Ojo con el registro de usuarios y con lo que puede ver cada uno de ellos
Ojo con los carritos y su visualización

## Ejecutar el proyecto


### 1. Abrir la carpeta descargada

Descarga el repositorio y descomprime el ZIP.

El repositorio ya incluye `.env` con la configuracion de MongoDB Atlas.

### 2. Iniciar la wb

Ejecuta desde esa carpeta

```bash
docker compose up -d --build
```

Abre la web en el navegador entrando en [http://localhost:8080](http://localhost:8080)

### Si aparece un error

- Comprueba que Docker esta arrancado y que tu usuario tiene permiso para utilizarlo
- Si falta `.env`, comprueba los archivos ocultos de la carpeta descargada
- Si el puerto `8080` esta ocupado, cierra la otra aplicacion que lo este utilizando
- Para consultar el estado del contenedor y sus mensajes, ejecuta estos comandos