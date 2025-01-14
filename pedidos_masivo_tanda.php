 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "21";
$submodulo = "414";
require_once("includes/rsusuario.php");



$consulta = "
select *,
(select usuario from usuarios where pedidos_masivo_tanda.registrado_por = usuarios.idusu) as registrado_por,
(select count(*) from pedidos_masivo_cab where estado = 1 and idtandamas = pedidos_masivo_tanda.idtandamas) as total_pedidos,
(select count(*) from pedidos_masivo_cab inner join pedidos_masivo_det on pedidos_masivo_det.idpedidomas =pedidos_masivo_cab.idpedidomas  where estado = 1 and idtandamas = pedidos_masivo_tanda.idtandamas) as total_productos
from pedidos_masivo_tanda 
where 
 estado = 1 
order by idtandamas desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Carga Masiva de Pedidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="pedidos_masivo_tanda_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idtandamas</th>
            <th align="center">Total Pedidos</th>
            <th align="center">Total Productos</th>
            <th align="center">Total Clientes</th>
            <th align="center">Archivo</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>


        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {

    $idtandamas = $rs->fields['idtandamas'];
    $consulta = "
select count(idcliente) as total_clientes
from (
        select count(idcliente), idcliente 
        from pedidos_masivo_cab
        where 
        estado = 1 
        and idtandamas = $idtandamas 
        group by idcliente
     ) tt 
";
    $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="pedidos_masivo_tanda_det.php?id=<?php echo $rs->fields['idtandamas']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <a href="pedidos_masivo_tanda_del.php?id=<?php echo $rs->fields['idtandamas']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="center"><?php echo intval($rs->fields['idtandamas']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total_pedidos']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total_productos']); ?></td>
            <td align="center"><?php echo formatomoneda($rsc->fields['total_clientes']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['archivo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
            <td align="center"><?php if ($rs->fields['registrado_el'] != "") {
                echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
            }  ?></td>


        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
