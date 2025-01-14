 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "605";
require_once("includes/rsusuario.php");


//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
$idcaja_compartida = intval($rscaja->fields['idcaja_compartida']);
if ($idcaja_compartida > 0) {
    $idcaja = $idcaja_compartida;
}
if ($idcaja == 0) {
    $errores .= "- La caja debe estar abierta para poder realizar una venta.";
    $valido = "N";
}
if ($estadocaja == 3) {
    $errores .= "- La caja debe estar abierta para poder realizar una venta.";
    $valido = "N";
}


$consulta = "
select productos.descripcion as producto, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal
from ventas 
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where
ventas.idcaja = $idcaja
and ventas.estado <> 6
and ventas.finalizo_correcto = 'S'
group by productos.descripcion
order by sum(ventas_detalles.subtotal) desc
";
//echo $consulta;
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
                    <h2>Mix de Ventas de mi Caja</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

 

<strong></strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Producto</th>
            <th align="center">Cantidad</th>

        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>
            <td align="left"><?php echo antixss($rs->fields['producto']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['cantidad'], '4', 'N'); ?></td>

        </tr>
<?php
$cantidad_acum += $rs->fields['cantidad'];
    $total_acum += $rs->fields['subtotal'];
    $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
      <tfoot>
        <tr>
            <td align="left">Totales</td>
            <td align="center"><?php echo formatomoneda($cantidad_acum, '4', 'N'); ?></td>

        </tr>
      </tfoot>
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
