document.addEventListener("DOMContentLoaded", function () {
    const tablaPedidos = document.querySelector("#tablaPedidos");

    // Función para cargar los pedidos
    function cargarPedidos(filtros = {}) {
        tablaPedidos.innerHTML = <tr><td colspan="7" class="text-center">Cargando...</td></tr>;

        fetch("pedidos_carga.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(filtros),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.length === 0) {
                    tablaPedidos.innerHTML = <tr><td colspan="7" class="text-center">No se encontraron pedidos.</td></tr>;
                    return;
                }
                tablaPedidos.innerHTML = data
                    .map(
                        (pedido) => 
                    <tr>
                        <td>
                            <button class="btn btn-sm btn-success btnAutorizar" data-id="${pedido.id}" data-estado="X">Autorizar</button>
                        </td>
                        <td>${pedido.id}</td>
                        <td>${pedido.estado}</td>
                        <td>${pedido.fecha}</td>
                        <td>${pedido.cliente}</td>
                        <td>${pedido.vendedor}</td>
                        <td>${pedido.total}</td>
                    </tr>
                
                    )
                    .join("");
            });
    }

    // Delegación de eventos para autorizar pedidos
    tablaPedidos.addEventListener("click", function (e) {
        if (e.target.classList.contains("btnAutorizar")) {
            const idPedido = e.target.dataset.id;
            const nuevoEstado = e.target.dataset.estado;

            fetch("actualizar_estado_pedido.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ idPedido, nuevoEstado }),
            })
                .then((response) => response.json())
                .then((data) => {
                    alert(data.message);
                    if (data.status === "success") {
                        cargarPedidos();
                    }
                });
        }
    });

    // Cargar pedidos al inicio
    cargarPedidos();
});