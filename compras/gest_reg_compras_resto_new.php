<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");

// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);


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
	    <?php require_once("../includes/head_gen.php"); ?>
      <style>
		tbody tr:hover td{
			color : #73879C !important;
		}
		.div_leyenda{
			width: 100%;
			display: flex;
			align-items: center;
			/* justify-content: center; */
		}

		.leyenda_local{
			width: 10px;
			height: 10px;
			background: #9BC1BC;
			display: inline-block;
			margin: 10px;
		}
		.leyenda_importacion{
			width: 10px;
			height: 10px;
			background: #FFC857;
			display: inline-block;
			margin: 10px;
		}
		.importacion{
			background: #FFC857;
			font-weight: bold;
			color: white;
		}
		.local{
			background: #9BC1BC;
			font-weight: bold;
			color: white;

		}
		.sin_verificar{
		background: #ce2d4fa8;
			font-weight: bold;
		}
		.sin_verificar:hover{
		background: #ce2d4f;
			color: #000;
			font-weight: bold;
		}

		
		.verificado{
		background: #D7FFAB;
			font-weight: bold;
		}

		.verificado:hover{
		background: #C3EB97;
			color: #000;
			font-weight: bold;
		}

  </style>
  </head>

  <body class="nav-md">
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
                    <h2>Registro de Compras</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
	<a href="tmpcompras_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
	<a href="reimpresion_compras.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Consulta de Facturas Finalizadas</a>
	<a href="../compras_ordenes/compras_ordenes.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Nueva Compra</a>

</p>
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
					<a href="compras_detalles.php?id=<?php echo $rs->fields['idtran']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
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
Select compras.idtran, fecha_compra,factura_numero,nombre,usuario,gest_depositos_compras.tipo,gest_depositos_compras.idcompra,
proveedores.nombre as proveedor, compras.facturacompra,
(select tipocompra from tipocompra where idtipocompra = compras.tipocompra) as tipocompra,
compras.total as monto_factura, compras.ocnum, 
(select nombre from sucursales where idsucu = compras.sucursal) as sucursal,
(select usuario from usuarios where compras.registrado_por = usuarios.idusu) as registrado_por,
registrado as registrado_el, compras.idcompra, tipo_origen.tipo as origen
from gest_depositos_compras
inner join proveedores on proveedores.idproveedor=gest_depositos_compras.idproveedor
inner join usuarios on usuarios.idusu=gest_depositos_compras.registrado_por
inner join compras on compras.idcompra = gest_depositos_compras.idcompra
INNER JOIN tipo_origen on tipo_origen.idtipo_origen = compras.idtipo_origen
where 
revisado_por=0 
and compras.estado <> 6
and compras.idcompra NOT IN (select compras.idcompra from compras where compras.idcompra_ref is not NULL)
order by compras.idcompra desc 
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
<?php if ($preferencias_compras == "S") { ?>
	<p>En caso de ser una compra de importacion al dar click en el Boton Detalles podras agregar gastos asociados y el costo de la cotizacion del Despacho</p>
	<div class="div_leyenda"> 
		<div class="leyenda_local"></div><small>Origen Local</small>
		<div class="leyenda_importacion"></div><small>Origen importacion</small>
	</div>
<?php } ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
      <th align="center">Idtran</th>
      <th align="center">Idcompra</th>
			<th align="center">Proveedor</th>
			<th align="center">Fecha compra</th>
			<?php if ($preferencias_compras == "S") { ?>
				<th align="center">Origen</th>
			<?php } ?>
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
					<a href="gest_adm_depositos_compras_det.php?idcompra=<?php echo $rs->fields['idcompra']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span> Detalles</a>
					<?php if (compra_importacion($rs->fields['idcompra'])['success'] == true) {?>
						<!-- <a href="../despacho/despacho_add.php?idcompra=<?php // echo $rs->fields['idcompra'];?>" class="btn btn-sm btn-default <?php // if(despacho_verificar($rs->fields['idcompra'])['success']){ echo "verificado";  }else{ echo "sin_verificar";}?> " title="Cotizacion Despacho" data-toggle="tooltip" data-placement="right"  data-original-title="Cotizacion Despacho"><span class="fa fa-suitcase"></span></a> -->
					<?php } ?>
				
        </div>

			</td>
			<td align="right"><?php echo intval($rs->fields['idtran']);  ?></td>
      		<td align="right"><?php echo intval($rs->fields['idcompra']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_compra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_compra']));
			} ?></td>
			<?php if ($preferencias_compras == "S") { ?>
				<td align="center" class="<?php if (strtoupper($rs->fields['origen']) == "IMPORTACION") {
				    echo "importacion";
				} else {
				    echo "local";
				}?>"><?php echo antixss($rs->fields['origen']); ?></td>
			<?php } ?>
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
