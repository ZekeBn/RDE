<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");



require_once("preferencias_proveedores.php");

//buscando moneda nacional
$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];
// TODO: ERROR MONEDA NACIONAL NO ENCONTRADA


//buscando pais defecto
$consulta = "SELECT idpais FROM paises_propio WHERE defecto=1 ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_pais_nacional = $rs_guarani->fields["idpais"];
// TODO: ERROR PAIS POR DFECTO NO ENCONTRADO
//buscando origenes importacion y locales
$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE UPPER(tipo)='LOCAL'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_local = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_local == 0) {
    $errores = "- Por favor cree el Origen LOCAL.<br />";
}
$consulta = "SELECT idtipo_origen FROM tipo_origen WHERE  UPPER(tipo)='IMPORTACION'";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_tipo_origen_importacion = intval($rs_guarani->fields["idtipo_origen"]);
if ($id_tipo_origen_importacion == 0) {
    $errores = "- Por favor cree el Origen IMPORTACON.<br />";
}

//////////////////////////////

$idproveedor = intval($_GET['id']);
if ($idproveedor == 0) {
    header("location: gest_proveedores.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from proveedores 
where 
idproveedor = $idproveedor
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idproveedor = intval($rs->fields['idproveedor']);
if ($idproveedor == 0) {
    header("location: gest_proveedores.php");
    exit;
}

// echo json_encode(($rs->fields['acuerdo_comercial']) =="S");exit;



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


    // recibe parametros
    $idempresa = antisqlinyeccion(1, "int");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $sucursal = antisqlinyeccion(1, "float");
    $comentarios = antisqlinyeccion($_POST['comentarios'], "text");
    $web = antisqlinyeccion($_POST['web'], "text");
    $telefono = antisqlinyeccion($_POST['telefono'], "text");
    $estado = 1;
    $email = antisqlinyeccion($_POST['email'], "text");
    $contacto = antisqlinyeccion($_POST['contacto'], "text");
    $area = antisqlinyeccion($_POST['area'], "text");
    $email_conta = antisqlinyeccion($_POST['email_conta'], "text");
    $borrable = antisqlinyeccion('S', "text");
    $diasvence = antisqlinyeccion($_POST['diasvence'], "int");
    $dias_entrega = antisqlinyeccion(intval($_POST['dias_entrega']), "int");
    $incrementa = antisqlinyeccion($_POST['incrementa'], "text");
    $acuerdo_comercial = antisqlinyeccion($_POST['acuerdo_comercial'], "text");
    $acuerdo_comercial_coment = antisqlinyeccion($_POST['acuerdo_comercial_coment'], "text");
    $archivo_acuerdo_comercial = $_FILES['archivo_acuerdo_comercial'];
    $acuerdo_comercial_desde = antisqlinyeccion($_POST['ac_desde'], "date");
    $acuerdo_comercial_hasta = antisqlinyeccion($_POST['ac_hasta'], "date");
    $persona = antisqlinyeccion($_POST['persona'], "int");
    ////
    $fantasia = antisqlinyeccion($_POST['fantasia'], "text");
    $cuenta_cte_mercaderia = antisqlinyeccion(intval($_POST['cuenta_cte_mercaderia']), "text");
    $cuenta_cte_deuda = antisqlinyeccion(intval($_POST['cuenta_cte_deuda']), "text");
    $idpais = antisqlinyeccion(intval($_POST['idpais']), "int");
    $idmoneda = antisqlinyeccion(intval($_POST['idmoneda']), "int");
    $agente_retencion = antisqlinyeccion($_POST['agente_retencion'], "text");
    $idtipo_servicio = antisqlinyeccion(intval($_POST['idtipo_servicio']), "int");
    $idtipo_origen = antisqlinyeccion(intval($_POST['idtipo_origen']), "int");
    $idtipocompra = antisqlinyeccion(intval($_POST['idtipocompra']), "int");
    $actualizado_por = $idusu;
    $actualizado_el = antisqlinyeccion($ahora, "text");



    $parametros_array = [
        "idproveedor" => $idproveedor,
        "idempresa" => $idempresa,
        "ruc" => $ruc,
        "nombre" => $nombre,
        "fantasia" => $fantasia,
        "direccion" => $direccion,
        "sucursal" => $sucursal,
        "comentarios" => $comentarios,
        "web" => $web,
        "telefono" => $telefono,
        "estado" => $estado,
        "email" => $email,
        "contacto" => $contacto,
        "area" => $area,
        "email_conta" => $email_conta,
        "borrable" => $borrable,
        "diasvence" => $diasvence,
        "dias_entrega" => $dias_entrega,
        "incrementa" => $incrementa,
        "acuerdo_comercial" => $acuerdo_comercial,
        "acuerdo_comercial_coment" => $acuerdo_comercial_coment,
        "archivo_acuerdo_comercial" => $archivo_acuerdo_comercial,
        "acuerdo_comercial_desde" => $acuerdo_comercial_desde,
        "acuerdo_comercial_hasta" => $acuerdo_comercial_hasta,
        "persona" => $persona,
        "idpais" => $idpais, // ya esta
        "idmoneda" => $idmoneda,
        "agente_retencion" => $agente_retencion,
        "idtipo_servicio" => $idtipo_servicio,
        "idtipo_origen" => $idtipo_origen, //ya esta
        "idtipocompra" => $idtipocompra,
        "cuenta_cte_mercaderia" => $cuenta_cte_mercaderia,
        "cuenta_cte_deuda" => $cuenta_cte_deuda,
        "actualizado_por" => $actualizado_por,
        "actualizado_el" => $actualizado_el,
        "form_completo" => 1,
    ];
    if ($archivo_acuerdo_comercial['name'] != "") {

        if (is_dir("../gfx/proveedores/acuerdos_comercial")) {

        } else {
            //creamos
            mkdir("../gfx/proveedores", "0777");
            mkdir("../gfx/proveedores/acuerdos_comercial", "0777");

        }
        $date_now = date("YmdHis");
        $extension_archivo = end(explode('.', $archivo_acuerdo_comercial['name']));
        $nombre_archivo = 'prv_'.$date_now.'.'.$extension_archivo;
        $dest_file = "../gfx/proveedores/acuerdos_comercial/$idproveedor/".$nombre_archivo;
        $directorio = "../gfx/proveedores/acuerdos_comercial/$idproveedor";
        $parametros_array["dest_file"] = $dest_file;
        $parametros_array["directorio"] = $directorio;
    }

    // TODO:verificar y cambiar
    if (trim($_POST['ruc']) == '') {
        $valido = "N";
        $errores .= " - El campo ruc no puede estar vacio.<br />";
    }
    if (trim($_POST['nombre']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre no puede estar vacio.<br />";
    }

    if (trim($_POST['diasvence']) == '') {
        $valido = "N";
        $errores .= " - El campo dias de credito no puede estar vacio.<br />";
    }
    if ($proveedores_sin_factura == "N" && ((trim($persona) == "" || $persona == "NULL"))) {
        $valido = "N";
        $errores .= " - El campo Persona , debe ser completado indicando si es persona física o jurídica..<br>".$saltolinea;
    }
    if (trim($_POST['incrementa']) == '' && $proveedores_sin_factura == "S") {
        $valido = "N";
        $errores .= " - El campo sin factura no puede estar vacio.<br />";
    }
    if (trim($_POST['acuerdo_comercial']) == '') {
        $valido = "N";
        $errores .= " - El campo acuerdo comercial no puede estar vacio.<br />";
    }


    if ((trim($_POST['acuerdo_comercial_coment']) != '') && (trim($_POST['acuerdo_comercial']) != 'S')) {
        $valido = "N";
        $errores .= " - El campo acuerdo comercial debe ser si, cuando hay un detalle sobre el acuerdo.<br />";
    }
    $archivo = $parametros_array['archivo_acuerdo_comercial'];
    $acuerdo_comercial = $parametros_array['acuerdo_comercial'];
    $acuerdo_comercial_desde = $parametros_array['acuerdo_comercial_desde'];
    $acuerdo_comercial_hasta = $parametros_array['acuerdo_comercial_hasta'];
    if ($proveedores_acuerdos_comerciales_archivo == "S") {
        $tamanoMaximo = 900 * 1024;
        $acuerdo_comercial = str_replace("'", "", $acuerdo_comercial);
        $dest_file = $parametros_array["dest_file"];
        if (($archivo['name'] != 'NULL' && $archivo['name'] != '')) {
            $extension = end(explode('.', $archivo['name']));
            if ($archivo['size'] >= $tamanoMaximo) {
                $valido = "N";
                $errores .= "El archivo .pdf,.jpg o .jpeg no puede pesar mas de 900KB peso actual=".($archivo['size'] / 1024)."KB.<br />";
            }
            $type = str_replace("'", "", $archivo['type']);
            if ($type != "application/pdf" && $extension != "jpg" && $extension != "jpeg") {
                $valido = "N";
                $errores .= "- El archivo debe ser .pdf,.jpg o .jpeg.</br>";
            }
        }

        if (($archivo['name'] != 'NULL' && $archivo['name'] != '') && $acuerdo_comercial != 'S') {
            $valido = "N";
            $errores .= " - El campo Acuerdo Comercial debe ser si, cuando hay un archivo del acuerdo comercial sobre el acuerdo.<br />";
        }
        if (($acuerdo_comercial_desde == 'NULL' or $acuerdo_comercial_desde == '') && $acuerdo_comercial == 'S') {
            $valido = "N";
            $errores .= " - El campo Acuerdo Comercial Fecha Desde debe ser completado.<br />";
        }
        if (($acuerdo_comercial_hasta == 'NULL' or $acuerdo_comercial_hasta == '') && $acuerdo_comercial == 'S') {
            $valido = "N";
            $errores .= " - El campo Acuerdo Comercial Fecha Hasta debe ser completado.<br />";
        }

        if (isset($dest_file) && file_exists($dest_file)) {
            $valido = "N";
            $errores .= " - El archivo ya existe ".$dest_file.".</br>";
        }
    }



    $consulta = "
	select * from proveedores where estado = 1 and ruc = $ruc and idproveedor <> $idproveedor limit 1;
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idproveedor'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otro proveedor registrado con el mismo ruc.<br />";
    }

    $consulta = "
	select * from proveedores where estado = 1 and nombre = $nombre and idproveedor <> $idproveedor limit 1;
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idproveedor'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otro proveedor registrado con la misma razon social.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {


        $res = update_proveedor($parametros_array);//idproveedor

        header("location: gest_proveedores.php");
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
		function editar_pais_moneda(event){
			event.preventDefault();
			var parametros_array = {
					"idpais"					: $("#moneda_pais #idpais").val(), 
					"idmoneda"					: $("#moneda_pais #idmoneda").val(),
					"agregar_pais"				: 1
				};
			$.ajax({		  
				data:  parametros_array,
				url:   'paises_dropdown.php',
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 5000
				crossDomain: true,
				beforeSend: function () {
				$("#submitEditarPais").text('Cargando...');
				},
				success:  function (response) {
					$("#dropdown_pais").html(response);
					$("#form1 #idmoneda").val($("#moneda_pais #idmoneda").val());
					cerrar_detalles_pais();
				},
				error: function(jqXHR, textStatus, errorThrown) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
				}
				}).fail( function( jqXHR, textStatus, errorThrown ) {
					errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
				});
		}
		function cerrar_detalles_pais(){
			$('#form1').removeClass('hide');
			$("#form1").addClass('show');
			$('#moneda_pais').removeClass('show');
			$("#moneda_pais").addClass('hide');
		}
		function detalles_pais(){
			$('#form1').removeClass('show');
			$("#form1").addClass('hide');
			$('#moneda_pais').removeClass('hide');
			
			$("#moneda_pais #idpais").val($("#form1 #idpais").val());
			$("#moneda_pais").addClass('show');
			
		}
		function cerrar_errores_proveedor(event){
			event.preventDefault();
			$('#boxErroresProveedor').removeClass('show');
			$('#boxErroresProveedor').addClass('hide');
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
		function verificar_pais(selectElement){
			const selectedOption = selectElement.options[selectElement.selectedIndex];
			//seleccion de origen local o importacion 
			console.log(<?php echo $id_tipo_origen_importacion; ?>);
			if(selectedOption.value==<?php echo $id_pais_nacional; ?>) {
				document.getElementById("idtipo_origen").value = <?php echo $id_tipo_origen_local; ?>;
			}else{
				document.getElementById("idtipo_origen").value = <?php echo $id_tipo_origen_importacion; ?>;

			}
			const idMoneda = selectedOption.dataset.hiddenValue;
			if (idMoneda){
				$("#idmoneda").val(idMoneda);
			}else{
				alerta("info","- El país seleccionado no cuenta con una moneda asociada. Se establecerá la moneda nacional como opción predeterminada.<br> Si lo deseas, puedes asignar manualmente una moneda haciendo uso del botón en forma de lupa ubicado junto al campo de ingreso del país.<br>","Alerta");
				$("#idmoneda").val(<?php echo $id_moneda_nacional ?>);
				
			}
		}
		function IsJsonString(str) {
			try {
				JSON.parse(str);
			} catch (e) {
				return false;
			}
			return true;
		}
		function carga_ruc_h(){
			var vruc = $("#ruc").val();
			var txtbusca="Buscando...";
			if(txtbusca != vruc){
				var parametros = {
						"ruc" : vruc
				};
				$.ajax({
						data:  parametros,
						url:   'ruc_extrae_prov.php',
						type:  'post',
						beforeSend: function () {
							$("#ruc").val(txtbusca);
						},
						success:  function (response) {
							if(IsJsonString(response)){
								var obj = jQuery.parseJSON(response);
								//alert(obj.error);
								if(obj.error == ''){
									var new_ruc = obj.ruc;
									var new_rz = obj.razon_social;
									var new_nom = obj.nombre_ruc;
									var new_ape = obj.apellido_ruc;
									var idcli = obj.idcliente;
									$("#ruc").val(new_ruc);
									$("#nombre").val(new_rz);
									//$("#nombres").val(new_nom);
									//$("#apellidos").val(new_ape);
									//if(parseInt(idcli)>0){
										//nclie(tipocobro,idpedido);
										//selecciona_cliente(idcli,tipocobro,idpedido);
									//}
								}else{
									$("#ruc").val(vruc);
									$("#nombre").val('');
				
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
		}
		$(document).ready(function() {
			<?php

            if (intval($id_moneda_nacional) == 0) {
                echo "
					
					
					alerta('info','- No cuenta con una moneda por defecto asociada. Realicelo en monedas_extranjeras si asi lo deseas.<br>','Alerta');
					";
            }
if (intval($id_pais_nacional) == 0) {
    echo "
					alerta('info','- No cuenta con un pais por defecto asociado. Realicelo en el modulo de paises si asi lo deseas.<br>','Alerta');
					";
}
?>
		});
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
                    <h2>Editar Proveedor</h2>
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
<div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
	<button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
		<span aria-hidden="true">×</span>
	</button>
	<strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
</div>
<form id="form1" name="form1" method="post" action="" enctype="multipart/form-data">


<div class="col-md-12 col-sm-12  " >
				<h2 style="font-size: 1.3rem;">Datos Tributarios</h2>
				<hr>
			

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="carga_ruc_h();" class="btn btn-sm btn-default" title="Buscar" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span></a> RUC * </label>
			<div class="col-md-9 col-sm-9 col-xs-12">                    
			<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
			    echo htmlentities($_POST['ruc']);
			} else {
			    echo htmlentities($rs->fields['ruc']);
			} ?>" placeholder="ruc" required class="form-control"  />	    
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon Social *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
			    echo htmlentities($_POST['nombre']);
			} else {
			    echo htmlentities($rs->fields['nombre']);
			}?>" placeholder="Nombre" class="form-control" required  />                    
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Fantasia</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_POST['fantasia'])) {
			    echo htmlentities($_POST['fantasia']);
			} else {
			    echo htmlentities($rs->fields['fantasia']);
			}?>" placeholder="Fantasia" class="form-control"   />                    
			</div>
		</div>
		<?php if ($proveedores_sin_factura == "S") {?>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Sin Factura *</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<select name="incrementa" id="incrementa"  title="Sin Factura" class="form-control" required>
						<option value="" >Seleccionar</option>
						<option value="S" <?php if ($_POST['incremental'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
						<option value="N" <?php if ($_POST['incremental'] == 'N' or $_POST['incremental'] == '') {?> selected="selected" <?php } ?>>NO</option>
					</select>
				</div>
			</div>
			<?php } else { ?>
			<div class="col-md-6 col-sm-6 col-xs-12 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Persona*</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
					<?php


                        // valor seleccionado
                        if (isset($_POST['persona'])) {
                            $value_selected = htmlentities($_POST['persona']);
                        } else {
                            $value_selected = $rs->fields['persona'];
                        }
			    // opciones
			    $opciones = [
			        'Física' => '1',
			        'Jurídica' => '2'
			    ];
			    // parametros
			    $parametros_array = [
			        'nombre_campo' => 'persona',
			        'id_campo' => 'persona',

			        'value_selected' => $value_selected,

			        'pricampo_name' => 'Seleccionar...',
			        'pricampo_value' => '',
			        'style_input' => 'class="form-control"',
			        'acciones' => '  ',
			        'autosel_1registro' => 'S',
			        'opciones' => $opciones

			    ];

			    // construye campo
			    echo campo_select_sinbd($parametros_array);


			    ?>
					</div>
			</div>
		<?php } ?>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Email Contador </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="email_conta" id="email_conta" value="<?php  if (isset($_POST['email_conta'])) {
			    echo htmlentities($_POST['email_conta']);
			} else {
			    echo htmlentities($rs->fields['email_conta']);
			}?>" placeholder="Email conta" class="form-control"  />                    
			</div>
		</div>



		<?php if ($proveedores_cta_cte == "S") {?>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable Deuda Proveedor</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="cuenta_cte_deuda" id="cuenta_cte_deuda" value="<?php  if (isset($_POST['cuenta_cte_deuda'])) {
				    echo htmlentities($_POST['cuenta_cte_deuda']);
				} else {
				    echo htmlentities($rs->fields['cuenta_cte_deuda']);
				}?>" placeholder="Cuenta Cte. Deuda Proveedor" class="form-control"   />                    
				</div>
			</div>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable Mercaderia</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="cuenta_cte_mercaderia" id="cuenta_cte_mercaderia" value="<?php  if (isset($_POST['cuenta_cte_mercaderia'])) {
				    echo htmlentities($_POST['cuenta_cte_mercaderia']);
				} else {
				    echo htmlentities($rs->fields['cuenta_cte_mercaderia']);
				}?>" placeholder="Cuenta Cte. Mercaderia" class="form-control"   />                    
				</div>
			</div>
		<?php } ?>

	



		<!-- TODO: SOLO PARA RDE  preferencias agente de retencion y proveedor de mercaderias-->
		<?php if ($proveedores_agente_retencion == "S") { ?>
			<div class="col-md-6 col-sm-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12" >Agente Retencion *</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<select class="custom-select form-control" name="agente_retencion" id="agente_retencion">
					<option value="S" <?php if ($rs->fields['agente_retencion'] == "S") { ?> selected <?php } ?>>Si</option>
					<option  value="N"  <?php if ($rs->fields['agente_retencion'] == "N" or $_POST['agente_retencion'] == '' && $rs->fields["agente_retencion"] != "S") { ?> selected <?php } ?>>No</option>
				</select>
				</div>
			</div>
		<?php } ?>
		
	</div>
	<div class="col-md-12 col-sm-12  " >
			<h2 style="font-size: 1.3rem;">Datos Personales</h2>
			<hr>
			
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
				    echo htmlentities($_POST['direccion']);
				} else {
				    echo htmlentities($rs->fields['direccion']);
				}?>" placeholder="Direccion" class="form-control"  />                    
				</div>
			</div>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
				    echo htmlentities($_POST['telefono']);
				} else {
				    echo htmlentities($rs->fields['telefono']);
				}?>" placeholder="Telefono" class="form-control"  />                    
				</div>
			</div>
			<!-- TODO: tambien en preferencias   -->
			<?php if ($proveedores_importacion == "S") {?>
				
				<div id="dropdown_pais"><?php require_once("./paises_dropdown.php") ?></div>
		
				<div class="col-md-6 col-sm-6 col-xs-12 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<?php

                        // consulta

                        $consulta = "
						SELECT idtipo, descripcion
						FROM tipo_moneda
						where
						estado = 1
						order by descripcion asc
						";

			    // valor seleccionado
			    if (isset($rs->fields['idmoneda'])) {
			        $value_selected = htmlentities($rs->fields['idmoneda']);
			    } else {
			        $value_selected = $id_moneda_nacional;
			    }

			    if ($_GET['idmoneda'] > 0) {
			        $add = "disabled";
			    }

			    // parametros
			    $parametros_array = [
			        'nombre_campo' => 'idmoneda',
			        'id_campo' => 'idmoneda',

			        'nombre_campo_bd' => 'descripcion',
			        'id_campo_bd' => 'idtipo',

			        'value_selected' => $value_selected,

			        'pricampo_name' => 'Seleccionar...',
			        'pricampo_value' => '',
			        'style_input' => 'class="form-control"',
			        'acciones' => ' required="required" "'.$add,
			        'autosel_1registro' => 'N'

			    ];

			    // construye campo
			    echo campo_select($consulta, $parametros_array);

			    ?>
					</div>
				</div>
			<?php } ?>
			<!--  hasta aca la preferencias -->
			
			<!-- TODO: tambien en preferencias proveedor   -->
			<?php if ($proveedores_importacion == "S") {?>
			

				<div class="col-md-6 col-sm-6 col-xs-12 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Origen Proveedor*</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<?php

			    // consulta

			    $consulta = "
						SELECT idtipo_origen, tipo
						FROM tipo_origen
						order by tipo asc
						";

			    // valor seleccionado
			    if (isset($rs->fields['idtipo_origen'])) {
			        $value_selected = htmlentities($rs->fields['idtipo_origen']);
			    } else {
			        $value_selected = null;
			    }



			    // parametros
			    $parametros_array = [
			        'nombre_campo' => 'idtipo_origen',
			        'id_campo' => 'idtipo_origen',

			        'nombre_campo_bd' => 'tipo',
			        'id_campo_bd' => 'idtipo_origen',

			        'value_selected' => $value_selected,

			        'pricampo_name' => 'Seleccionar...',
			        'pricampo_value' => '',
			        'style_input' => 'class="form-control"',
			        'acciones' => ' required="required" "'.$add,
			        'autosel_1registro' => 'N'

			    ];

			    // construye campo
			    echo campo_select($consulta, $parametros_array);

			    ?>
					</div>
				</div>
			<?php } ?>
			<!--  hasta aca la preferencias -->
		
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="email" id="email" value="<?php  if (isset($_POST['email'])) {
				    echo htmlentities($_POST['email']);
				} else {
				    echo htmlentities($rs->fields['email']);
				}?>" placeholder="Email" class="form-control"  />                    
				</div>
			</div>
		
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Contacto </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="contacto" id="contacto" value="<?php  if (isset($_POST['contacto'])) {
				    echo htmlentities($_POST['contacto']);
				} else {
				    echo htmlentities($rs->fields['contacto']);
				}?>" placeholder="Contacto" class="form-control"  />                    
				</div>
			</div>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Area del Contacto </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="text" name="area" aria-describedby="contactoAreaHelp" id="area" value="<?php  if (isset($_POST['area'])) {
					    echo htmlentities($_POST['area']);
					} else {
					    echo htmlentities($rs->fields['area']);
					}?>" placeholder="Area" class="form-control"  />                    
					<small id="contactoAreaHelp" class="form-text text-muted">
						Referente al Area/Cargo del contacto destinado a este proveedor.
					</small>	
				</div>
			</div>
		
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentarios </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="comentarios" id="comentarios" value="<?php  if (isset($_POST['comentarios'])) {
				    echo htmlentities($_POST['comentarios']);
				} else {
				    echo htmlentities($rs->fields['comentarios']);
				}?>" placeholder="Comentarios" class="form-control"  />                    
				</div>
			</div>
		
		
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Web </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input type="text" name="web" id="web" value="<?php  if (isset($_POST['web'])) {
					    echo htmlentities($_POST['web']);
					} else {
					    echo htmlentities($rs->fields['web']);
					}?>" placeholder="Web" class="form-control"  />                    
				</div>
			</div>

			
		<!-- ////////////////////fin datos personales  -->
	</div>
	
	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Acuerdos Comerciales</h2>
		<hr>


		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo comercial *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<select name="acuerdo_comercial" id="acuerdo_comercial"  title="Acuerdo comercial" class="form-control" required>
				<option value="" >Seleccionar </option>
				<option value="S" <?php if ($rs->fields['acuerdo_comercial'] == "S") { ?> selected <?php } ?>>SI</option>
				<option value="N" <?php if ($rs->fields['acuerdo_comercial'] == "N" or $_POST['acuerdo_comercial'] == '' && $rs->fields["acuerdo_comercial"] != "S") { ?> selected <?php } ?>>NO</option>
			</select>
			</div>
		</div>

		<?php if ($proveedores_acuerdos_comerciales_archivo == "S") {?>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo Comercial pdf</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="file" name="archivo_acuerdo_comercial" aria-describedby="archivoProveedorHelp" id="archivo_acuerdo_comercial" class="form-control" />
				<?php if (isset($rs->fields["ac_archivo"])) { ?>
					
					<small id="archivoProveedorHelp" class="form-text text-muted">
						Si se carga otro archivo, el archivo actual se almacenará y se convertirá en el archivo 
						vigente para este proveedor. Sin embargo, aún será posible descargar los archivos 
						anteriores desde el detalle del proveedor.
					</small>
				<?php } ?>
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" name="ac_desde" id="ac_desde" value="<?php  if (isset($_POST['ac_desde'])) {
				    echo htmlentities($_POST['ac_desde']);
				} else {
				    echo $rs->fields['ac_desde'] != "" ? htmlentities(date("Y-m-d", strtotime($rs->fields['ac_desde']))) : "";
				}?>" placeholder="Fecha compra" class="form-control"  onBlur="validar_fecha();" />                    
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta*</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" name="ac_hasta" id="ac_hasta" value="<?php  if (isset($_POST['ac_hasta'])) {
				    echo htmlentities($_POST['ac_hasta']);
				} else {
				    echo $rs->fields['ac_hasta'] != "" ? htmlentities(date("Y-m-d", strtotime($rs->fields['ac_hasta']))) : "";
				}?>" placeholder="Fecha compra" class="form-control"  onBlur="validar_fecha();" />                    
			</div>
		</div>
		<?php } ?>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo comercial Detalle </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="acuerdo_comercial_coment" id="acuerdo_comercial_coment" value="<?php  if (isset($_POST['acuerdo_comercial_coment'])) {
			    echo htmlentities($_POST['acuerdo_comercial_coment']);
			} else {
			    echo htmlentities($rs->fields['acuerdo_comercial_coment']);
			}?>" placeholder="Acuerdo comercial detalle" class="form-control"  />                    
			</div>
		</div>


		
	</div>

	<div class="col-md-12 col-sm-12  " >
		<h2 style="font-size: 1.3rem;">Datos Compra</h2>
		<hr>
		<?php if ($proveedores_tipo_compra == "S") { ?>
		<div class="col-md-6 col-sm-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo compra</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<?php
                // consulta
                $consulta = "
				SELECT idtipocompra, tipocompra
				FROM tipocompra
				order by tipocompra asc
				";

		    // valor seleccionado
		    if (isset($_POST['idtipocompra'])) {
		        $value_selected = htmlentities($_POST['idtipocompra']);
		    } else {
		        $value_selected = htmlentities($rs->fields['tipocompra']);
		    }

		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idtipocompra',
		        'id_campo' => 'idtipocompra',

		        'nombre_campo_bd' => 'tipocompra',
		        'id_campo_bd' => 'idtipocompra',

		        'value_selected' => $value_selected,

		        'pricampo_name' => 'Seleccionar...',
		        'pricampo_value' => '',
		        'style_input' => 'class="form-control"',
		        'acciones' => '  ',
		        'autosel_1registro' => 'S'

		    ];

		    // construye campo
		    echo campo_select($consulta, $parametros_array);

		    ?>
			</div>
		</div>
		<?php } ?>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Dias de Credito *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="diasvence" id="diasvence" value="<?php  if (isset($_POST['diasvence'])) {
			    echo intval($_POST['diasvence']);
			} else {
			    echo intval($rs->fields['diasvence']);
			}?>" placeholder="Diasvence" class="form-control" required />                    
			</div>
		</div>

		<!-- ///////////////////////////////////////////// -->
		<?php if ($tipo_servicio == "S") { ?>
			<div class="col-md-6 col-sm-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12" >Tipo Servicio</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<?php

                    // consulta

                    $consulta = "
					SELECT idtipo_servicio, tipo
					FROM tipo_servicio
					where estado = 1
					order by tipo asc
					";

		    // valor seleccionado
		    if (isset($rs->fields['idtipo_servicio'])) {
		        $value_selected = htmlentities($rs->fields['idtipo_servicio']);
		    } else {
		        $value_selected = null;
		    }



		    // parametros
		    $parametros_array = [
		        'nombre_campo' => 'idtipo_servicio',
		        'id_campo' => 'idtipo_servicio',

		        'nombre_campo_bd' => 'tipo',
		        'id_campo_bd' => 'idtipo_servicio',

		        'value_selected' => $value_selected,

		        'pricampo_name' => 'Seleccionar...',
		        'pricampo_value' => '',
		        'style_input' => 'class="form-control"',
		        'acciones' => ' '.$add,
		        'autosel_1registro' => 'N'

		    ];

		    // construye campo
		    echo campo_select($consulta, $parametros_array);

		    ?>
				</div>
			</div>
		<?php } ?>
		<!--  -->


		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Dias de Entrega</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="dias_entrega" id="dias_entrega" value="<?php  if (isset($_POST['dias_entrega'])) {
			    echo intval($_POST['dias_entrega']);
			} else {
			    echo intval($rs->fields['dias_entrega']);
			}?>" placeholder="dias_entrega" class="form-control" required />                    
			</div>
		</div>

		

		
	</div>
	
	<div class="clearfix"></div>
	<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_proveedores.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

	<input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
	<br />
</form>
<div id="moneda_pais" class="hide" >
	<br />
	<h2 style="font-size: 1.3rem;">Asignar moneda a pais</h2>
				<hr>
	
	<div class="col-md-6 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">
			Pais *
		</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<?php

                // consulta

                $consulta = "
				SELECT p.idpais, p.nombre, p.idmoneda FROM paises_propio p
				WHERE p.estado = 1
				order by nombre asc;
				";

// valor seleccionado
if (isset($rs->fields['idpais'])) {
    $value_selected = htmlentities($rs->fields['idpais']);
} else {
    $value_selected = htmlentities($_GET['id']);
}



// parametros
$parametros_array = [
    'nombre_campo' => 'idpais',
    'id_campo' => 'idpais',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpais',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'data_hidden' => 'idmoneda',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  onchange="verificar_pais(this)" '.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
		</div>
	</div>
		<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

    // consulta

    $consulta = "
				SELECT idtipo, descripcion
				FROM tipo_moneda
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($rs->fields['idmoneda'])) {
    $value_selected = htmlentities($rs->fields['idmoneda']);
} else {
    $value_selected = $id_moneda_nacional;
}

if ($_GET['idmoneda'] > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmoneda',
    'id_campo' => 'idmoneda',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
		</div>

		<div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 col-md-offset-5">
			<a href="javascript:void(0);" onclick="cerrar_detalles_pais()" class="btn btn-default"><span class="fa fa-reply"></span> Volver</a>
			<button type="submit" id="submitEditarPais" class="btn btn-success" onclick="editar_pais_moneda(event);" ><span class="fa fa-check-square-o"></span> Guardar</button>
		</div>
	</div>
	
	<div class="clearfix form-group"></div>
	<br />
</div>

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

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
