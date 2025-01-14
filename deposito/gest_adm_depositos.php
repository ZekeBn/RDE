<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "55";
require_once("../includes/rsusuario.php");
require_once("./preferencias_deposito.php");

$consulta = "SELECT iddeposito FROM gest_depositos WHERE UPPER(gest_depositos.descripcion) like \"%SALON DE VENTAS%\" or UPPER(gest_depositos.descripcion) like \"%AVERIADO%\"  or UPPER(gest_depositos.descripcion) like \"%IRRECUPERABLE%\" ";
$rs_alertas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$alertas = $rs_alertas->RecordCount();
// busca si hay deposito de transito creado
$consulta = "
select iddeposito from gest_depositos where tiposala = 3 and estado = 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si no hay crea
if (intval($rstran->fields['iddeposito']) == 0) {
    $consulta = "
	select max(iddeposito) as max from gest_depositos
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito_new = intval($rsmax->fields['max']) + 1;

    $consulta = "
	INSERT INTO gest_depositos 
	(
	iddeposito, direccion, idencargado, estado, descripcion, tiposala,
	 color, idempresa, idsucursal, borrable, compras, orden_nro
	 ) 
	VALUES
	(
	$iddeposito_new, NULL, 1, 1, 'TRANSITO', 3,
	 '#FFFFFF', 1, 1, 'N', 0, 1
	 );
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}




$consulta = "
select *,
(select usuario from usuarios where gest_depositos.idencargado = usuarios.idusu) as encargado,
(select sucursales.nombre from sucursales where sucursales.idsucu=gest_depositos.idsucursal limit 1) as sucursal,
(select tipo_sala from gest_depositos_tiposala where gest_depositos_tiposala.idtiposala = gest_depositos.tiposala) as tipo_sala
from gest_depositos 
where 
 estado = 1 
order by orden_nro asc, descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//$tdpto=$rsdpto->RecordCount();




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<link href="../vendors/switchery/dist/switchery.min.css" rel="stylesheet">
	<script src="../vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
	<script>
		function IsJsonString(str) {
			try {
				JSON.parse(str);
			} catch (e) {
				return false;
			}
			return true;
		}
		function registra_permiso_devolucion(iddeposito){
			var direccionurl='gest_adm_depositos_autosel_devolucion.php';	
			var parametros = {
			"iddeposito"  : iddeposito
			};
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
						// $("#box_td_devolucion_"+iddeposito).html('Cargando...');	
				},
				success:  function (response, textStatus, xhr) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						if(obj.success == true){
							$("#box_td_devolucion_"+iddeposito).html(obj.html_checkbox);
							if(obj.autosel_devolucion == "S"){
								var elems = document.querySelector('#box_devolucion_'+iddeposito);
								$("#box_devolucion_"+iddeposito).prop("checked",true);
								var switchery = new Switchery(elems);
								if(parseInt(obj.id_activo_anterior) >0){
									$("#box_td_devolucion_"+obj.id_activo_anterior).html(obj.html_checkbox_anterior);
									var elemento_anterior = document.querySelector('#box_devolucion_'+obj.id_activo_anterior);
									$("#box_devolucion_"+obj.id_activo_anterior).prop("checked",false)
									var switchery = new Switchery(elemento_anterior);
								}
							}
							if(obj.autosel_devolucion == "N"){
								var elems = document.querySelector('#box_devolucion_'+iddeposito);
								$("#box_devolucion_"+iddeposito).prop("checked",false)
								var switchery = new Switchery(elems);
							}
						}else{
							alert(obj.errores);
						}
					}else{
						alert(response);	
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
	function registra_permiso(iddeposito){
		var direccionurl='gest_adm_depositos_autosel.php';	
		//alert(direccion);
		var parametros = {
		"iddeposito"  : iddeposito
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {
					// $("#box_td_"+iddeposito).html('Cargando...');	
			},
			success:  function (response, textStatus, xhr) {
				if(IsJsonString(response)){
					var obj = jQuery.parseJSON(response);
					if(obj.success == true){
						$("#box_td_"+iddeposito).html(obj.html_checkbox);
						if(obj.autosel_compras == "S"){
							var elems = document.querySelector('#box_'+iddeposito);
							$("#box_"+iddeposito).prop("checked",true)
							var switchery = new Switchery(elems);
							if(parseInt(obj.id_activo_anterior) >0){
								$("#box_td_"+obj.id_activo_anterior).html(obj.html_checkbox_anterior);
								var elemento_anterior = document.querySelector('#box_'+obj.id_activo_anterior);
								$("#box_"+obj.id_activo_anterior).prop("checked",false)
								var switchery = new Switchery(elemento_anterior);
							}
						}
						if(obj.autosel_compras == "N"){
							var elems = document.querySelector('#box_'+iddeposito);
							$("#box_"+iddeposito).prop("checked",false)
							var switchery = new Switchery(elems);
						}
					}else{
						alert(obj.errores);
					}
				}else{
					alert(response);	
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
	function switchery_reactivar(){
		var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
		elems.forEach(function(html) {
			var switchery = new Switchery(html);
		});
	}
	function switchery_reactivar_uno(idsubmodulo){
			var elems = document.querySelector('#box_'+idsubmodulo);
			var switchery = new Switchery(elems);

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
                    <h2>Administrar Depositos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">




<p>
<a href="gest_adm_depositos_agregar.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a>
<a href="gest_adm_depositos_borrados.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Depositos Borrados</a>
<a href="gest_deposito_admin_global_xls.php" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Stock Consolidado</a>
<a href="gest_deposito_admin_global_det_csv.php" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar Stock Global Detallado</a>
<?php if ($preferencia_conteo_por_producto == "S") {?>
	<a href="../conteo_deposito/conteo_stock.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Conteo de Stock</a>
<?php } ?>
</p>
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Orden nro</th>
			<th align="center">Deposito</th>
			<th align="center">Tipo Deposito</th>
			<th align="center">Sucursal</th>
			<th align="center">Encargado</th>
			<th align="center">Direccion</th>
			<?php if ($preferencia_autosel_compras == "S") { ?>
				<th >Predeterminado en Compras</th>
			<?php } ?>
			<?php if ($preferencia_autosel_devoluciones == "S") {?>
				<th >Predeterminado en Devolucion</th>
			<?php } ?>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {
    $iddeposito_sel = intval($rs->fields['iddeposito']);
    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="gest_deposito_admin.php?idpo=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
                    <?php if ($rs->fields['tiposala'] != 3) { ?>
					<a href="gest_depositos_edit.php?id=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<?php if ($rs->fields['borrable'] == 'S') { ?>
                    <a href="gest_depositos_del.php?id=<?php echo $rs->fields['iddeposito']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                    <?php } ?>
                    <?php } ?>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['orden_nro']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipo_sala']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['encargado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
			<?php if ($preferencia_autosel_compras == "S" && intval($rs->fields['tiposala']) != 3 && intval($rs->fields['compras']) == 0) { ?>
				<td align="center" id="box_td_<?php echo $iddeposito_sel; ?>">		
					<input name="producto" id="box_<?php echo $iddeposito_sel; ?>" type="checkbox" value="S" class="js-switch" onChange="registra_permiso(<?php echo $iddeposito_sel; ?>);" <?php if (($rs->fields['autosel_compras']) == "S") {
					    echo "checked";
					} ?>   >
				</td>
				<?php } ?>
				<?php if ($preferencia_autosel_devoluciones == "S") {?>
				<td align="center" id="box_td_devolucion_<?php echo $iddeposito_sel; ?>">		
					<input name="devolucion" id="box_devolucion_<?php echo $iddeposito_sel; ?>" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_devolucion(<?php echo $iddeposito_sel; ?>);" <?php if (($rs->fields['autosel_devolucion']) == "S") {
					    echo "checked";
					} ?>   >
				</td>

			<?php } ?>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />


<?php if ($alertas < 3) { ?>

<div class="alert alert-info" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
	</button>
	<div  style="font-size: 1.5rem;">
		Se solicita encarecidamente crear los siguientes elementos si aún no existen en el sistema:<br>
		<ul>
			<li>Salon de Ventas</li>
			<li>Averiado</li>
			<li>Irrecuperable</li>
		</ul>
		<p >Agradecemos su pronta atención y acción sobre este asunto.</p>
	</div>
</div>
<?php } ?>
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
