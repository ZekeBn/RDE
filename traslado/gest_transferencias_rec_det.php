<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "223";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_stock.php");
require_once("includes/funciones_traslados.php");

$idtanda = intval($_GET['id']);
if ($idtanda == 0) {
    header("location: gest_transferencias_rec.php");
    exit;
}



$consulta = "
select editar_traslado , editar_traslado_recep
from usuarios 
where 
idusu = $idusu
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$editar_traslado = $rs->fields['editar_traslado'];
$editar_traslado_recep = $rs->fields['editar_traslado_recep'];

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.origen) as deposito_origen,
(select descripcion from gest_depositos where iddeposito = gest_transferencias.destino) as deposito_destino,
(select usuario from usuarios where gest_transferencias.generado_por = usuarios.idusu) as generado_por
from gest_transferencias 
where 
 estado = 3 
  and idtanda = $idtanda
order by idtanda asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtanda = intval($rs->fields['idtanda']);
$fecha_transferencia = $rs->fields['fecha_transferencia'];
$iddeposito_destino = intval($rs->fields['destino']);
$iddeposito_origen = intval($rs->fields['origen']);
$idpedidorepo = intval($rs->fields['idpedidorepo']);
if ($idtanda == 0) {
    header("location: gest_transferencias_rec.php");
    exit;
}


$consulta = "
select * from gest_depositos where tiposala = 3 order by iddeposito asc limit 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito_transito = intval($rstran->fields['iddeposito']);
if ($iddeposito_transito == 0) {
    echo "Deposito de transito inexistente.";
    exit;
}

// actualiza cantidad recibida igualando a cantidad
$consulta = "
update gest_depositos_mov 
set 
cantidad_recibe = cantidad
 where 
idtanda = $idtanda
and idmotivo_dif is null 
and recibio_destino = 'N'
and estado = 1
and cantidad_recibe = 0
";
//$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots



    $parametros_array = [
        'idtanda' => $idtanda,
        'idusu' => $idusu

    ];
    $res = recepcion_traslado_valida($parametros_array);
    if ($res['valido'] == 'N') {
        $valido = "N";
        $errores .= $res['errores'];
    }




    // si todo es correcto actualiza
    if ($valido == "S") {



        $res = recepcion_traslado_registra($parametros_array);

        header("location: gest_transferencias_rec.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());






?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function guarda_recibe(id,i){
	
	var cantidad_recibe = $("#cantidad_recibe_"+id).val();
	var idmotivodif = $("#idmotivo_"+id).val();
	
	var flag_html='<input name="edit_flag_'+i+'" id="edit_flag_'+i+'" type="hidden" value="0"><input name="idser_fila_'+i+'" id="idser_fila_'+i+'" type="hidden" value="'+id+'">';
	var flag_html_edit='<input name="edit_flag_'+i+'" id="edit_flag_'+i+'" type="hidden" value="1"><input name="idser_fila_'+i+'" id="idser_fila_'+i+'" type="hidden" value="'+id+'">';
	
	var direccionurl='gest_depositos_mov_edit_ajax_recep.php';	
	var parametros = {
	  "id"              : id,
	  "cantidad_recibe" : cantidad_recibe,
	  "idmotivo"     : idmotivodif
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#busqueda_prod").html('Cargando...');
			$("#btn_guarda_"+id).hide();				
		},
		/*
		'idmotivo' => $idmotivo,
		'cantidad_recibe' => $cantidad_recibe,
		'cantidad_enviada' => $cantidad_traslado,
		'diferencia' => $diferencia,
		'valido' => $valido,
		'errores' => $errores
		*/
		
		success:  function (response, textStatus, xhr) {
			
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					$("#dif_"+id).html(obj.diferencia+flag_html);
					$("#cantenv_"+id).html(obj.cantidad_enviada);
					$("#cantidad_recibe_"+id).val(obj.cantidad_recibe);
					if(obj.cantidad_enviada !=  obj.cantidad_recibe){
						document.getElementById("fila_"+id).style.backgroundColor='#F99';
					}else{
						document.getElementById("fila_"+id).style.backgroundColor='#FFF';
					}
				}else{
					alerta_modal('No Procesado',nl2br(obj.errores));
					$("#dif_"+id).html('Error'+flag_html_edit);
					document.getElementById("fila_"+id).style.backgroundColor='#FF9';
				}
			}else{
				alert(response);
				$("#dif_"+id).html('Error'+flag_html_edit);	
				document.getElementById("fila_"+id).style.backgroundColor='#FF9';
			}
			$("#btn_guarda_"+id).show();
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
function editandose(i){
	//$("#enviar").hide();
	var id = $('#idser_fila_'+i).val();
	$('#edit_flag_'+i).val(1);
	document.getElementById("fila_"+id).style.backgroundColor='#FF9';
	
}
function comprueba_edicion(){
	var valido = 'S';
	for(i=1;i<1000;i++){
		var e_flag = parseInt($('#edit_flag_'+i).val());
		if(e_flag > 0){
			valido = 'N';
			//alert('Hay productos editandose (Amarillo), guarde la edicion antes de finalizar.');
			break;
		}
	}	
	if(valido == 'S'){
		//alert('listo!');
		$('#form1_final').submit();
	}else{
		alert('Hay productos editandose (Amarillo), guarde la edicion antes de finalizar.');
	}
	//
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function alerta_modal(titulo,mensaje){
	$('#dialogobox').modal('show');
	$("#myModalLabel").html(titulo);
	$("#modal_cuerpo").html(mensaje);	
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
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
                    <h2>Traslados entrantes sin confirmar</h2>
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

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idtanda</th>
			<th align="center">Origen</th>
			<th align="center">Destino</th>
			<th align="center">Fecha transferencia</th>
			<th align="center">Fecha Registrado</th>
			<th align="center">Generado por</th>
			</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo intval($rs->fields['idtanda']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_origen']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['deposito_destino']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_transferencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_transferencia']));
			} ?></td>
			<td align="center"><?php if ($rs->fields['fecha_real'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_real']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['generado_por']); ?></td>
			</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php
$consulta = "
select *, 
(select descripcion from insumos_lista where idinsumo = gest_depositos_mov.idproducto) as producto
from gest_depositos_mov 
where 
 idtanda = $idtanda 
 and estado  = 1
order by fechahora asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
///echo $consulta;
?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
            <th align="center">Idproducto</th>
			<th align="center">Producto</th>
			<th align="center">Cantidad</th>
			<th align="center">Cantidad Recibida</th>
			<th align="center">Diferencia</th>
			<th align="center">Motivo Diferencia</th>
            <th align="center"></th>
		</tr>
	  </thead>
	  <tbody>
<?php
$i = 1;
while (!$rs->EOF) {
    $idserpks = $rs->fields['idserpks'];

    $cantidad_traslado = floatval($rs->fields['cantidad']);
    $cantidad_recibe = floatval($rs->fields['cantidad_recibe']);
    $diferencia = $cantidad_traslado - $cantidad_recibe;
    if ($diferencia <> 0) {
        $style = 'style="background-color:#F99;"';
    } else {
        $style = 'style="background-color:#FFF;"';
    }

    ?>
		<tr  id="fila_<?php echo $idserpks; ?>" <?php echo $style; ?> >
			
            <td align="center"><?php echo antixss($rs->fields['idproducto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['producto']); ?></td>
            <td align="right" id="cantenv_<?php echo $idserpks ?>"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>
            <td align="right">
              <input type="text" name="cantidad_recibe_<?php echo $idserpks; ?>" id="cantidad_recibe_<?php echo $idserpks; ?>" value="<?php echo floatval($rs->fields['cantidad_recibe']); ?>" class="form-control" <?php if ($editar_traslado_recep == 'S') { ?>onChange="editandose(<?php echo $i; ?>);"<?php } else { ?> disabled<?php } ?> ></td>
            <td align="right" id="dif_<?php echo $idserpks ?>"><?php echo $diferencia; ?>
            <input name="edit_flag_<?php echo $i; ?>" id="edit_flag_<?php echo $i; ?>" type="hidden" value="0">
            <input name="idser_fila_<?php echo $i; ?>" id="idser_fila_<?php echo $i; ?>" type="hidden" value="<?php echo $idserpks ?>">
            </td>
            <td align="right">
              <?php
$disabled = "";
    if ($editar_traslado_recep != 'S') {
        $disabled = "disabled";
    }

    // consulta
    $consulta = "
SELECT idmotivo, motivo 
FROM motivos_transfer_norecibe
where 
estado = 1
order by motivo asc
 ";

    // valor seleccionado
    if (isset($_POST['idmotivo_'.$idserpks])) {
        $value_selected = htmlentities($_POST['idmotivo_'.$idserpks]);
    } else {
        $value_selected = htmlentities($rs->fields['idmotivo_dif']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idmotivo_'.$idserpks,
        'id_campo' => 'idmotivo_'.$idserpks,

        'nombre_campo_bd' => 'motivo',
        'id_campo_bd' => 'idmotivo',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '   '.$disabled,
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);


    ?></td>
            <td align="right">
            <?php if ($editar_traslado_recep == 'S') { ?>
            <a href="#" class="btn btn-sm btn-default" id="btn_guarda_<?php echo $idserpks; ?>" onClick="guarda_recibe(<?php echo $idserpks; ?>,<?php echo $i; ?>);"><span class="fa fa-floppy-o"></span> Guardar</a>
			<?php } else { ?>
            <a href="#" class="btn btn-sm btn-default" id="btn_guarda_<?php echo $idserpks; ?>" onClick="alert('Tu usuario no tiene permisos para realizar esta accion.');"><span class="fa fa-floppy-o"></span> Guardar</a>
            <?php } ?>
            </td>
		</tr>
<?php
    $i++;
    $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />


<div class="clearfix"></div>
<br />
<form id="form1_final" name="form1" method="post" action="">
    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3" id="enviar">
        <?php if ($editar_traslado_recep == 'S') { ?>
	   <button type="button" class="btn btn-success" onMouseUp="comprueba_edicion();" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <?php } else { ?>
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <?php } ?>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_transferencias_rec.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>

<br /><br />
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        <!-- POPUP DE MODAL OCULTO -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
						...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->


        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
