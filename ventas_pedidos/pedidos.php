<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

// Nombre del módulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

// Consulta para listar los pedidos con cliente y vendedor
$consulta = "
SELECT 
    p.idpedido,
    p.fechapedido,
    p.totalgs,
    p.estado,
    c.nombre AS cliente_nombre,
    c.apellido AS cliente_apellido
FROM pedidos p
LEFT JOIN cliente c ON p.idcliente = c.idcliente
ORDER BY p.fechapedido DESC";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// Función para obtener el estado del pedido
function obtenerEstadoPedido($estado) {
    switch ($estado) {
        case 3: return 'anulado'; // Asegúrate de que coincida con la clase CSS
        case 2: return 'completo';
        case 0: return 'pendiente';
        case 1: return 'autorizado';
        default: return 'desconocido'; // Clase para estados desconocidos
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once("../includes/head_gen.php"); ?>
    <style>
        .estado_anulado {
            background: #FF6F61;
            font-weight: bold;
            color: white;
        }

        .estado_completo {
            background: #D7FFAB;
            font-weight: bold;
        }

        .estado_pendiente {
            background: #FFC857;
            font-weight: bold;
        }

        .estado_autorizado {
            background: rgb(50, 175, 73);
            font-weight: bold;
            color: white;
        }

        .estado_desconocido {
            background: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php require_once("../includes/menu_gen.php"); ?>
            <?php require_once("../includes/menu_top_gen.php"); ?>

            <!-- page content -->
            <div class="right_col" role="main">

                <div class="clearfix"></div>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Lista de Pedidos</h2>
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                                </ul>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <p>
                                    <a href="pedidos_add.php" class="btn btn-sm btn-primary"><span class="fa fa-plus"></span> Nuevo Pedido</a>
                                    <a href="pedidos_genera_factura.php" class="btn btn-sm btn-primary"><span class="fa fa-file"></span> Generar Factura</a>                                  
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered jambo_table bulk_action">
                                        <thead>
                                            <tr>
                                                <th>ID Pedido</th>
                                                <th>Fecha Pedido</th>   
                                                <th>Cliente</th>
                                                <th>Total (Gs)</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while (!$rs->EOF) { 
                                                $estado = obtenerEstadoPedido($rs->fields['estado']); // Aquí obtenemos el estado correctamente
                                            ?>
                                                <tr class="estado_<?php echo $estado; ?>"> <!-- Aplicamos la clase correcta -->
                                                    <td><?php echo $rs->fields['idpedido']; ?></td>
                                                    <td><?php echo date("d/m/Y H:i", strtotime($rs->fields['fechapedido'])); ?></td>
                                                    <td><?php echo $rs->fields['cliente_nombre'] . ' ' . $rs->fields['cliente_apellido']; ?></td>
                                                    <td><?php echo number_format($rs->fields['totalgs'], 2, ',', '.'); ?></td>
                                                    <td><?php echo ucfirst($estado); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="pedidos_det.php?id=<?php echo $rs->fields['idpedido']; ?>" class="btn btn-sm btn-info" title="Ver Detalle"><i class="fa fa-eye"></i></a>
                                                            <a href="pedidos_edit.php?id=<?php echo $rs->fields['idpedido']; ?>" class="btn btn-sm btn-warning" title="Editar Pedido"><i class="fa fa-edit"></i></a>
                                                            <a href="pedidos_autorizacion.php?id=<?php echo $rs->fields['idpedido']; ?>" class="btn btn-sm btn-success" title="Autorizaciones"><i class="fa fa-check"></i></a>
                                                         </div>
                                                    </td>
                                                </tr>
                                            <?php $rs->MoveNext();
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php require_once("../includes/pie_gen.php"); ?>
        </div>
    </div>
    <?php require_once("../includes/footer_gen.php"); ?>
</body>
</html>
