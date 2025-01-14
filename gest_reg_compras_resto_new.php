<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");


$consulta = "
select *,
(select usuario from usuarios where tmpcompras.registrado_por = usuarios.idusu) as registrado_por,
(select nombre from sucursales where idsucu = tmpcompras.sucursal) as sucursal,
(select nombre from proveedores where proveedores.idproveedor = tmpcompras.proveedor) as proveedor,
(select tipocompra from tipocompra where idtipocompra = tmpcompras.tipocompra) as tipocompra
from tmpcompras 
where 
 estado = 1 
order by idtran asc
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
                    <h2>Registro de Compras</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p><a href="tmpcompras_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<strong>Cargas de compra pendientes de finalizacion:</strong><br />
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idtran</th>
			<th align="center">Proveedor</th>
			<th align="center">Fecha compra</th>
			<th align="center">Factura</th>
			<th align="center">Condicion</th>
			<th align="center">Monto factura</th>
			<th align="center">Orden Num.</th>
			<th align="center">Sucursal</th>
      <th align="center">Registrado por</th>
      <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_reg_compras_resto_det.php?id=<?php echo $rs->fields['idtran']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="tmpcompras_edit.php?id=<?php echo $rs->fields['idtran']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="tmpcompras_del.php?id=<?php echo $rs->fields['idtran']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="right"><?php echo formatomoneda($rs->fields['idtran']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_factura']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
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

<?php

$buscar = "
Select compras.idtran, fecha_compra,factura_numero,nombre,usuario,tipo,gest_depositos_compras.idcompra,
proveedores.nombre as proveedor, compras.facturacompra,
(select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
compras.total as monto_factura, compras.ocnum, 
(select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
(select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
registrado as registrado_el, compras.idcompra
from gest_depositos_compras
inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
inner join compras on compras.idcompra = gest_depositos_compras.idcompra
where 
revisado_por=0 
and compras.estado <> 6
order by fecha_compra desc 
limit 50
";
//echo $buscar;
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>


            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Compras pendientes de Verificacion</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<strong>Compras finalizadas que aun no se dio ingreso al stock:</strong><br />
<br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
      <th align="center">Idtran</th>
      <th align="center">Idcompra</th>
			<th align="center">Proveedor</th>
			<th align="center">Fecha compra</th>
			<th align="center">Factura</th>
			<th align="center">Condicion</th>
			<th align="center">Monto factura</th>
			<th align="center">Orden Num.</th>
			<th align="center">Sucursal</th>
      <th align="center">Registrado por</th>
      <th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_adm_depositos_compras_det.php?idcompra=<?php echo $rs->fields['idcompra']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
				</div>

			</td>
			<td align="right"><?php echo intval($rs->fields['idtran']);  ?></td>
      <td align="right"><?php echo intval($rs->fields['idcompra']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
      <td align="center"><?php echo antixss($rs->fields['tipocompra']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_factura']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="right"><?php echo antixss($rs->fields['sucursal']);  ?></td>
      <td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
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
