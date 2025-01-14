 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "322";
require_once("includes/rsusuario.php");




$consulta = "
select *,
(select usuario from usuarios where lista_precios_venta.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where lista_precios_venta.borrado_por = usuarios.idusu) as borrado_por
from lista_precios_venta 
where 
 estado = 1 
order by idlistaprecio asc
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
                    <h2>Listas de Precios</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p><a href="lista_precios_venta_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
    <a href="lista_precios_venta_import.php" class="btn btn-sm btn-default"><span class="fa fa-upload"></span> Carga Masiva</a>
                      
                      
</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th></th>
            <th align="center">Idlistaprecio</th>
            <th align="center">Lista precio</th>
            <th align="center">%Recargo Automatico</th>
            <th align="center">Redondeo Ceros</th>
            <th align="center">Redondeo Direccion</th>
            <th align="center">Registrado por</th>
            <th align="center">Registrado el</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td>
                <?php if ($rs->fields['borrable'] == 'S') { ?>
                <div class="btn-group">
                    <a href="lista_precios_venta_det.php?id=<?php echo $rs->fields['idlistaprecio']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <a href="lista_precios_venta_edit.php?id=<?php echo $rs->fields['idlistaprecio']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="lista_precios_venta_del.php?id=<?php echo $rs->fields['idlistaprecio']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>
                <?php } ?>
            </td>
            <td align="center"><?php echo intval($rs->fields['idlistaprecio']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['lista_precio']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['recargo_porc'], 2, 'N'); ?>%</td>
            <td align="center"><?php echo formatomoneda($rs->fields['redondeo_ceros'], 2, 'N'); ?></td>
            <td align="center"><?php
            if ($rs->fields['redondeo_direccion'] == 'A') {
                echo "Hacia Arriba";
            } elseif ($rs->fields['redondeo_direccion'] == 'B') {
                echo "Hacia Abajo";
            } else {
                echo "Normal (0,5 arriba)";
            }

    ?></td>
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
