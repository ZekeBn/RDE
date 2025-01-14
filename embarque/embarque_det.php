<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "598";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");




$idembarque = intval($_GET['id']);
if ($idembarque == 0) {
    header("location: embarque.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *,
(select transporte.descripcion from transporte where transporte.idtransporte = embarque.idtransporte ) as nombre_transporte,
( select puertos.descripcion from puertos where puertos.idpuerto = embarque.idpuerto ) as puerto,
(select ocnum_ref from compras_ordenes where ocnum = embarque.ocnum) as ocnum_ref,
( select vias_embarque.descripcion from vias_embarque where vias_embarque.idvias_embarque = embarque.idvias_embarque ) as vias,
(select usuario from usuarios where embarque.registrado_por = usuarios.idusu) as registrado_por
from embarque 
where 
idembarque = $idembarque
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idembarque = intval($rs->fields['idembarque']);
$ocnum = intval($rs->fields['ocnum']);
$ocnum_ref = intval($rs->fields['ocnum_ref']);
$idcompra = intval($rs->fields['idcompra']);
if ($idembarque == 0) {
    header("location: embarque.php");
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
    function cambiar_vias_embarque(selectElement){
      const selectedOption = selectElement.options[selectElement.selectedIndex];
      const idViasEmbarque = selectedOption.dataset.hiddenValue;
      // console.log(idViasEmbarque);
      $("#idvias_embarque").val(idViasEmbarque)

    }
  </script>
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
                    <h2>Embarque</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

	
                  





				  
<p>
  <a href="embarque.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
  <?php if ($ocnum_ref > 0) {?>
    <a href="../compras_ordenes/compras_ordenes_det_finalizado.php?id=<?php echo $ocnum_ref ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Orden de Compra</a>
  <?php } ?>
</p>

<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Idembarque</th>
			<td align="center"><?php echo intval($rs->fields['idembarque']); ?></td>
		</tr>
		<tr>
			<th align="center">Idcompra</th>
			<td align="center"><?php echo intval($rs->fields['idcompra']); ?></td>
		</tr>
    <tr>
			<th align="center">Ocnum</th>
			<td align="center"><?php echo intval($rs->fields['ocnum']); ?></td>
		</tr>
		<tr>
			<th align="center">Puerto</th>
			<td align="center"><?php echo antixss($rs->fields['puerto']); ?></td>
		</tr>
		<tr>
			<th align="center">Idtransporte</th>
			<td align="center"><?php echo antixss($rs->fields['nombre_transporte']); ?></td>
		</tr>
		<tr>
			<th align="center">Idvias embarque</th>
			<td align="center"><?php echo antixss($rs->fields['vias']); ?></td>
		</tr>
		<tr>
			<th align="center">Estado Embarque</th>
			<td align="center"><?php echo intval($rs->fields['estado_embarque']) == 1 ? "Activo" : "Finalizado"; ?></td>
		</tr>
		<tr>
			<th align="center">Descripcion</th>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
		</tr>

		<tr>
			<th align="center">Fecha embarque</th>
			<td align="center"><?php if ($rs->fields['fecha_embarque'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_embarque']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Fecha llegada</th>
			<td align="center"><?php if ($rs->fields['fecha_llegada'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_llegada']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Registrado por</th>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
		</tr>
		<tr>
			<th align="center">Registrado el</th>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
		</tr>
		


</table>
<br />
</div>
<div class="row" >
  <div id="grilla_box1" class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
    <h2 >Orden de compra</h2>
    <?php require("compras_ordenes_grillaprod_det.php"); ?>
  </div>
  <div id="grilla_box2" class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
    <h2 >Factura de compra</h2>
    <?php require("compras_grillaprod_det.php"); ?>
  </div>
  <div class="clearfix"></div>
  </div>
</div>

<div class="clearfix"></div>
</div>


    







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
