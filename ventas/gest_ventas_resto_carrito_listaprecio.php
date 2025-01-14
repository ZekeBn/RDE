<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");



$consulta = "
select * 
from lista_precios_venta 
inner join lista_precios_venta_perm on lista_precios_venta_perm.idlistaprecio = lista_precios_venta.idlistaprecio
where 
lista_precios_venta_perm.idusuario = $idusu
and lista_precios_venta_perm.estado = 1 
and lista_precios_venta.estado = 1 
and lista_precios_venta.idlistaprecio > 1
order by lista_precios_venta.lista_precio asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$lista_precio = $rs->fields['lista_precio'];
$idlistaprecio = $rs->fields['idlistaprecio'];






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
                    <h2>Aplicar Lista de Precio</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php if ($idlistaprecio > 0) {?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idlistaprecio</th>
			<th align="center">Lista precio</th>
			<th align="center">Recargo porc</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_ventas_resto_carrito_listaprecio_aplica.php?id=<?php echo $rs->fields['idlistaprecio']; ?>" class="btn btn-sm btn-default" title="Aplicar Lista" data-toggle="tooltip" data-placement="right"  data-original-title="Aplicar Lista"><span class="fa fa-check"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idlistaprecio']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['lista_precio']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['recargo_porc']);  ?>%</td>

		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php } else { ?>
Tu usuario no tiene canales de venta asignados.

<?php }?>
<br /><br /><br /><br /><br />

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
