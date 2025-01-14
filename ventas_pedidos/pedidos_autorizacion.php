<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

// Module name this file belongs to
$modulo = "1";
$submodulo = "2";   
$dirsup = "S";
require_once("../includes/rsusuario.php");

$sucursal = $_POST['sucursal'] ?? '';
$fechapedido = $_POST['fechapedido'] ?? '';
$numpedido = $_POST['numpedido'] ?? '';
$cliente = $_POST['cliente'] ?? '';

$consulta = "SELECT idpedido, fechapedido, idempresa, idcliente, registrado_por, estado, procesado, totalgs, anulado_el, anulado_por 
             FROM pedidos
             WHERE 1=1";

if (!empty($sucursal)) {
    $consulta .= " AND idsucu = " . $conexion->qstr($sucursal);
}
if (!empty($fechapedido)) {
    $consulta .= " AND fechapedido = " . $conexion->qstr($fechapedido);
}
if (!empty($numpedido)) {
    $consulta .= " AND idpedido = " . $conexion->qstr($numpedido);
}
if (!empty($cliente)) {
    $consulta .= " AND idcliente = " . $conexion->qstr($cliente);
}

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once("../includes/head_gen.php"); ?>
    <link rel="stylesheet" href="css/ventas_pedidos/pedidos_autorizacion.css">
</head>
<body class="nav-md">    
    <div class="container body">
        <div class="main_container">
            <?php require_once("../includes/menu_gen.php"); ?>
            <?php require_once("../includes/menu_top_gen.php"); ?>
            <div class="right_col" role="main">
                <div class="page-title">
                    <div class="title_left">
                        <h3>Autorización de Pedidos</h3>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel"> 
                            <div class="x_content">
                                <!-- Filtros de búsqueda -->
                                <div class="row">
                                    <form id="formBusqueda">
                                        <div class="col-md-12 col-sm-6 form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal</label>
                                            <div class="col-md-5 col-sm-5 col-xs-9">
                                                <select name="sucursal" id="sucursal" class="form-control">
                                                    <option value="">Seleccione una Sucursal</option>
                                                    <?php
                                                    $consulta = "SELECT idsucu, nombre FROM sucursales;";
                                                    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                                                    while (!$rs->EOF) {
                                                        $row = $rs->fields;
                                                        echo "<option value=\"{$row['idsucu']}\">{$row['nombre']}</option>";
                                                        $rs->MoveNext();
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-sm-6 form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha</label>
                                            <div class="col-md-5 col-sm-5 col-xs-9">
                                                <input type="date" name="fechapedido" id="fechapedido" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-sm-6 form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Número</label>
                                            <div class="col-md-5 col-sm-5 col-xs-9">
                                                <input type="text" name="numpedido" id="numpedido" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-sm-6 form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente</label>
                                            <div class="col-md-5 col-sm-5 col-xs-9">
                                                <select name="cliente" id="cliente" class="form-control">
                                                    <option value="">Seleccione un Cliente</option>
                                                    <?php
                                                    $consulta = "SELECT idcliente, CONCAT(nombre, ' ', COALESCE(apellido, '')) as nom FROM cliente;";
                                                    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                                                    while (!$rs->EOF) {
                                                        $row = $rs->fields;
                                                        echo "<option value=\"{$row['idcliente']}\">{$row['nom']}</option>";
                                                        $rs->MoveNext();
                                                    }
                                                    ?>
                                                </select> 
                                            </div>
                                        </div>
                                        <div class="form-group text-center">
                                            <button type="button" id="buscar" class="btn btn-default"><span class="fa fa-search"></span> Buscar</button>
                                        </div>
                                    </form>
                                </div><br>

                                <!-- Tabla de pedidos -->
                                <div class="table-responsive">
                                    <table class="table table-striped jambo_table bulk_action" id="tablaPedidos">
                                        <thead>
                                            <tr>
                                                <th>Estado</th>
                                                <th>Tipo</th>
                                                <th>Serie</th>
                                                <th>Nro</th>
                                                <th>Fecha Pedido</th>
                                                <th>Cliente</th>
                                                <th>Monto Total</th>
                                                <th></th>
                                                <th>Autorización Comercial</th>
                                                <th></th>
                                                <th></th>
                                                <th>Autorización Cta Cte</th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while (!$rs->EOF) { ?>
                                                <tr class="estado_<?php echo $rs->fields['estado']; ?>">
                                                    <td><?php echo $rs->fields['idpedido']; ?></td>
                                                    <td><?php echo $rs->fields['estado']; ?></td>
                                                    <td><?php echo $rs->fields['fechapedido']; ?></td>
                                                    <td><?php echo $rs->fields['idcliente']; ?></td>
                                                    <td><?php echo $rs->fields['totalgs']; ?></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td><input type="checkbox"></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td><input type="checkbox"></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>
                                                        <a href="pedidos_det.php?idpedido=<?php echo $rs->fields['idpedido']; ?>" class="btn btn-sm btn-success" title="Ver Detalle">
                                                            <i class="fa fa-search"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php $rs->MoveNext(); } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <!-- End panel content -->
                        </div>                    
                    </div>
                </div>
                <div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;justify-content:center;">
                    <button id="cancelar" type="button" class="btn btn-primary" onclick="window.location='pedidos.php'"><span class="fa fa-ban"></span> Cancelar</button>
                </div>
            </div>    
        </div>
    </div>

    <?php require_once("../includes/pie_gen.php"); ?>
    </div>
    <?php require_once("../includes/footer_gen.php"); ?>
    <!-- Scripts -->
    <script>
document.addEventListener("DOMContentLoaded", function () {
    const buscarButton = document.getElementById('buscar');
    const tablaPedidos = document.getElementById('tablaPedidos').querySelector('tbody');


    function asignarEventosCheckbox() {
        const checkbox1 = document.querySelectorAll('.checkbox-autcomercial');
        const checkbox2 = document.querySelectorAll('.checkbox-autoctacte');

        checkbox1.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const row = this.closest('tr');
                const fechaInput = row.querySelector('.fecha-input-1');
                const usuarioInput = row.querySelector('.usuario-input-1');
                const idPedido = this.dataset.idpedido;

                if (this.checked) {
                    // Generar la fecha/hora actual
                    const fechaActual = new Date().toLocaleString();
                    fechaInput.value = fechaActual;

                    // Guardar los datos automáticamente
                    guardarDatos(idPedido, usuarioInput.value, fechaActual);
                } else {
                    // Vaciar los campos y limpiar el registro en la base de datos
                    fechaInput.value = "";
                    guardarDatos(idPedido, usuarioInput.value, "");
                }
            });
        });

        checkbox2.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const row = this.closest('tr');
                const fechaInput = row.querySelector('.fecha-input-2');
                const usuarioInput = row.querySelector('.usuario-input-2');
                const idPedido = this.dataset.idpedido;

                if (this.checked) {
                    // Generar la fecha/hora actual
                    const fechaActual = new Date().toLocaleString();
                    fechaInput.value = fechaActual;

                    // Guardar los datos automáticamente
                    guardarDatos(idPedido, usuarioInput.value, fechaActual);
                } else {
                    // Vaciar los campos y limpiar el registro en la base de datos
                    fechaInput.value = "";
                    guardarDatos(idPedido, usuarioInput.value, "");
                }
            });
        });
    }

    function guardarDatos(idPedido, usuario1, fecha1, usuario2, fecha2) {
        const datos = { idPedido, usuario1, fecha1, usuario2, fecha2 };

        fetch('guardar_autorizacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Datos guardados exitosamente');
            } else {
                console.error('Error al guardar los datos:', data.message);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
        });
    }
});

    </script>

<script>
    const usuarioActual = "<?php echo htmlspecialchars($idusu, ENT_QUOTES, 'UTF-8'); ?>";
</script>

</body>
</html>