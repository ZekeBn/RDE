<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// busca el ruc de hacienda
$consulta = "
select idcliente, ruc, razon_social from cliente where borrable = 'N' and estado <> 6 order by idcliente asc limit 1
";
$rsruc_pred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ruc_pred = trim($rsruc_pred->fields['ruc']);
$razon_social_pred = trim($rsruc_pred->fields['razon_social']);



//buscando moneda nacional
$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_guarani->fields["idtipo"];

if ($id_moneda_nacional == 0) {
    $errores = "Por favor verifique si la moneda local fue asignada o creada <a  style='color:white;' href ='../cotizaciones/monedas_extranjeras.php' > ¡Click Aqui! </a>";
}


//buscando pais defecto
$consulta = "SELECT idpais FROM paises_propio WHERE defecto=1 ";
$rs_guarani = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_pais_nacional = $rs_guarani->fields["idpais"];

if ($id_pais_nacional == 0) {
    $errores = "Por favor verifique si el pais por defecto fue creado <a  style='color:white;' href ='../paises/paises.php' > ¡Click Aqui! </a>";
}

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


?>
<script>
   
	function agregar_proveedores_ajax(event){
        event.preventDefault();

		var parametros_array = {
            "ruc"					        : $("#form_proveedores #ruc").val(), 
            "nombre"					    : $("#form_proveedores #nombre").val(),
            "fantasia"					    : $("#form_proveedores #fantasia").val(),
            "direccion"					    : $("#form_proveedores #direccion").val(),
            "comentarios"					: $("#form_proveedores #comentarios").val(),
            "web"					        : $("#form_proveedores #web").val(),
            "email"					        : $("#form_proveedores #email").val(),
            "telefono"					    : $("#form_proveedores #telefono").val(),
            "contacto"					    : $("#form_proveedores #contacto").val(),
            "area"					        : $("#form_proveedores #area").val(),
            "email_conta"					: $("#form_proveedores #email_conta").val(),
            "diasvence"					    : $("#form_proveedores #diasvence").val(),
            "dias_entrega"					: $("#form_proveedores #dias_entrega").val(),
            "incrementa"					: $("#form_proveedores #incrementa").val(),
            "acuerdo_comercial"             : $("#form_proveedores #acuerdo_comercial").val(),
            "archivo_acuerdo_comercial"     : $("#form_proveedores #archivo_acuerdo_comercial").val(),
            "acuerdo_comercial_desde"       : $("#form_proveedores #acuerdo_comercial_desde").val(),
            "acuerdo_comercial_hasta"       : $("#form_proveedores #acuerdo_comercial_hasta").val(),
            "acuerdo_comercial_coment"      : $("#form_proveedores #acuerdo_comercial_coment").val(),
            "persona"					    : $("#form_proveedores #persona").val(),
            "idpais"					    : $("#form_proveedores #idpais").val(),
            "idmoneda"					    : $("#form_proveedores #idmoneda").val(),
            "agente_retencion"				: $("#form_proveedores #agente_retencion").val(),
            "idtipo_servicio"				: $("#form_proveedores #idtipo_servicio").val(),
            "idtipo_origen"				    : $("#form_proveedores #idtipo_origen").val(),
            "idtipocompra"				    : $("#form_proveedores #idtipocompra").val(),
            "cuenta_cte_mercaderia"			: $("#form_proveedores #cuenta_cte_mercaderia").val(),
            "cuenta_cte_deuda"				: $("#form_proveedores #cuenta_cte_deuda").val(),
            "registrado_por"				: $("#form_proveedores #registrado_por").val(),
            "registrado_el"				    : $("#form_proveedores #registrado_el").val(),
            "idproveedor"				    : $("#form_proveedores #idproveedor").val(),
            "agregar_proveedor"				: 1
        };
        console.log(parametros_array);
		$.ajax({		  
			data:  parametros_array,
			url:   'proveedores_add_ajax.php',
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function () {
			// $("#submitEditarPais").text('Cargando...');
			},
			success:  function (response) {
			console.log(response);
                if(JSON.parse(response)["valido"] == "N"){
                    alerta("danger",JSON.parse(response)["errores"],"Error");
                    var inputElement = $("#form_proveedores #ruc");
                    $('#dialogobox').animate({
                        scrollTop: inputElement.offset().top
                    }, 150);
                }else{
                    cerrar_pop();
                    recargar_proveedores();
                }
                // $("#dropdown_pais").html(response);
				// $("#form_proveedores #idmoneda").val($("#moneda_pais #idmoneda").val());
				// cerrar_detalles_pais();
			},
			error: function(jqXHR, textStatus, errorThrown) {
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
			}
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
			});
	}
	function cerrar_detalles_pais(){
		$('#form_proveedores').removeClass('hide');
		$("#form_proveedores").addClass('show');
		$('#moneda_pais').removeClass('show');
		$("#moneda_pais").addClass('hide');
	}
	function detalles_pais(){
		$('#form_proveedores').removeClass('show');
		$("#form_proveedores").addClass('hide');
		$('#moneda_pais').removeClass('hide');
		
		$("#moneda_pais #idpais").val($("#form_proveedores #idpais").val());
		$("#moneda_pais").addClass('show');

		setTimeout(function() {
			$(window).scrollTop(0)},500
		)
		
	}
	function cerrar_errores_proveedor(event){
		event.preventDefault();
		$('#form_proveedores #boxErroresProveedor').removeClass('show');
		$('#form_proveedores #boxErroresProveedor').addClass('hide');
	}
	function alerta( clase ,error,titulo){
		var alertaClase = 'alert-' + clase;
		if (clase == "info"){
			$('#form_proveedores #boxErroresProveedor').removeClass('alert-danger');
		}else{
			$('#form_proveedores #boxErroresProveedor').removeClass('alert-info');
		}
		$('#tituloErroresProveedor').html(titulo);
		$('#form_proveedores #boxErroresProveedor').addClass(alertaClase);
		$('#form_proveedores #boxErroresProveedor').removeClass('hide');
		$("#erroresProveedor").html(error);
		$('#form_proveedores #boxErroresProveedor').addClass('show');
		
	}
	function verificar_pais(selectElement){
		
		const selectedOption = selectElement.options[selectElement.selectedIndex];
		//seleccion de origen local o importacion 
		if(selectedOption.value==<?php echo $id_pais_nacional?>) {

			$("#idtipo_origen").val(<?php echo $id_tipo_origen_local?>);
		}else{
			$("#idtipo_origen").val(<?php echo $id_tipo_origen_importacion?>);

		}
		const idMoneda = selectedOption.dataset.hiddenValue;
		console.log(idMoneda);
		if (parseInt(idMoneda) > 0){
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
					url:   '../proveedores/ruc_extrae_prov.php',
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



<form id="form_proveedores" name="form_proveedores" method="post" action="" enctype="multipart/form-data">
    <div class="alert  alert-dismissible fade in hide" role="alert" id="boxErroresProveedor">
        <button type="button" class="close" onclick="cerrar_errores_proveedor(event)" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
        <strong id="tituloErroresProveedor">Errores:</strong><br /><p id="erroresProveedor"></p>
    </div>

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
                            $value_selected = 'S';
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

	



		<!-- SOLO PARA RDE  preferencias agente de retencion y proveedor de mercaderias-->
		<?php if ($proveedores_agente_retencion == "S") { ?>
			<div class="col-md-6 col-sm-6 col-xs-12 form-group" >
				<label class="control-label col-md-3 col-sm-3 col-xs-12" >Agente Retencion *</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<select class="custom-select form-control" name="agente_retencion" id="agente_retencion">
					<option value="S">Si</option>
					<option selected value="N">No</option>
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
			<!--  tambien en preferencias   -->
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
			    if (isset($_POST['idmoneda'])) {
			        $value_selected = htmlentities($_POST['idmoneda']);
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
			
			<!-- tambien en preferencias proveedor   -->
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
			    if (isset($_POST['idtipo_origen'])) {
			        $value_selected = htmlentities($_POST['idtipo_origen']);
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
			<option value="" >Seleccionar</option>
			<option value="S" <?php if ($_POST['acuerdo_comercial'] == 'S') {?> selected="selected" <?php } ?>>SI</option>
			<option value="N" <?php if ($_POST['acuerdo_comercial'] == 'N' or $_POST['acuerdo_comercial'] == '') {?> selected="selected" <?php } ?>>NO</option>
			</select>
			</div>
		</div>
		<?php if ($proveedores_acuerdos_comerciales_archivo == "S") {?>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo Comercial pdf</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="file" name="archivo_acuerdo_comercial" id="archivo_acuerdo_comercial" class="form-control" />
			</div>
		</div>

		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" name="ac_desde" id="ac_desde" value="<?php  if (isset($_POST['ac_desde'])) {
				    echo htmlentities($_POST['ac_desde']);
				} else {
				    echo htmlentities($rs->fields['ac_desde']);
				}?>" placeholder="Fecha compra" class="form-control"  onBlur="validar_fecha();" />                    
			</div>
		</div>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta*</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" name="ac_hasta" id="ac_hasta" value="<?php  if (isset($_POST['ac_hasta'])) {
				    echo htmlentities($_POST['ac_hasta']);
				} else {
				    echo htmlentities($rs->fields['ac_hasta']);
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
		        $value_selected = htmlentities($rs->fields['idtipocompra']);
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
		    if (isset($_POST['idtipo_servicio'])) {
		        $value_selected = htmlentities($_POST['idtipo_servicio']);
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
		<?php if ($proveedores_dias_entrega == "S") { ?>
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
		<?php } ?>

		
	</div>
	
	<div class="clearfix"></div>
	<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
            <a type="submit" href="javascript:void(0);" onClick="agregar_proveedores_ajax(event);" class="btn btn-success" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-check-square-o"></span> Registrar</a>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form_proveedores" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
	<br />
</form>


<div class="clearfix"></div>
<br /><br />


