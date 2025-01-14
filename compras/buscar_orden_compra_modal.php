<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");
require_once("../compras_ordenes/preferencias_compras_ordenes.php");

$idtran = intval($_POST['idtransaccion']);
$idunico = intval($_POST['idunico']);





$consulta_numero_filas = "
select 
count(*) as filas from compras_ordenes where
compras_ordenes.estado =2 and compras_ordenes.estado_orden =1 and compras_ordenes.ocnum_ref is not NULL
";
$rs_filas = $conexion->Execute($consulta_numero_filas) or die(errorpg($conexion, $consulta_numero_filas));
$num_filas = $rs_filas->fields['filas'];
$filas_por_pagina = 10;
$num_pag = intval($_POST['pag']);
$paginas_num_max = ceil($num_filas / $filas_por_pagina);
if (intval($num_filas) > $filas_por_pagina) {
    $limit = "  LIMIT $filas_por_pagina";
}

if (($_POST['pag']) > 0) {
    $numero = (intval($_POST['pag']) - 1) * $filas_por_pagina;
    if ($numero != 0) {
        $offset = " offset $numero";

    }
} else {
    $offset = " ";
    $num_pag = 1;
}


$whereadd = "";
if ($preferencias_facturas_multiples == "S") {
    $whereadd = "  and compras_ordenes.estado_orden =1 and compras_ordenes.ocnum_ref is not NULL";
} else {
    $whereadd = "and compras_ordenes.ocnum_ref is NULL";
}
$consulta = "
select compras_ordenes.*,
(select usuario from usuarios where compras_ordenes.registrado_por = usuarios.idusu) as registrado_por,
(select proveedores.nombre from proveedores WHERE proveedores.idproveedor = compras_ordenes.idproveedor ) as proveedor
from compras_ordenes
where
compras_ordenes.estado =2 $whereadd
order by ocnum desc $limit $offset
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>
<script>
 if ($("#erroresEditarArticulo").is(':empty')) {
    $('#boxErroresEditarArticulo').hide();
  }
  function cerrar_embarque(event,idembarque){
	event.preventDefault();
	$("#box_embarque_"+idembarque).css('display', 'none');	
	$("#box_close_embarque_"+idembarque).css('display', 'none');	
  }
  function detalle_embarque(event,ocnum){
	event.preventDefault();
	var parametros_array = {
			"ocn"						: ocnum
			
		};
	  $.ajax({		  
		data:  parametros_array,
		url:   './compras_ordenes_grillaprod_det.php',
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 5000
		crossDomain: true,
		beforeSend: function () {
		  $("#submitAgregarProveedor").text('Cargando...');
		},
		success:  function (response) {
			$("#box_embarque_"+ocnum).css('display', 'contents');	
			$("#box_close_embarque_"+ocnum).css('display', 'table-row');	
			$("#embarque_"+ocnum).html(response);	
		},
		error: function(jqXHR, textStatus, errorThrown) {
		  errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
		}
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
		});
	

  }
 
	function IsJsonString(str) {
		try {
			JSON.parse(str);
		} catch (e) {
			return false;
		}
		return true;
	}
	function alerta( clase ,error,titulo){
		var alertaClase = 'alert-' + clase;
		if (clase == "info"){
			$('#boxErroresProveedor').removeClass('alert-danger');
		}else{
			$('#boxErroresProveedor').removeClass('alert-info');
		}
		$('#tituloErroresProveedor').html(titulo);
		$('#boxErroresProveedor').addClass(alertaClase);
		$('#boxErroresProveedor').removeClass('hide');
		$("#erroresProveedor").html(error);
		$('#boxErroresProveedor').addClass('show');
		
	}



	$(document).ready(function() {
		$('#boxErroresProveedor').on('closed.bs.alert', function () {
			$('#boxErroresProveedor').removeClass('show');
			$('#boxErroresProveedor').addClass('hide');
		});
		
	});
	function cerrar_errores_proveedor(event){
		event.preventDefault();
		$('#boxErroresProveedor').removeClass('show');
		$('#boxErroresProveedor').addClass('hide');
	}
	function nl2br (str, is_xhtml) {
		var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	}
	function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){
	// error
	if(tipo == 'error'){
		if(jqXHR.status == 404){
		alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
		}else if(jqXHR.status == 0){
		alert('Se ha rechazado la conexión.');
		}else{
		alert(jqXHR.status+' '+errorThrown);
		}
	// fail
	}else{
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
	}
	}
	function cambiar_pag(i){
		event.preventDefault();
			
			var parametros = {
					"pag"   : i
			};

			$("#titulov").html("Buscar Orden de Compra");
			$.ajax({		  
				data:  parametros,
				url:   'buscar_orden_compra_modal.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {	
					
				},
				success:  function (response) {
					$("#ventanamodal").modal("show");
					$("#cuerpov").html(response);	
					
				}
			});
	}

</script>
<style>
    .finalizado{
      background-color: hsl(210, 50%, 70%) !important;
      color: #fff !important;
      border: hsl(210, 50%, 70%) solid 1px !important;
	  }
    .activo{
      background: #C3EB97;
      color: #405467!important;
    }
  </style>
<div class="clearfix"></div>
<br />
<br />
<div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
	<button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
</div>


<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Ocnum</th>
			<th align="center">Proveedor</th>
			<th align="center">Idproveedor</th>
			<th align="center">Registrado por</th>
			<th align="center">fecha</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="javascript:void(0);" onclick="detalle_embarque(event,<?php echo $rs->fields['ocnum']; ?>)" class="btn btn-sm btn-default" title="Detalle" data-placement="right"  ><span class="fa fa-search"></span></a>
					<a href="tmpcompras_add.php?idproveedor=<?php echo $rs->fields['idproveedor']; ?>&ocnum=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Select" data-toggle="tooltip" data-placement="right"  data-original-title="Select"><span class="fa fa-check"></span>Seleccionar</a>
					<!-- <a href="../compras/compras_detalles.php?id=<?php echo $rs->fields['ocnum']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-list-ul"></span></a> -->
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['ocnum']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['proveedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['idproveedor']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php echo antixss(date("d/m/Y", strtotime($rs->fields['fecha']))); ?></td>
		</tr>
		<tr style="display:none;" id="box_close_embarque_<?php echo $rs->fields['ocnum']; ?>"><td colspan="7">
			<div  style="display:flex;justify-content:right;">
				<a href="javascript:void(0);" onclick="cerrar_embarque(event,<?php echo $rs->fields['ocnum']; ?>);" class="btn btn-sm btn-default">
					<span class="fa fa-close"></span>
				</a>
			</div>
			</td>
		</tr>
		<tr style="display:none;" id="box_embarque_<?php echo $rs->fields['ocnum']; ?>">
			<td id="embarque_<?php echo $rs->fields['ocnum']; ?>" colspan="7"></td>
		</tr>

<?php

$rs->MoveNext();
} //$rs->MoveFirst();?>


<tr>
    <td align="center" colspan="10">
        <div class="btn-group">
            <?php
            $last_index = 0;
if ($num_pag + 10 > $paginas_num_max) {
    $last_index = $paginas_num_max;
} else {
    $last_index = $num_pag + 10;
}
if ($num_pag != 1) { ?>
                <a href="javascript:void(0);" onclick="cambiar_pag(<?php echo(1);?>)" class="btn btn-sm btn-default" title="<?php echo(1);?>"  data-placement="right"  data-original-title="<?php echo(1);?>"><span class="fa fa-chevron-left"></span><span class="fa fa-chevron-left"></span></a>
                <a href="javascript:void(0);" onclick="cambiar_pag(<?php echo($num_pag - 1);?>)" class="btn btn-sm btn-default" title="<?php echo($num_pag - 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag - 1);?>"><span class="fa fa-chevron-left"></span></a>
            <?php }
$inicio_pag = 0;
if ($num_pag != 1 && $num_pag - 5 > 0) {
    $inicio_pag = $num_pag - 5;
} else {
    $inicio_pag = 1;
}
for ($i = $inicio_pag; $i <= $last_index; $i++) {
    ?>
                <a href="javascript:void(0);" onclick="cambiar_pag(<?php echo($i);?>)" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo($i);?>"  data-placement="right"  data-original-title="<?php echo($i);?>"><?php echo($i);?></a>
                <?php if ($i == $last_index && ($num_pag + 1 <= $paginas_num_max)) {?>
                    <a  href="javascript:void(0);" onclick="cambiar_pag(<?php echo($num_pag + 1);?>)" class="btn btn-sm btn-default" title="<?php echo($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo($num_pag + 1);?>"><span class="fa fa-chevron-right"></span></a>
                    <a href="javascript:void(0);"  onclick="cambiar_pag(<?php echo($paginas_num_max);?>)" class="btn btn-sm btn-default" title="<?php echo($paginas_num_max);?>"  data-placement="right"  data-original-title="<?php echo($paginas_num_max);?>"><span class="fa fa-chevron-right"></span><span class="fa fa-chevron-right"></span></a>
                <?php } ?>
            <?php } ?>
        </div>
    </td>
</tr>
	  </tbody>
	 
    </table>
</div>






