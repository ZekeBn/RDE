<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");


$consulta = "
select *
from compras 
where 
 estado = 1 
order by idtran asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
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
                    <h2>Titulo Modulo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                 
                  
<p><a href="compras_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idtran</th>
			<th align="center">Idcompra</th>
			<th align="center">Idproveedor</th>
			<th align="center">Sucursal</th>
			<th align="center">Idempresa</th>
			<th align="center">Fechacompra</th>
			<th align="center">Facturacompra</th>
			<th align="center">Facturacompra incrementa</th>
			<th align="center">Registrado por</th>
			<th align="center">Total</th>
			<th align="center">Iva10</th>
			<th align="center">Iva5</th>
			<th align="center">Exenta</th>
			<th align="center">Registrado</th>
			<th align="center">Estado</th>
			<th align="center">Tipocompra</th>
			<th align="center">Moneda</th>
			<th align="center">Cambio</th>
			<th align="center">Comprastock</th>
			<th align="center">Cambioreal</th>
			<th align="center">Cambiohacienda</th>
			<th align="center">Cambioproveedor</th>
			<th align="center">Anulado el</th>
			<th align="center">Anulado por</th>
			<th align="center">Vencimiento</th>
			<th align="center">Timbrado</th>
			<th align="center">Vto timbrado</th>
			<th align="center">Notacredito</th>
			<th align="center">Creado auto</th>
			<th align="center">Ocnum</th>
			<th align="center">Idnotacred</th>
			<th align="center">Idtipocomprobante</th>
			<th align="center">Cdc</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="compras_det.php?id=<?php echo $rs->fields['idtran']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="compras_edit.php?id=<?php echo $rs->fields['idtran']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="compras_del.php?id=<?php echo $rs->fields['idtran']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idtran']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idcompra']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idproveedor']); ?></td>
			<td align="center"><?php echo intval($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idempresa']); ?></td>
			<td align="center"><?php if ($rs->fields['fechacompra'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fechacompra']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['facturacompra']); ?></td>
			<td align="center"><?php echo intval($rs->fields['facturacompra_incrementa']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['total']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['iva10']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['iva5']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['exenta']);  ?></td>
			<td align="center"><?php if ($rs->fields['registrado'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado']));
			}  ?></td>
			<td align="center"><?php echo intval($rs->fields['estado']); ?></td>
			<td align="center"><?php echo intval($rs->fields['tipocompra']); ?></td>
			<td align="center"><?php echo intval($rs->fields['moneda']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cambio']);  ?></td>
			<td align="center"><?php echo intval($rs->fields['comprastock']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cambioreal']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cambiohacienda']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cambioproveedor']);  ?></td>
			<td align="center"><?php if ($rs->fields['anulado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['anulado_el']));
			}  ?></td>
			<td align="center"><?php echo intval($rs->fields['anulado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['vencimiento'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['vencimiento']));
			} ?></td>
			<td align="center"><?php echo intval($rs->fields['timbrado']); ?></td>
			<td align="center"><?php if ($rs->fields['vto_timbrado'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['vto_timbrado']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['notacredito']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['creado_auto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idnotacred']); ?></td>
			<td align="center"><?php echo intval($rs->fields['idtipocomprobante']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['cdc']); ?></td>
		</tr>
<?php
$total_acum += $rs->fields['total'];
    $iva10_acum += $rs->fields['iva10'];
    $iva5_acum += $rs->fields['iva5'];
    $exenta_acum += $rs->fields['exenta'];
    $cambio_acum += $rs->fields['cambio'];
    $cambioreal_acum += $rs->fields['cambioreal'];
    $cambiohacienda_acum += $rs->fields['cambiohacienda'];
    $cambioproveedor_acum += $rs->fields['cambioproveedor'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	  <tfoot>
		<tr>
			<td>Totales</td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($total_acum); ?></td>
			<td align="center"><?php echo formatomoneda($iva10_acum); ?></td>
			<td align="center"><?php echo formatomoneda($iva5_acum); ?></td>
			<td align="center"><?php echo formatomoneda($exenta_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($cambio_acum); ?></td>
			<td></td>
			<td align="center"><?php echo formatomoneda($cambioreal_acum); ?></td>
			<td align="center"><?php echo formatomoneda($cambiohacienda_acum); ?></td>
			<td align="center"><?php echo formatomoneda($cambioproveedor_acum); ?></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
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
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
