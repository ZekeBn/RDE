<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "599";
$dirsup = "S";
require_once("../includes/rsusuario.php");





$iddespacho = intval($_GET['id']);
if ($iddespacho == 0) {
    header("location: despacho.php");
    exit;
}

$idcompra = intval($_GET['idcompra']);


// consulta a la tabla
$consulta = "
select *,
(select usuario from usuarios where despacho.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from tipo_moneda where tipo_moneda.idtipo = despacho.tipo_moneda ) as moneda,
( select aduana.descripcion from aduana where aduana.idaduana = despacho.idaduana ) as aduana,
(SELECT proveedores.nombre from proveedores where  proveedores.idproveedor = despacho.iddespachante ) as despachante,
(select emb.ocnum from embarque as emb INNER JOIN compras on compras.idcompra = emb.idcompra and compras.idcompra = despacho.idcompra) as ocnum
from despacho 
where 
iddespacho = $iddespacho
and estado = 1
limit 1
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddespacho = intval($rs->fields['iddespacho']);
$idcompra = intval($rs->fields['idcompra']);
$ocnum = intval($rs->fields['ocnum']);

if ($iddespacho == 0) {
    header("location: despacho.php");
    exit;
}



?>
<!DOCTYPE html>
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
                    <h2>Detalle Tipo Cambio Despacho</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

 




                  
                  
              
<p><a href="<?php if ($idcompra != 0) {
    echo "../compras/gest_adm_depositos_compras_det.php?idcompra=$idcompra";
} else { ?>despacho.php<?php } ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Iddespacho</th>
			<td align="center"><?php echo intval($rs->fields['iddespacho']); ?></td>
		</tr>
		<tr>
			<th align="center">Tipo moneda</th>
			<td align="center"><?php echo antixss($rs->fields['moneda']); ?></td>
		</tr>
		<tr>
			<th align="center">Despachante</th>
			<td align="center"><?php echo antixss($rs->fields['despachante']); ?></td>
		</tr>
		<tr>
			<th align="center">Aduana</th>
			<td align="center"><?php echo antixss($rs->fields['aduana']); ?></td>
		</tr>
		<tr>
			<th align="center">Cotizacion</th>
			<td align="center"><?php echo formatomoneda($rs->fields['cotizacion']);  ?></td>
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
	
	
		<tr>
			<th align="center">Comentario</th>
			<td align="center"><?php echo antixss($rs->fields['comentario']); ?></td>
		</tr>
		<tr>
			<th align="center">Fecha despacho</th>
			<td align="center"><?php if ($rs->fields['fecha_despacho'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_despacho']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Idcompra</th>
			<td align="center"><?php echo intval($rs->fields['idcompra']); ?></td>
		</tr>


</table>
 </div>
<br />

<div class="row" >
  <?php if ($ocnum > 0) { ?>
    <div id="grilla_box1" class="col-md-12 col-sm-12 col-xs-12" >
      <h2 >Orden de compra</h2>
      <?php require("../embarque/compras_ordenes_grillaprod_det.php"); ?>
    </div>
  <?php } ?>
  <div class="clearfix"></div>
  <div id="grilla_box2" class="col-md-12 col-sm-12 col-xs-12" >
    <h2 >Factura de compra</h2>
    <?php require("../embarque/compras_grillaprod_det.php"); ?>
  </div>
  <div class="clearfix"></div>
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
<div class="modal fade bs-example-modal-lg fade in  hide" tabindex="-1"  role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg alert">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" onclick="cerrar_errores_proveedor(event)"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
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
