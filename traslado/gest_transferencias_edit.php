<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_stock.php");

$idtanda = intval($_GET['id']);

$consulta = "
select gest_depositos_mov.idproducto, gest_depositos_mov.cantidad, gest_depositos_mov.recibio_destino,
(select descripcion from insumos_lista where idinsumo  = gest_depositos_mov.idproducto) as producto, 
gest_transferencias.idtanda, gest_depositos_mov.idserpks
from gest_depositos_mov 
inner join gest_transferencias on gest_transferencias.idtanda = gest_depositos_mov.idtanda
where 
gest_transferencias.estado = 3 
and gest_depositos_mov.estado = 1
and gest_transferencias.idtanda=$idtanda
and recibio_destino = 'N'
order by iddeposito asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtanda = intval($rs->fields['idtanda']);
if ($idtanda == 0) {
    header("location: gest_transferencias.php");
    exit;
}


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function guardar(id){
	
	
	var cantidad = $("#cant_"+id).val();
	var direccionurl='gest_depositos_mov_edit_ajax.php';	
	var parametros = {
	  "id"        : id,
	  "cantidad"  : cantidad,
	  "MM_update" : "form1",
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#box_"+id).html('Guardando...');				
		},
		success:  function (response, textStatus, xhr) {
			if(xhr.status === 200){
				$("#box_"+id).html(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
</script>
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Transferencia en Transito</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">



<p><a href="gest_transferencias.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idproducto</th>
            <th align="center">Producto</th>
            <th align="center">Cantidad</th>
			<th align="center">Recibio destino</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_depositos_mov_edit.php?id=<?php echo $rs->fields['idserpks']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="gest_depositos_mov_del.php?id=<?php echo $rs->fields['idserpks']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
            <td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['producto']); ?></td>
			<td align="right" id="box_<?php echo $rs->fields['idserpks']; ?>"><input name="cant_<?php echo $rs->fields['idserpks']; ?>" id="cant_<?php echo $rs->fields['idserpks']; ?>" type="text" value="<?php echo floatval($rs->fields['cantidad']); ?>" class="form-control" required="required"><a href="javascript:void(0);" onClick="guardar(<?php echo $rs->fields['idserpks']; ?>);" class="btn btn-sm btn-default" title="Guardar" data-toggle="tooltip" data-placement="right"  data-original-title="Guardar"><span class="fa fa-floppy-o"></span></a></td>
			
			<td align="center"><?php echo antixss($rs->fields['recibio_destino']); ?></td>
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
