<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
require_once("../cotizaciones/preferencias_cotizacion.php");

global $cotiza_dia_anterior;
global $editar_fecha;
global $usa_cot_compra;

$idmoneda = $_POST['idmoneda'];

$hoy = date("Y-m-d");
?>
<style>
	#form_cotizacion_moneda input {
		color: #020202cc;
		padding: 3%;
		font-size: initial;
	}

	input:focus,
	select:focus {
		border: #add8e6 solid 3px !important;
		/* Este es un tono de azul pastel */
	}

	input:focus,
	select:focus {
		border: #add8e6 solid 3px !important;
		/* Este es un tono de azul pastel */
	}

	#preloader-overlay {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		z-index: 9999;
		display: none;
		align-items: center;
		justify-content: center;
	}

	.lds-facebook {
		display: inline-block;
		position: relative;
		width: 80px;
		height: 80px;
	}

	.lds-facebook div {
		display: inline-block;
		position: absolute;
		left: 8px;
		width: 16px;
		background: #fff;
		animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
	}

	.lds-facebook div:nth-child(1) {
		left: 8px;
		animation-delay: -0.24s;
	}

	.lds-facebook div:nth-child(2) {
		left: 32px;
		animation-delay: -0.12s;
	}

	.lds-facebook div:nth-child(3) {
		left: 56px;
		animation-delay: 0;
	}

	@keyframes lds-facebook {
		0% {
			top: 8px;
			height: 64px;
		}

		50%,
		100% {
			top: 24px;
			height: 32px;
		}
	}
</style>

<script>
	function agregar_moneda_modal() {
		var direccionurl = 'compras_ordenes_select_cotizacion.php';
		var parametros = {
			"cotizacion": $("#form_cotizacion_moneda #cotizacion").val(),
			"compra": $("#form_cotizacion_moneda #compra").val(),
			"fecha": $("#form_cotizacion_moneda #fecha").val(),
			"tipo_moneda": $("#form_cotizacion_moneda #tipo_moneda").val(),
			"agregar": 1
		};
		$.ajax({
			data: parametros,
			url: direccionurl,
			type: 'post',
			cache: false,
			timeout: 3000, // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function() {

			},
			success: function(response, textStatus, xhr) {
				// console.log(response);
				cerrar_pop();
				$("#box_cotizaciones").html(response);
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if (jqXHR.status == 404) {
					alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
				} else if (jqXHR.status == 0) {
					alert('Se ha rechazado la conexión.');
				} else {
					alert(jqXHR.status + ' ' + errorThrown);
				}
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {

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

	function ver_cotizacion_set() {
		document.getElementById('preloader-overlay').style.display = 'flex';
		$.ajax({
			data: {
				"SET": 1
			},
			url: '../cotizaciones/cotizacion_set_ver.php',
			type: 'post',
			cache: false,
			timeout: 15000, // I chose 3 secs for kicks: 5000
			crossDomain: true,
			beforeSend: function() {},
			success: function(response) {
				document.getElementById('preloader-overlay').style.display = 'none';
				var res = JSON.parse(response);
				var fecha = res['fecha'];
				if (res.success == true) {
					var usa_cot_compra = <?php echo $usa_cot_compra ? "'$usa_cot_compra'" : "'N'"; ?>;
					var text = "Fecha: " + res['fecha'] + " " + res['moneda'] + " VENTA: " + res['cotizacion'];
					if (usa_cot_compra == "S") {
						text += " COMPRA: " + res['compra'];
					}
					$("#cotizacion_set").html(text);
				} else {
					var resultado = res['error'] + res['texto'];
					$("#cotizacion_set").html(resultado)
				}
				$("#contenedor #box_cotizacion_set").css("display", "block");
				$("#cotizacion_set").css("font-weight", "bold");
				$("#cotizacion_set").css("font-size", "1.1rem");
				$("#form_cotizacion_moneda #cotizacion").val(res['cotizacion']);
				$("#form_cotizacion_moneda #compra").val(res['compra']);
				// console.log(res['cotizacion']);

				// Confirmar la fecha de la set
				mostrarConfirmacion(fecha);
			},
			error: function(jqXHR, textStatus, errorThrown) {
				document.getElementById('preloader-overlay').style.display = 'none';
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			document.getElementById('preloader-overlay').style.display = 'none';
			errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
		});
	}

	// Manejamos la confirmacion o rechazo de la fecha set por el usuario
	function mostrarConfirmacion(fechaCotizacion) {
		const confirmMessage = `¿Desea registrar la cotización con la fecha de ${fechaCotizacion}?`;
		if (confirm(confirmMessage)) {
			// User accepted, set the date
			$("#form_cotizacion_moneda #fecha").val(fechaCotizacion);
		} else {
			// User declined, set today's date
			$("#form_cotizacion_moneda #fecha").val(new Date().toISOString().split('T')[0]); // Today's date in YYYY-MM-DD
		}
	}

	function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo) {
		// error
		if (tipo == 'error') {
			if (jqXHR.status == 404) {
				alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
			} else if (jqXHR.status == 0) {
				alert('Se ha rechazado la conexión.');
			} else {
				alert(jqXHR.status + ' ' + errorThrown);
			}
			// fail
		} else {
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

	function cerrar_alert() {
		$("#contenedor #box_cotizacion_set").css("display", "none");
	}
</script>

<div class="colcompleto" id="contenedor">
	<div style="width:100%"><a href="javascript:void(0);" style="float:right" onclick="ver_cotizacion_set()" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Ver Cotizacion Dolar SET</a></div>
	<div class="clearfix"></div>
	<div style="text-align:center;display:none;" id="box_cotizacion_set" class="alert alert-info">
		<button type="button" class="close" onclick="cerrar_alert()" aria-label="Close"><span>×</span></button>
		<div id="cotizacion_set"></div>
		<div class="clearfix"></div>
	</div>
	<div class="clearfix"></div>
</div>

<p>&nbsp;</p>
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<form id="form_cotizacion_moneda" name="form_cotizacion_moneda" method="post" action="">

	<div class="col-md-6 col-sm-12 col-xs-12 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">* Cotizaci&oacute;n (venta) </label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="cotizacion" id="cotizacion" value="<?php if (isset($_POST['cotizacion'])) {
																			echo htmlentities($_POST['cotizacion']);
																		} else {
																			echo htmlentities($rs->fields['cotizacion']);
																		} ?>" placeholder="cotizacion(venta)" required="required" style="height: 40px; width: 99%;" />
		</div>
	</div>
	<?php if ($usa_cot_compra == "S") { ?>
		<div class="col-md-6 col-sm-12 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"> Cotizaci&oacute;n (compra) </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="text" name="compra" id="compra" value="<?php if (isset($_POST['compra'])) {
																		echo htmlentities($_POST['compra']);
																	} else {
																		echo htmlentities($rs->fields['compra']);
																	} ?>" placeholder="cotizacion(compra)" required="required" style="height: 40px; width: 99%;" />
			</div>
		</div>
	<?php } ?>

	<?php if ($editar_fecha == 'S') { ?>
		<div class="col-md-6 col-sm-12 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12"> * Fecha </label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<input type="date" name="fecha" id="fecha" value="<?php if (isset($_POST['fecha'])) {
																		echo htmlentities($_POST['fecha']);
																	} else {
																		echo htmlentities($hoy);
																	} ?>" placeholder="fecha" required="required" style="height: 40px; width: 99%;" />
			</div>
		</div>
	<?php } ?>
	<div id="box_moneda_extranjera_id">
		<div class="col-md-6 col-sm-12 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">*Moneda Extranjera:</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php
				// consulta
				$consulta = "select * from tipo_moneda  where estado = 1 and idempresa = $idempresa and borrable = 'S' and nacional = 'N' ";

				// valor seleccionado
				if (isset($_POST['idtipo'])) {
					$value_selected = htmlentities($_POST['tipo_moneda']);
				} else {
					$value_selected = $idmoneda;
				}

				// parametros
				$parametros_array = [
					'nombre_campo' => 'tipo_moneda',
					'id_campo' => 'tipo_moneda',

					'nombre_campo_bd' => 'descripcion',
					'id_campo_bd' => 'idtipo',

					'value_selected' => $value_selected,

					'pricampo_name' => 'Seleccionar...',
					'pricampo_value' => '',
					'style_input' => 'class="form-control"',
					'autosel_1registro' => 'N'
				];

				// construye campo
				echo campo_select($consulta, $parametros_array);
				?>
			</div>
		</div>
	</div>
	<br />

	<p align="center">
		<input type="hidden" name="MM_insert" value="form_cotizacion_moneda" />
	</p>
	<div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
			<a href="javascript:void(0)" onclick="agregar_moneda_modal()" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</a>
		</div>
	</div>
	<br />
</form>
<br /><br /><br />

<div id="preloader-overlay">
	<div class="lds-facebook">
		<div></div>
		<div></div>
		<div></div>
	</div>
</div>