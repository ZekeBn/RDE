<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

// traer del cookie de asignacion para notas de credito igual que en ventas
if ($facturador_electronico == 'S') {
    echo "Modulo en desarrollo para facturadores electronicos.";
    exit;
}


$nota_pexp = "001";
$nota_suc = "001";
if (intval($factura_suc) == 0) {
    $nota_suc = "001";
} else {
    $nota_suc = $factura_suc;
}
if (intval($factura_pexp) == 0) {
    $nota_pexp = "001";
} else {
    $nota_pexp = $factura_pexp;
}



function timbrado_tanda_nc($nota_suc, $nota_pexp, $numnota = 0, $fec_nota = '')
{
    global  $conexion;
    global $ahora;
    $ahorad = date("Y-m-d", strtotime($ahora));
    if ($fec_nota == '') {
        $fec_nota = $ahorad;
    }

    if ($numnota > 0) {
        $whereadd = "
		and $numnota >= inicio
		and $numnota <= fin
		";
    }
    $consulta = "
	SELECT * 
	FROM facturas 
	where 
	estado = 'A'
	and valido_hasta >= '$fec_nota'
	and valido_desde <= '$fec_nota'
	and sucursal = $nota_suc
	and punto_expedicion = $nota_pexp
	and idtipodocutimbrado = 2
	$whereadd
	";
    //echo $consulta;
    //exit;
    $rstimbrado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $res = [
        'idtanda' => $rstimbrado->fields['idtanda'],
        'timbrado' => $rstimbrado->fields['timbrado'],
        'valido_hasta' => $rstimbrado->fields['valido_hasta'],
        'valido_desde' => $rstimbrado->fields['valido_desde'],
        'inicio' => $rstimbrado->fields['inicio'],
        'fin' => $rstimbrado->fields['fin'],
        'nota_suc' => $rstimbrado->fields['sucursal'],
        'nota_pexp' => $rstimbrado->fields['punto_expedicion']

    ];
    return $res;

}

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

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


    // recibe parametros
    $idmotivo = antisqlinyeccion($_POST['idmotivo'], "int");
    $idsucursal = antisqlinyeccion($_POST['idsucursal'], "int");
    $fecha_nota = antisqlinyeccion($_POST['fecha_nota'], "text");
    $idcliente = antisqlinyeccion($_POST['idcliente'], "int");
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $estado = 1;
    $anulado_por = antisqlinyeccion($_POST['anulado_por'], "int");
    $anulado_el = antisqlinyeccion($_POST['anulado_el'], "text");
    $tiponc = antisqlinyeccion($_POST['tiponc'], "int");
    $idcajaaplicar = antisqlinyeccion($_POST['idcajaaplicar'], "int");
    $idcuentaclientepagcab = antisqlinyeccion($_POST['idcuentaclientepagcab'], "int");
    $numero = antisqlinyeccion($_POST['numero'], "text");
    $observacion = antisqlinyeccion($_POST['obs'], "text");



    if (intval($_POST['idmotivo']) == 0) {
        $valido = "N";
        $errores .= " - El campo idmotivo no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['idsucursal']) == 0) {
        $valido = "N";
        $errores .= " - El campo idsucursal no puede ser cero o nulo.<br />";
    }

    if (trim($_POST['fecha_nota']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_nota no puede estar vacio.<br />";
    }
    if (intval($_POST['numero']) == 0) {
        $valido = "N";
        $errores .= " - El campo nota de credito no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['idcliente']) == 0) {
        $valido = "N";
        $errores .= " - El campo cliente no puede ser cero o nulo.<br />";
    }


    // valida timbrado
    $nota_cred = explode('-', $_POST['numero']);
    $nota_suc = intval($nota_cred[0]);
    $nota_pexp = intval($nota_cred[1]);
    $nota_num = intval($nota_cred[2]);

    $datos_timbrado = timbrado_tanda_nc($nota_suc, $nota_pexp, $nota_num, $_POST['fecha_nota']);
    $timbrado = intval($datos_timbrado['timbrado']);
    $sutim = intval($datos_timbrado['nota_suc']);
    $petim = intval($datos_timbrado['nota_pexp']);
    $iniciatim = intval($datos_timbrado['inicio']);
    $chautim = intval($datos_timbrado['fin']);
    $timb_valido_desde = $datos_timbrado['valido_desde'];
    $timb_valido_hasta = $datos_timbrado['valido_hasta'];
    $idtimbrado = intval($datos_timbrado['idtanda']);
    $notacredito_completa = agregacero($sutim, 3).'-'.agregacero($petim, 3).'-'.agregacero($nota_num, 7);
    if (intval($idtimbrado) == 0) {
        $errores .= "-No existe tanda de timbrado para el punto de expedicion seleccionado.";
        $valido = "N";
    }

    $consulta = "
	select * from cliente where idcliente = $idcliente limit 1
	";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social = $rscli->fields['razon_social'];
    $ruc = $rscli->fields['ruc'];


    $consulta = "
	select * 
	from 
	nota_credito_cabeza 
	where 
	numero = '$notacredito_completa' 
	and idtandatimbrado = $idtimbrado
	and estado <> 6
	limit 1
	";
    $rscval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rscval->fields['idnotacred'] > 0) {
        $valido = "N";
        $errores .= "-Ya existe otra nota de credito con el mismo numero $notacredito_numero.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into nota_credito_cabeza
		(idmotivo, idsucursal, fecha_nota, idtandatimbrado, timbrado, timb_valido_desde, timb_valido_hasta, numero, idcliente, razon_social, ruc, registrado_por, registrado_el, estado, anulado_por, anulado_el, tiponc, idcajaaplicar, idcuentaclientepagcab,observaciones)
		values
		($idmotivo, $idsucursal, $fecha_nota, $idtimbrado, $timbrado, '$timb_valido_desde', '$timb_valido_hasta', '$notacredito_completa', $idcliente, '$razon_social', '$ruc', $registrado_por, $registrado_el, $estado, $anulado_por, $anulado_el, 2, 0, 0,$observacion)
		";
        // echo $consulta;exit;
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		select * from nota_credito_cabeza where registrado_por = $idusu order by idnotacred desc limit 1
		";
        $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idnotacred = intval($rsmax->fields['idnotacred']);
        $consulta = "
		update lastcomprobantes set numero_nc = $nota_num where idsuc=$nota_suc and pe=$nota_pexp
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: nota_credito_cuerpo.php?id=".$idnotacred);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());





?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
function alerta_modal(titulo,mensaje){
	$('#dialogobox').modal('show');
	$("#myModalLabel").html(titulo);
	$("#modal_cuerpo").html(mensaje);

	
}
function buscar_cliente(){

	
	//var monto_abonar = $("#idcta_"+idcta).val();
	var direccionurl='nota_credito_cabeza_buscli.php';	
	var parametros = {
	  "MM_insert"    : "form1"
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#myModalLabel").html('Busqueda de Clientes');
			$("#modal_cuerpo").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			$("#myModalLabel").html('Busqueda de Clientes');
			$("#modal_cuerpo").html(response);
			$('#dialogobox').modal('show');
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

function buscar_cliente_rz(razon_social){


	var direccionurl='nota_credito_cabeza_buscli_res.php';	
	var parametros = {
	  "razon_social"    : razon_social
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#myModalLabel").html('Busqueda de Clientes');
			$("#result_clie").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			//$("#myModalLabel").html('Busqueda de Clientes');
			$("#result_clie").html(response);
			//$('#dialogobox').modal('show');
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
function buscar_cliente_ruc(ruc){


	var direccionurl='nota_credito_cabeza_buscli_res.php';	
	var parametros = {
	  "ruc"    : ruc
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#myModalLabel").html('Busqueda de Clientes');
			$("#result_clie").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			//$("#myModalLabel").html('Busqueda de Clientes');
			$("#result_clie").html(response);
			//$('#dialogobox').modal('show');
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
function seleccionar_cliente(idcliente,ruc,razon_social){
	$("#idcliente").val(idcliente);
	$("#cliente").val(ruc+' | '+razon_social);
	$('#dialogobox').modal('hide');
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
                    <h2>Nota de Credito a Clientes</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
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
<form id="form1" name="form1" method="post" action="">
<?php

//traemos la mayor NC de lc
$consulta = "Select max(numero_nc) as mayor  from lastcomprobantes where idsuc=$nota_suc and pe=$nota_pexp";
$rslast = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$prox_nota = intval($rslast->fields['mayor']) + 1;
$nota_numero = agregacero($nota_suc, 3).'-'.agregacero($nota_pexp, 3).'-'.agregacero($prox_nota, 7);

?>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nota Numero </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="numero" id="numero" value="<?php  if (isset($_POST['numero'])) {
	    echo htmlentities($_POST['numero']);
	} else {
	    echo htmlentities($nota_numero);
	}?>" placeholder="ej:001-001-0000001" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha nota </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_nota" id="fecha_nota" value="<?php  if (isset($_POST['fecha_nota'])) {
	    echo htmlentities($_POST['fecha_nota']);
	} else {
	    echo date("Y-m-d");
	}?>" placeholder="Fecha nota" class="form-control"  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motivo * [&nbsp;<a href='javascript:void(0);' title="Agregar" onclick="agregar_motivo();"><span class="fa fa-plus"></span>&nbsp;</a>]</label>
	<div class="col-md-9 col-sm-9 col-xs-12" id="motivoslistado">
	<?php require_once("nc_mini_motivos.php"); ?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idsucursal'])) {
    $value_selected = htmlentities($_POST['idsucursal']);
} else {
    $value_selected = htmlentities($rs->fields['idsucursal']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucursal',
    'id_campo' => 'idsucursal',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>




<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="cliente" id="cliente" value="<?php  if (isset($_POST['cliente'])) {
	    echo antixss($_POST['cliente']);
	} else {
	    echo antixss($rs->fields['cliente']);
	}?>" placeholder="Cliente" class="form-control" readonly onMouseUp="buscar_cliente();" />                    
    <input  type="hidden" name="idcliente" id="idcliente" value="<?php  if (isset($_POST['idcliente'])) {
        echo intval($_POST['idcliente']);
    } else {
        echo intval($rs->fields['idcliente']);
    }?>">
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Observaciones </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="obs" id="obs" value="<?php  if (isset($_POST['obs'])) {
	    echo antixss($_POST['obs']);
	} else {
	    echo antixss($rs->fields['observaciones']);
	}?>" placeholder="" class="form-control"   />                    
   
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='nota_credito_cabeza.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
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
                          <h4 class="modal-title" id="myModalLabel"></h4>
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
              <script>
				function agregar_motivo(){
					$("#myModalLabel").html("Agregar Motivos");
					var parametros = {
						
					};
					$.ajax({		  
						data:  parametros,
						url:   'nc_mini_agrega_motivos.php',
						type:  'post',
						cache: false,
						timeout: 20000,  // I chose 3 secs for kicks: 3000
						crossDomain: true,
						beforeSend: function () {	
							$("#modal_cuerpo").html('');	
							
						},
						success:  function (response) {
							$("#modal_cuerpo").html(response);	
							$("#dialogobox").modal("show");
						}
					});
					
					
				}
				function registrar(){
					var errores="";
					var nombre=$("#descripcion").val();
					if (nombre==''){
						errores=errores+"Debe indicar un nombre para el motivo.";
					}
					if(errores==''){
						var parametros = {
							"agregar" : 1,
							"nombre"  : nombre
						};
						$.ajax({		  
							data:  parametros,
							url:   'nc_mini_motivos.php',
							type:  'post',
							cache: false,
							timeout: 20000,  // I chose 3 secs for kicks: 3000
							crossDomain: true,
							beforeSend: function () {	
								$("#reca").html('');	
								
							},
							success:  function (response) {
								$("#reca").html(response);	
								if(response=='OK'){
									recargar();
								}
								//$("#dialogobox").modal("show");
							}
						});
					} else {
						alert(errores);
					}
					
				}
				function recargar(){
					var parametros = {
						
						};
					$.ajax({		  
							data:  parametros,
							url:   'nc_mini_motivos.php',
							type:  'post',
							cache: false,
							timeout: 20000,  // I chose 3 secs for kicks: 3000
							crossDomain: true,
							beforeSend: function () {	
								$("#motivoslistado").html('');	
								
							},
							success:  function (response) {
								$("#motivoslistado").html(response);	
								$("#dialogobox").modal("hide");
							}
						});
				}
				function eliminar_motivo(idmotivochau){
					
					
					if(idmotivochau!=''){
						var parametros = {
							"eliminar" : idmotivochau
						};
						$.ajax({		  
							data:  parametros,
							url:   'nc_mini_motivos.php',
							type:  'post',
							cache: false,
							timeout: 20000,  // I chose 3 secs for kicks: 3000
							crossDomain: true,
							beforeSend: function () {	
								$("#reca").html('');	
								
							},
							success:  function (response) {
								$("#reca").html(response);	
								if(response=='OK'){
									recargar();
								}
								//$("#dialogobox").modal("show");
							}
						});
					} else {
						alert(errores);
					}
					
				}
			  </script>
              
              
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
