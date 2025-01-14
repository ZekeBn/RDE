<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "180";
require_once("../includes/rsusuario.php");


// rellenar retroactivo
$consulta = "
insert into sucursal_cliente
(idcliente, sucursal, direccion, telefono, mail, estado, registrado_por, registrado_el, `borrado_por`, `borrado_el` )
SELECT 
idcliente, 'CASA MATRIZ', cliente.direccion, NULL, NULL, cliente.estado, 1, '$ahora', NULL, NULL 
FROM `cliente` 
where 
idcliente not in (select sucursal_cliente.idcliente from sucursal_cliente)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from cliente 
where 
idcliente = $idcliente
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}


$consulta = "
select *,
(select usuario from usuarios where sucursal_cliente.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where sucursal_cliente.borrado_por = usuarios.idusu) as borrado_por
from sucursal_cliente 
where 
 estado = 1 
 and idcliente = $idcliente
order by idsucursal_clie asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once("../includes/head_gen.php"); ?>
    <script>
        function cliente_tipo(tipo){
            // persona fisica
            if(tipo == 1){
                $("#nombre_box").show();
                $("#apellido_box").show();
                $("#fantasia_box").hide();
            // persona juridica
            }else{
                $("#nombre_box").hide();
                $("#apellido_box").hide();
                $("#fantasia_box").show();
            }
        }
    </script>
</head>

<body class="nav-md" onLoad="cliente_tipo('<?php echo $rs->fields['tipocliente'] ?>');">
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
                    <?php require_once("../includes/lic_gen.php");?>
                    
                    <!-- SECCION -->
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Sucursales del Cliente</h2>
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                                    </ul>
                                    <div class="clearfix"></div>
                                </div>
								<p>
                                        <a href="cliente.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                                        <a href="sucursal_cliente_add.php?id=<?php echo $idcliente; ?>" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
                                    </p>
                                <div class="x_content">
                                    <?php
                                    if ($rs->EOF) {
                                        echo '<div class="alert alert-success" role="alert">
										<h4 class="alert-heading">Sucursal inexistente!!</h4>
										<p>La sucursal seleccionada se encuentra eliminada, si desea cargar una nueva oprima Agregar</p>
									  </div>';
                                    } else {
                                        ?>
                                    <div class="table-responsive">
                                        <table width="100%" class="table table-bordered jambo_table bulk_action">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th align="center">Idsucursal clie</th>
                                                    <th align="center">Idcliente</th>
                                                    <th align="center">Sucursal</th>
                                                    <th align="center">Direccion</th>
                                                    <th align="center">Telefono</th>
                                                    <th align="center">Mail</th>
                                                    <th align="center">Estado</th>
                                                    <th align="center">Registrado por</th>
                                                    <th align="center">Registrado el</th>
                                                    <th align="center">Borrado por</th>
                                                    <th align="center">Borrado el</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    while (!$rs->EOF) {
                                                        ?>
                                                <tr>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="sucursal_cliente_edit.php?id=<?php echo $rs->fields['idsucursal_clie']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                                                            <a href="sucursal_cliente_del.php?id=<?php echo $rs->fields['idsucursal_clie']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                                                        </div>
                                                    </td>
                                                    <td align="center"><?php echo intval($rs->fields['idsucursal_clie']); ?></td>
                                                    <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
                                                    <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
                                                    <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
                                                    <td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
                                                    <td align="center"><?php echo antixss($rs->fields['mail']); ?></td>
                                                    <td align="center"><?php echo intval($rs->fields['estado']); ?></td>
                                                    <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
                                                    <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                                                        echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
                                                    }  ?></td>
                                                    <td align="center"><?php echo antixss($rs->fields['borrado_por']); ?></td>
                                                    <td align="center"><?php if ($rs->fields['borrado_el'] != "") {
                                                        echo date("d/m/Y H:i:s", strtotime($rs->fields['borrado_el']));
                                                    }  ?></td>
                                                </tr>
                                                <?php
                                                    $rs->MoveNext();
                                                    }
                                        //$rs->MoveFirst();
                                        ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <br />
                                    <?php
                                    }
?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- SECCION --> 
                </div>
            </div>
            <!-- /page content -->
    
            <!-- footer content -->
            <?php require_once("../includes/pie_gen.php"); ?>
            <!-- /footer content -->
        </div>
    </div>
    <?php require_once("../includes/footer_gen.php"); ?>
</body>
</html>