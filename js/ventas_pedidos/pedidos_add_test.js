document.addEventListener('DOMContentLoaded', () => {
    const botonAgregarProducto = document.getElementById('agregarProductoBtn');
    const tablaProductos = document.getElementById('productos');
    const botonFinalizar = document.getElementById('finalizar'); // Botón para finalizar el pedido

    // Función para agregar un nuevo producto (nueva fila)
    botonAgregarProducto.addEventListener('click', () => {
        const selectProducto = document.getElementById('producto'); // Obtener el select de productos
        const productoSeleccionado = selectProducto.value;
        const descripcionSeleccionada = selectProducto.options[selectProducto.selectedIndex].text;

        if (!productoSeleccionado) {
            alert("Por favor, selecciona un producto.");
            return;
        }

        const nuevaFila = document.createElement('tr');
        const celdas = [
            { campo: 'idprod', valor: productoSeleccionado },
            { campo: 'descripcion', valor: descripcionSeleccionada },
            { campo: 'caja', valor: '' },
            { campo: 'unidad', valor: '' },
            { campo: 'uxc', valor: '' },
            { campo: 'cant_uni', valor: '' },
            { campo: 'precio_lista', valor: '' },
            { campo: 'precio_unitario', valor: '' },
            { campo: 'desc', valor: '' },
            { campo: 'iva_porce', valor: '' },
            { campo: 'iva', valor: '' },
            { campo: 'total_iva', valor: '' },
            { campo: 'cant_max', valor: '' }
        ];

        celdas.forEach((celdaInfo) => {
            const nuevaCelda = document.createElement('td');
            const nuevoInput = document.createElement('input');
            nuevoInput.type = 'text';
            nuevoInput.name = celdaInfo.campo;
            nuevoInput.className = 'form-control';
            nuevoInput.value = celdaInfo.valor;
            nuevaCelda.appendChild(nuevoInput);
            nuevaFila.appendChild(nuevaCelda);
        });

        tablaProductos.appendChild(nuevaFila);
    });

    // Función para finalizar el pedido y enviar todos los datos al servidor
    botonFinalizar.addEventListener('click', () => {
        console.log("llega boton finalizar");
        const datosPedido = {};
        const formRegistro = document.getElementById('formRegistro');

        // Capturar todos los campos del formulario principal
        const formData = new FormData(formRegistro);
        formData.forEach((value, key) => {
            datosPedido[key] = value;
        });
        console.log(JSON.stringify(datosPedido));

        // Capturar los datos de la tabla
        const filas = tablaProductos.getElementsByTagName('tr');
        const productos = [];

        Array.from(filas).forEach(fila => {
            const celdas = fila.getElementsByTagName('td');
            const filaDatos = {};

            Array.from(celdas).forEach(celda => {
                const input = celda.getElementsByTagName('input')[0];
                if (input) {
                    filaDatos[input.name] = input.value;
                }
            });

            productos.push(filaDatos);
        });

        console.log("filas "+filas);
        console.log("productos ");
        console.log(productos);
        datosPedido.productos = productos;

        // Enviar los datos al servidor
        fetch('procesar_pedido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosPedido),
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Pedido registrado exitosamente.');
                    window.location.href = 'pedidos.php'; // Redirigir después del éxito
                } else {
                    alert('Error al registrar el pedido: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un problema al procesar el pedido.');
            });
    });
});