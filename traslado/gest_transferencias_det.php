<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");


// funciones para stock
require_once("includes/funciones_traslados.php");
require_once("includes/funciones_stock.php");
require_once("includes/funciones_produccion.php");

$idtanda = intval($_GET['id']);


// busca en preferencias si quiere validar o no el disponible de stock
$consulta = "
SELECT 	traslado_nostock FROM preferencias where idempresa = $idempresa
";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rspref->fields['traslado_nostock'] == 2) {
    $valida_stock = "S";
} else {
    $valida_stock = "N";
}



//Buscamos tanda activa
$buscar = "
select * 
from gest_transferencias 
where 
estado=1 
and idtanda = $idtanda
";
$rstanda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtanda = intval($rstanda->fields['idtanda']);
$fecha_transferencia = $rstanda->fields['fecha_transferencia'];
$idpedidorepo = $rstanda->fields['idpedidorepo'];
if ($idtanda == 0) {
    header("location: gest_transferencias.php");
    exit;
}

// valida deposito de transito
$consulta = "
select * from gest_depositos where tiposala = 3 order by iddeposito asc limit 1
";
$rstran = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito_transito = intval($rstran->fields['iddeposito']);
if ($iddeposito_transito == 0) {
    echo "Deposito de transito inexistente.";
    exit;
}

// datos de transferencia
$estado = intval($rstanda->fields['estado']);
$origen = intval($rstanda->fields['origen']);
$destino = intval($rstanda->fields['destino']);
if ($rstanda->fields['fecha_transferencia'] != '') {
    $fechis = date("Y-m-d", strtotime($rstanda->fields['fecha_transferencia']));
}

// descripcion de depositos
$buscar = "select *,
(Select descripcion as origen from gest_depositos where iddeposito=$origen) as origen,
(Select descripcion as dst from gest_depositos where iddeposito=$destino) as destino
from gest_depositos
limit 1
";
$rsdd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$orichar = trim($rsdd->fields['origen']);
$deschar = trim($rsdd->fields['destino']);







// trae los depositos para origen y destino
$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where usuarios.idempresa=$idempresa and gest_depositos.idempresa=$idempresa 
and tiposala <> 3
order by descripcion ASC ";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado
where usuarios.idempresa=$idempresa and gest_depositos.idempresa=$idempresa 
and tiposala <> 3
order by descripcion ASC";
$rsd2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



//Post Final
if (isset($_POST['ter']) && ($_POST['ter']) != '') {
    $tfin = $idtanda;
    $valido = "S";
    $errores = "";

    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());





    // parametros para validar
    $parametros_array = [
        'iddeposito_origen' => $origen,
        'iddeposito_destino' => $destino,
        'fecha_traslado' => $fecha_transferencia,
        'generado_por' => $idusu,
        'detalle' => '',
        'detalle_precargado' => 'S',
        'idtanda' => $idtanda

    ];


    /*$detalle[]=array(
        'idinsumo' => 7,
        'cantidad' => 10,
        'necesidad' => 0,
        'lote' => 770,
        'vencimiento' => '2021-05-01',
    );
    $detalle[]=array(
        'idinsumo' => 15,
        'cantidad' => 8,
        'necesidad' => 0,
        'lote' => 585,
        'vencimiento' => '2021-06-01',
    );*/
    /*$parametros_array=array(
        'iddeposito_origen' => $origen,
        'iddeposito_destino' => $destino,
        'fecha_traslado' => $fecha_transferencia,
        'generado_por' => $idusu,
        'detalle' => $detalle,
        'detalle_precargado' => 'N',
        'idtanda' => $idtanda

    );*/
    //print_r($parametros_array);exit;
    $res = valida_traslado_entre_depositos($parametros_array);
    // si algo no es valido
    if ($res['valido'] != 'S') {
        $errores .= $res['errores'];
        $valido = "N";
    }


    //


    //print_r($res);exit;
    // si todo es valido
    if ($valido == "S") {


        $res = registra_traslado_entre_depositos($parametros_array);
        //print_r($res);exit;

        header("Location: gest_transferencias.php?l=1");
        exit;


    } //if($valido=="S"){

}
// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());
/*
$listot=intval($_GET['l']);
if ($listot==1){
    //traemos la tanda anterior
    $buscar="Select max(idtanda) as mayor
    from gest_transferencias where generado_por=$idusu and estado=2 and idempresa=$idempresa";

    $rsante=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    //echo $buscar;
    $tandante=intval($rsante->fields['mayor']);

    if ($tandante > 0){
        //cabecera p matriz
        $buscar="Select * from gest_transferencias where idtanda=$tandante";
        $rstandacabe=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));


    }


}
*/




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
function OpenPage(enlace,parametros,tipo,div,cargando){
	var parametros_array = URLToArray(parametros);
	//alert(parametros);
	$.ajax({
		data:  parametros_array,
		url:   enlace,
		type:  tipo,
		dataType: 'html',
		beforeSend: function () {
			$("#"+div).html("Cargando...");
		},
		success:  function (response) {
			$("#"+div).html(response);
		}
	});
}
function URLToArray(url) {
    var request = {};
    var pairs = url.substring(url.indexOf('?') + 1).split('&');
    for (var i = 0; i < pairs.length; i++) {
        if(!pairs[i])
            continue;
        var pair = pairs[i].split('=');
        request[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
     }
     return request;
}
function ArrayToURL(array) {
  var pairs = [];
  for (var key in array)
    if (array.hasOwnProperty(key))

      pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(array[key]));
  return pairs.join('&');
}
function comenzar(){
	//NUeva tandade transferencias
	var fechatr=document.getElementById('fechatrans').value;
	var origen=parseInt(document.getElementById('origen').value);
	var destino=parseInt(document.getElementById('destino').value);
	var errores='';
	if (fechatr==''){
		errores=errores+'Debe indicar fecha para transferencia. \n';
		
	}
	if (origen==0){
		errores=errores+'Debe indicar deposito de origen. \n';
		
	}
	if (destino==0){
		errores=errores+'Debe indicar deposito destino. \n';
		
	}
	if (origen==destino){
		errores=errores+'No se puede mover al mismo lugar. \n'	;
	}
	if (errores==''){
			document.getElementById('comenzart').submit();	
	} else {
		
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
	}
	
	
}
function enviar(){
	//controlamos cantidad a mover
	
	document.getElementById('cambiar').submit();
	
	
}
function buscar(){
	var producto=(document.getElementById('codigop').value);
	var productocod=(document.getElementById('codigoprod').value);
	var errores='';
	
	if (producto !='' || productocod!=''){
		document.getElementById('sc2').submit();
		
	} else {
		errores=errores+'Debe indicar producto a buscar.'	;
		
	}
	if (errores!=''){
		alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
		
	}
	
}

function addtmp(posicion,producto,tanda){
	var cantidad=document.getElementById('cantimov_'+posicion).value;
	var insu=producto;
	
	
	var parametros="insu="+insu+'&ca='+cantidad+'&tp=1&id='+tanda;
	//enlace='add_tmp_traslado_new.php';
	//OpenPage(enlace,parametros,'POST','tmprodusmov','pred');
	
	
	
	//setTimeout(function(){ abrecos(idpsele,cod); }, 500);
	var parametros = {
			"insu" : insu,
			"ca"   : cantidad,
			"tp"   : 1,
			"id" : <?php echo $idtanda ?>
	};
   $.ajax({
			data:  parametros,
			url:   'add_tmp_traslado_new.php',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
				$("#tmprodusmov").html('Cargando...');
			},
			success:  function (response) {
				$("#tmprodusmov").html(response);
				switchery_reactivar();
			}
	});
	
}
function addtmp_todo(tipo){
	//alert($("#tras_insumo").serialize());
   $.ajax({
			data:  $("#tras_insumo").serialize(),
			url:   'add_tmp_traslado_new.php',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
					$("#insumo_box").html("");
					$("#tmprodusmov").html('Cargando...');
			},
			success:  function (response) {
					$("#tmprodusmov").html(response);
					if(tipo == 2){
						$("#codigobarra").focus();
					}else{
						$("#codigoprod").focus();	
					}
					switchery_reactivar();
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
function chau(cual,tanda){
	if (cual !=''){	
		//enlace='add_tmp_traslado_new.php';
		//var parametros="cual="+cual+'&tp=3&id='+<?php echo $idtanda ?>;
		//OpenPage(enlace,parametros,'POST','tmprodusmov','pred');
		var parametros = {
				"cual" : cual,
				"tp"   : 3,
				"id" : <?php echo $idtanda ?>
		};
	   $.ajax({
				data:  parametros,
				url:   'add_tmp_traslado_new.php',
				type:  'post',
				dataType: 'html',
				beforeSend: function () {
					$("#tmprodusmov").html('Cargando...');
				},
				success:  function (response) {
					$("#tmprodusmov").html(response);
					switchery_reactivar();
				}
		});
		
	}
}
function terminar(tandafin){
	$("#cerrartrans").hide();
	var tfp=document.getElementById('ter').value;
	if (tfp !=''){
		document.getElementById('fin').submit();
	}
	
}
function imprematriz(pregunta_duplicar='S'){
	var texto = document.getElementById("ocidu").value;
	var duplic = 'N';
	if(pregunta_duplicar == 'S'){
		if(window.confirm('Imprimir con duplicado?')){
			var duplic = 'S';	
		}
	}
	var parametros = {
			"tk" : texto,
			"id" : <?php echo $idtanda ?>
	};
   $.ajax({
			data:  parametros,
			url:   'http://localhost/impresorweb/lcorden_compra.php',
			type:  'post',
			dataType: 'html',
			beforeSend: function () {
					$("#imprimeoc").html("Enviando impresion...");
			},
			crossDomain: true,
			success:  function (response) {
					//$("#impresion_box").html(response);	
					//si impresion es correcta marcar
					//var str = response;
					//var res = str.substr(0, 18);
					//;
					if(duplic == 'S'){
						imprematriz('N');
					}
					$("#imprimeoc").html('Impresion Enviada!');
					
			}
	});

	
	
	
}
function busca_insumo(valor){
	var n = valor.length;
	//alert(valor);
	if(n > 2){
		$("#codigoprod").val('');
	   var parametros = {
              "codigop" : valor,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_mover_stock_cuadro_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
                }
        });	
	}
}
function busca_insumo_cod(){
	   var valor = $("#codigoprod").val();
	  $("#codigop").val(''); 
	   var parametros = {
              "codigoprod" : valor,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_mover_stock_cuadro_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
						$("#codigoprod").val('');
						$(".insu_focus_1").focus();
                }
        });	
}
function busca_insumo_cbar(e){
	var codbar = $("#codigobarra").val();
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		var valor = $("#codigobarra").val();
		$("#codigop").val(''); 
		var parametros = {
		  "codigobarra" : valor,
		  "id" : <?php echo $idtanda ?>
		};
		$.ajax({
			data:  parametros,
			url:   'gest_mover_stock_cuadro_new.php',
			type:  'post',
			beforeSend: function () {
					$("#insumo_box").html("Cargando...");
			},
			success:  function (response) {
					$("#insumo_box").html(response);
					$("#codigobarra").val('');
					$(".insu_focus_1").focus();
			}
		});	
	}
}
function busca_insumo_cod_enter(e){
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		busca_insumo_cod();
	}
}
function agregaitem(e,tipo){
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		
		addtmp_todo(tipo);
	}
}
function busca_insumo_grup(valor){
	  $("#codigoprod").val('');
	  $("#codigop").val(''); 
	   var parametros = {
              "grupo" : valor,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'gest_mover_stock_cuadro_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
						$("#insumo_box").html(response);
                }
        });	
}
function recarga_tmp(){
	   var parametros = {
              "tp" : 0,
			  "id" : <?php echo $idtanda ?>
	   };
       $.ajax({
                data:  parametros,
                url:   'add_tmp_traslado_new.php',
                type:  'post',
                beforeSend: function () {
                        $("#tmprodusmov").html("Cargando...");
                },
                success:  function (response) {
						$("#tmprodusmov").html(response);
						switchery_reactivar();
                }
        });	
}
function genera_auto(){
	if(window.confirm('Se generara automaticamente en base al stock ideal y stock minimo. esta seguro?')){
		$("#gen_auto").hide();
		document.location.href='gest_transferencias_gen.php?id=<?php echo $idtanda; ?>';
	}
}
function genera_auto_ped(){
	document.location.href='gest_transferencias_gen_ped.php?id=<?php echo $idtanda; ?>';
}
function alerta_modal(titulo,mensaje){
	$('#modal_ventana').modal('show');
	$("#modal_titulo").html(titulo);
	$("#modal_cuerpo").html(mensaje);
	//$("#modal_pie").html(html_botones);
	/*
	Otros usos:
	$('#modal_ventana').modal('show'); // abrir
	$('#modal_ventana').modal('hide'); // cerrar
	*/
	
}
function ventana(){
	var titulo = $("#titulo").val();
	var mensaje = $("#mensaje").val();
	alerta_modal(titulo,mensaje);
}
function alertar(titulo,error,tipo,boton){
	//swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	alerta_modal(titulo,error);
}
function registrar_cambio_cant(unicaser){
	var cantidad_modif = $("#cantidad_modif").val();
	var lote_modif = $("#lote_modif").val();
	var vto_modif = $("#vto_modif").val();
	
	 var parametros = {
                "id"         : unicaser,
				"MM_update"  : "form1",
				"cantidad"   : cantidad_modif,
				"lote"  	 : lote_modif,
				"vto"  		 : vto_modif
        };
       $.ajax({
                data:  parametros,
                url:   'traslado_edit_cant.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop1").html("<br /><br /><br />Registrando...<br /><br /><br />");
                },
                success:  function (response) {
						//$("#pop1").html(response);
						if(response == 'OK'){
							document.location.href='gest_transferencias_det.php?id=<?php echo $idtanda ?>';
						}else{
							$("#pop1").html(response);
						}
                }
        });
}
function asignardt(cual){
	
	var parametros = {
			"id" : cual
	};
	$.ajax({
		data:  parametros,
		url:   'traslado_edit_cant.php',
		type:  'post',
		beforeSend: function () {
			$('#modal_ventana').modal('show');
			$("#modal_titulo").html('Editar datos del Articulo a trasladar');
			$("#modal_cuerpo").html('Cargando...');
		},
		success:  function (response) {
			$("#modal_cuerpo").html(response);
		}
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
function confirma(unicaser){
	var direccionurl='confirma_registra.php';	
	//alert(direccion);
	var parametros = {
	  "unicaser"  : unicaser,
	  "idtanda"  : <?php echo $idtanda; ?>,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#conf_"+unicaser).html('Cargando...');	
		},
		success:  function (response, textStatus, xhr) {
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					$("#conf_"+unicaser).html(obj.html_checkbox);
					var confirmado = obj.confirmado;
					var elem = $("#confirma_"+unicaser);
					//alert(elem);
					var idbox = "confirma_"+unicaser;
					switchery_reactivar_uno(idbox);
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
function switchery_reactivar_uno(idbox){
	var elems = document.querySelector('#'+idbox);
	var switchery = new Switchery(elems);

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
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Traslado de Stock N&ordm; <?php echo $idtanda; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="gest_transferencias.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="#" onmouseup="genera_auto();" id="gen_auto" class="btn btn-sm btn-default"><span class="fa fa-magic"></span> Generacion Automatica</a>
<a href="#" onmouseup="genera_auto_ped();" class="btn btn-sm btn-default"><span class="fa fa-table"></span> Generar en base a Pedido</a>

</p>
<hr />
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

			<th align="center">Fecha</th>
			<th align="center">Deposito Origen</th>
			<th align="center">Deposito Destino</th>
		</tr>
	  </thead>
	  <tbody>
		<tr>
			<td align="center"><?php echo date("d/m/Y", strtotime($fecha_transferencia)); ?></td>
			<td align="center"><?php echo antixss($orichar); ?></td>
			<td align="center"><?php echo antixss($deschar); ?></td>
		</tr>
	  </tbody>
    </table>
</div>
<div class="clearfix"></div>
<br />
<?php if ($estado == 1) {?>
    <div class="form-group" id="cerrartrans">
        <div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-4">
	   <button type="button" class="btn btn-success" onclick="terminar(<?php echo $idtanda?>)"><span class="fa fa-check-square-o"></span> Finalizar Transferencia</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_transferencias.php'"><span class="fa fa-ban"></span> Continuar mas tarde</button>
	   <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
        </div>
    </div>
    <div class="clearfix"></div>
<br />
    <form id="fin" name="fin" action="gest_transferencias_det.php?id=<?php echo $idtanda ?>" method="post">
    	<input type="hidden" name="ter" id="ter" value="<?php echo $idtanda?>"  />
    <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
    </form>
<?php }?>

<hr />
 <form id="sc2" name="sc2" action="gest_transferencias_det.php?id=<?php echo $idtanda ?>" method="post" >
 
 <div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codigoprod" id="codigoprod" value="<?php  if (isset($_POST['codigoprod'])) {
	    echo htmlentities($_POST['codigoprod']);
	} ?>" placeholder="Codigo Articulo" class="form-control"  onkeyup="busca_insumo_cod_enter(event);" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Articulo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="app" id="app" value="<?php  if (isset($_POST['codigop'])) {
	    echo htmlentities($_POST['codigop']);
	}?>" placeholder="Articulo" class="form-control" onkeyup="busca_insumo(this.value);" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Grupo de Stock </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
 <select name="grupo"  id="grupo" class="form-control" onchange="busca_insumo_grup(this.value);">
                <option value="0" selected="selected">Seleccionar</option>
                <?php
                $buscar = "Select * from grupo_insumos where idempresa=$idempresa and estado=1 order by nombre asc";
$gr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

while (!$gr->EOF) {?>
                <option value="<?php echo $gr->fields['idgrupoinsu']?>" <?php if ($gr->fields['idgrupoinsu'] == $_GET['gr']) { ?>selected="selected"<?php } ?>><?php echo trim($gr->fields['nombre']) ?></option>
                <?php $gr->MoveNext();
}?>
              </select>                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo de Barras </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codigobarra" id="codigobarra" value="<?php  if (isset($_POST['codigobar'])) {
	    echo htmlentities($_POST['codigobar']);
	}?>" placeholder="Codigo de Barras" class="form-control" onkeyup="busca_insumo_cbar(event);"   />                    
	</div>
</div>
 <!--
 <div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="button" class="btn btn-default" onclick="busca_insumo_cod();"><span class="fa fa-search"></span> Buscar</button>

        </div>
    </div>
-->
    	</form>
<div class="clearfix"></div>
<hr />
    <div align="center"><?php echo $errorcantidaad;?></div>
    	<div align="center" id="insumo_box"></div>
        <hr />
    <div id="tmprodusmov" align="center">
    <?php require_once('add_tmp_traslado_new.php');?>
    
    </div>
    
    <?php


$tanda = $tandante;
if ($tanda > 0) {
    //traemos la tanda anterior
    $buscar = "Select *,(select usuario from usuarios where idusu=gest_transferencias.generado_por) as responsable,
	(Select descripcion from gest_depositos where iddeposito=gest_transferencias.origen) as origenc,
	(Select descripcion from gest_depositos where iddeposito=gest_transferencias.destino) as destinoc
	 from gest_transferencias where idtanda=$tanda";
    $rscabe = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $fechatrans = date("d/m/Y", strtotime($rscabe->fields['fecha_transferencia']));

    $idgenera = intval($rscabe->fields['generado_por']);
    $origen = trim($rscabe->fields['origenc']);
    $destino = trim($rscabe->fields['destinoc']);
    $dst = intval($rscabe->fields['destino']);
    $or = intval($rscabe->fields['origen']);
    $responsable = ($rscabe->fields['responsable']);

    //cuerpo
    $buscar = "select *,(select descripcion from insumos_lista where idinsumo=gest_depositos_mov.idproducto) as descripcion
	 from gest_depositos_mov where idtanda=$tanda and idempresa=$idempresa
	 order by descripcion asc";
    $rscuerpo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    if ($mostrarcosto == 'S') {
        $cabezacosto = '|Costo Gs';
        $cabezasub = '|Subtotal Gs';

    }

    $texto = "********************************************************************************
  $nombreempresa - Traslado N&deg; $tanda 
********************************************************************************
Fecha Traslado   : $fechatrans
Deposito Origen  : $origen
Deposito Destino : $destino
--------------------------------------------------------------------------------
Codigo |Producto               |Cantidad     $cabezacosto        $cabezasub
--------------------------------------------------------------------------------
";
    $to = 0;
    while (!$rscuerpo->EOF) {
        //Buscamos el precio de compras
        $pp = antisqlinyeccion($rscuerpo->fields['idproducto'], 'texto');

        $buscar = "Select costogs from gest_depositos_stock
		 where iddeposito=$dst and idproducto=$pp and disponible > 0 order by idseriecostos asc";
        $buscar = "Select precio_costo as costogs from costo_productos
             where id_producto=$pp and precio_costo > 0 order by idseriepkcos desc limit 1";
        $rscos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $costo = floatval($rscos->fields['costogs']);


        $subt = floatval($rscuerpo->fields['cantidad']) * $costo;
        $to = $to + $subt;
        $texto .= agregaespacio($rscuerpo->fields['idproducto'], 7)."|".agregaespacio($rscuerpo->fields['descripcion'], 23)."|".agregaespacio(formatomoneda($rscuerpo->fields['cantidad'], 4, 'N'), 13);
        if ($mostrarcosto == 'S') {
            $texto .= "|".agregaespacio(formatomoneda($costo), 16)."|".agregaespacio(formatomoneda($subt), 16);

        }
        $texto .= "".$saltolinea;
        $rscuerpo->MoveNext();
    }
    $texto .= "------------------------------------------------------------------------";
    if ($mostrarcosto == 'S') {

        $texto = $texto."	
Total Enviado Gs: ".formatomoneda($to);
    }
    $texto .= "	
Encargado Compras:..................... Firma:.................................
Recibido por: ......................... Firma: ................................
Observaciones: ................................................................
Responsable :".$responsable." Impreso el  :".date("d/m/Y H:i:s")."
";
    /*Fecha: ....../...../.....               Hora: ..... : .....*/
    $ah = date("YmdHis");
    $hh = rand();

    $final = $texto;

    $textooc = $final;
    ?>
<div style="width:500px; margin:0px auto;" id="imprimeoc"> <strong>Traslado:</strong><br /><pre><?php echo $textooc; ?></pre></div>
    <textarea name="ocidu" id="ocidu" style="display:none; width:800px; height:500px;"><?php echo $textooc; ?><?php //echo trim($textooc);?></textarea>
<?php } ?> 





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
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
						...
                        </div>
                        <div class="modal-footer" id="modal_pie">
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
<link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
<script src="vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
  </body>
</html>
