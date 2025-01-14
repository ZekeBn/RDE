<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");

// Module name this file belongs to
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");
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
                        <h3>Detalle del Pedido</h3>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel"> 
                            <div class="x_content">
                                <?php if (trim($errores) != "") { ?>
                                    <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                        <strong>Errors:</strong><br /><?php echo $errores; ?>
                                    </div>
                                <?php } ?>
                                <!-- Filtros de búsqueda para los pedidos-->
                                <div class="row">
                                    <div class="col-md-12 col-sm-6 form-group">
                                    </div>
                                    
                                </div><br>
                                <!-- Tabla de pedidos-->
                                <div class="table-responsive">
                                    <table class="table table-striped jambo_table bulk_action">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Estado</th>
                                                <th>Tipo</th>
                                                <th>Serie</th>
                                                <th>Nro</th>
                                                <th>Fec. Pedido</th>
                                                <th>Cliente</th>
                                                <th>Vendedor</th>
                                                <th>Plazo</th>
                                                <th>Lista Precio</th>
                                                <th>Monto Total</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr>
                                                <td><div class="btn-group">
                                                  <a  id="openModal" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                                                        
                                                    <div id="pAutorizacion" class="modal">
                                                        <div class="modal-content">
                                                            <span class="close">&times;</span>
                                                            <h2>Autorización</h2>
                                                            <p>
                                                                Seleccione el pedido a autorizar.
                                                            </p>
                                                        </div>
                                                    </div>
                                                
                                                </div></td>
                                                <td><?php echo antixss($rs_prod->fields['estado']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['idTipo']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['serie']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['nro']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['fecPedido']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['cliente']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['vendedor']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['plazo']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['listaPrecio']); ?></td>
                                                <td><?php echo antixss($rs_prod->fields['montoTotal']); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <!-- End panel content -->  
                        </div>                    
                    </div>
                </div>

            </div>    
        </div>
    </div>

    <?php require_once("../includes/pie_gen.php"); ?>
    </div>
    <?php require_once("../includes/footer_gen.php"); ?>
    <!-- Scripts -->
    <script src="../js/ventas_pedidos/pedidos_autorizaciones.js"></script>    
</body>
</html>