<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

$dirsup = "S";
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once("../includes/head_gen.php"); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="nav-md">
<div class="container body">
    <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
        <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
            <div class="">
                <div class="page-title">
                </div>
                <div class="clearfix"></div>
                <?php require_once("../includes/lic_gen.php"); ?>

                <!-- SECCION -->
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Datos Plantilla</h2>
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                    </li>
                                </ul>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">

                                <!-- Botón para abrir el modal de búsqueda de clientes -->
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                        data-target="#modalBuscarCliente">
                                    Buscar Cliente
                                </button>

                                <!-- Modal de búsqueda de clientes -->
                                <div class="modal fade" id="modalBuscarCliente" tabindex="-1" role="dialog"
                                     aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Buscar Cliente</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="buscarClienteInput">Buscar por Código o Nombre:</label>
                                                    <input type="text" class="form-control" id="buscarClienteInput"
                                                           placeholder="Ingrese código o nombre">
                                                </div>
                                                <button type="button" id="btnBuscarClienteModal"
                                                        class="btn btn-primary mb-3">Buscar
                                                </button>
                                                <table class="table">
                                                    <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Email</th>
                                                        <th>Teléfono</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="resultadoBusqueda">
                                                    <!-- Aquí se mostrarán los resultados de la búsqueda -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cerrar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- JavaScript -->
                                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
                                <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
                                <script>
                                    $(document).ready(function () {
                                        // Función para realizar la búsqueda de clientes
                                        function buscarCliente() {
                                            var valorBusqueda = $("#buscarClienteInput").val();
                                            if (valorBusqueda != "") {
                                                var direccionurl = 'buscar_cliente.php';
                                                var parametros = {
                                                    "bus": valorBusqueda
                                                };
                                                // Realizar la consulta a la base de datos usando AJAX
                                                $.ajax({
                                                    data: parametros,
                                                    url: direccionurl,
                                                    type: 'post',
                                                    cache: false,
                                                    timeout: 3000,
                                                    crossDomain: true,
                                                    success: function(response) {
                                                        // Limpiar la tabla antes de agregar los nuevos resultados
                                                        $("#resultadoBusqueda").empty();
                                                        // Verificar si se encontraron resultados
                                                        if (response.length > 0) {
                                                        // Construir filas de la tabla con los resultados
                                                        $.each(response, function(index, cliente) {
                                                            var fila = "<tr>" +
                                                                "<td>" + cliente.idcliente + "</td>" +
                                                                "<td>" + cliente.nombre + "</td>" +
                                                                "<td>" + cliente.email + "</td>" +
                                                                "<td>" + cliente.telefono + "</td>" +
                                                            "</tr>";
                                                        $("#resultadoBusqueda").append(fila);
                                                        });
                                                        // Obtener el primer cliente de la respuesta (suponiendo que solo hay uno)
                                                            var primerCliente = response[0];
                                                            // Asignar valores a los campos de texto
                                                            $('#codigoTextbox').val(primerCliente.idcliente);
                                                            $('#nombreTextbox').val(primerCliente.nombre);
                                                            $('#emailTextbox').val(primerCliente.email);
                                                            $('#telefonoTextbox').val(primerCliente.telefono);
                                                            $('#rucTextbox').val(primerCliente.ruc);
                                                            $('#direccionTextbox').val(primerCliente.direccion);
                                                        } else {
                                                            // Mostrar un mensaje si no se encontraron resultados
                                                            var mensaje = "<tr><td colspan='4'>No se encontraron resultados</td></tr>";
                                                            $("#resultadoBusqueda").append(mensaje);
                                                        }
                                                    },
                                                    error: function (jqXHR, textStatus, errorThrown) {
                                                        if (jqXHR.status == 404) {
                                                            alert('Página no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                                                        } else if (jqXHR.status == 0) {
                                                            alert('Se ha rechazado la conexión.');
                                                        } else {
                                                            alert(jqXHR.status + ' ' + errorThrown);
                                                        }
                                                    }
                                                }).fail(function (jqXHR, textStatus, errorThrown) {
                                                    if (jqXHR.status === 0) {
                                                        alert('No conectado: verifique la red.');
                                                    } else if (jqXHR.status == 404) {
                                                        alert('Página no encontrada [404]');
                                                    } else if (jqXHR.status == 500) {
                                                        alert('Error interno del servidor [500].');
                                                    } else if (textStatus === 'parsererror') {
                                                        alert('Error al analizar JSON solicitado.');
                                                    } else if (textStatus === 'timeout') {
                                                        alert('Tiempo de espera agotado, error de tiempo de espera.');
                                                    } else if (textStatus === 'abort') {
                                                        alert('Solicitud ajax abortada.');
                                                    } else {
                                                        alert('Error no capturado: ' + jqXHR.responseText);
                                                    }
                                                });
                                            }
                                        }

                                        // Evento de tecla presionada en el campo de búsqueda
                                        $('#buscarClienteInput').on('keyup', function (event) {
                                            // Verificar si se presionó la tecla "Enter" (código 13)
                                            if (event.which === 13) {
                                                // Ejecutar la búsqueda al presionar "Enter"
                                                buscarCliente();
                                                event.preventDefault(); // Evitar que el formulario se envíe
                                            }
                                        });

                                        // Evento de clic en el botón de búsqueda en el modal
                                        $('#btnBuscarClienteModal').on('click', function (event) {
                                            // Ejecutar la búsqueda al hacer clic en el botón
                                            buscarCliente();
                                            event.preventDefault(); // Evitar que el formulario se envíe
                                        });

                                        // Evento de doble clic en una fila de la tabla de resultados
                                        $('#resultadoBusqueda').on('dblclick', 'tr', function () {
                                            var idcliente = $(this).find('td:eq(0)').text();
                                            var nombre = $(this).find('td:eq(1)').text();
                                            var email = $(this).find('td:eq(2)').text();
                                            var telefono = $(this).find('td:eq(3)').text();

                                            // Obtener el valor de ruc del objeto cliente
                                            var ruc = $(this).find('td:eq(4)').text();
                                            var direccion = $(this).find('td:eq(5)').text();

                                            console.log("RUC del cliente:", ruc);
                                            console.log("Dirección del cliente:", direccion);

                                            // Asignar valores a los textbox
                                            $('#codigoTextbox').val(idcliente);
                                            $('#nombreTextbox').val(nombre);
                                            $('#emailTextbox').val(email);
                                            $('#telefonoTextbox').val(telefono);
                                            $('#rucTextbox').val(ruc);
                                            $('#direccionTextbox').val(direccion);

                                            // Forzar el cierre completo del modal
                                            $('#modalBuscarCliente').modal('hide');
                                            $('body').removeClass('modal-open');
                                            $('.modal-backdrop').remove();
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SECCION -->

                <!-- Textbox para mostrar los valores seleccionados -->
                <div class="row">
                    <div class="col-md-6">
                        <label for="codigoTextbox">Código:</label>
                        <input type="text" class="form-control" id="codigoTextbox" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="nombreTextbox">Nombre:</label>
                        <input type="text" class="form-control" id="nombreTextbox" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="emailTextbox">Email:</label>
                        <input type="text" class="form-control" id="emailTextbox" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="telefonoTextbox">Teléfono:</label>
                        <input type="text" class="form-control" id="telefonoTextbox" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="rucTextbox">RUC:</label>
                        <input type="text" class="form-control" id="rucTextbox" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="direccionTextbox">Dirección:</label>
                        <input type="text" class="form-control" id="direccionTextbox" readonly>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page content -->

        <!-- POPUP DE MODAL OCULTO -->
        <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true"
             id="modal_ventana">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                        </button>
                        <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                    </div>
                    <div class="modal-body" id="modal_cuerpo">
                        Contenido...
                    </div>
                    <div class="modal-footer" id="modal_pie">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    </div>

                </div>
            </div>
        </div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
        <?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
    </div>
</div>
<?php require_once("../includes/footer_gen.php"); ?>
</body>
</html>
