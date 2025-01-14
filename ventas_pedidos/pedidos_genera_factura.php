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
                        <h3>Generación de Facturas a partir de Pedidos</h3>
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
                                    <div class="row">
                                            <div class="col-md-12 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente</label>
                                                <div class="col-md-2 col-sm-5 col-xs-9">
                                                    <input type="text" name="idcliente" id="idcliente" class="form-control"
                                                        value="<?php echo $_REQUEST['idcliente']; ?>" />
                                                </div>
                                                <div class="col-md-4 col-sm-5 col-xs-9">
                                                    <select name="cliente" id="cliente" class="form-control">
                                                        <option value="">Seleccione un Cliente</option>
                                                        <?php
                                                        $consulta = "SELECT idcliente, CONCAT(nombre, ' ', COALESCE(apellido, '')) as nom FROM cliente;";
                                                        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                                                        while (!$rs->EOF) {
                                                            $row = $rs->fields;
                                                            $selected = (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] == $row['idcliente']) ? 'selected' : '';
                                                            echo "<option value=\"{$row['idcliente']}\" $selected>{$row['nom']}</option>";
                                                            $rs->MoveNext();
                                                        }
                                                        ?>
                                                    </select> 
                                                </div>
                                            </div>
                                        </div>  
                                        <div class="row">
                                            <div class="col-md-12 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor</label>
                                                <div class = "row">
                                                    <div class="col-md-2">
                                                        <input type="input" name="fechapedido" id="fechapedido" class="form-control"
                                                            value="<?php echo $_REQUEST['fechapedido']; ?>" /> 
                                                    </div>
                                                    <div class="col-md-4 ">
                                                        <input type="input" name="fechapedido" id="fechapedido" class="form-control"
                                                            value="<?php echo $_REQUEST['fechapedido']; ?>" /> 
                                                    </div>
                                                </div>
                                               
                                            </div>
                                        </div>  
                                        <div class="row">
                                            <div class="col-md-12 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Zona</label>
                                                <div class="col-md-6 col-sm-5 col-xs-9">
                                                    <input type="text" name="numpedido" id="numpedido" class="form-control"
                                                        value="<?php echo $_REQUEST['numpedido']; ?>" />
                                                </div>
                                            </div>
                                        </div> 
                                        <div class ="row">
                                            <div class="col-md-12 col-sm-6 form-group">
                                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha</label>
                                                <div class="col-md-6 col-sm-5 col-xs-9">
                                                    <input type="date" name="numpedido" id="numpedido" class="form-control"
                                                        value="<?php echo $_REQUEST['numpedido']; ?>" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div align="center">
                                            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                                <button type="submit" class="btn "><span class="fa fa-search"></span> Buscar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div><br>
                                <!-- Tabla de pedidos-->
                                <div class="table-responsive">
                                    <table class="table table-striped jambo_table bulk_action">
                                        <thead>
                                            <tr>
                                                <?php if (!empty($pedidos)): ?>
                                                    <!-- Generar encabezados dinámicamente -->
                                                    <?php foreach (array_keys($pedidos[0]) as $header): ?>
                                                        <th><?= htmlspecialchars($header) ?></th>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <th>No hay pedidos autorizados</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($pedidos)): ?>
                                                <?php foreach ($pedidos as $pedido): ?>
                                                    <tr>
                                                        <?php foreach ($pedido as $value): ?>
                                                            <td><?= htmlspecialchars($value) ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;justify-content:center;">
                                    <button id="cancelar" type="button" class="btn btn-primary" onclick="window.location='pedidos.php'"><span class="fa fa-ban"></span> Cancelar</but>
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