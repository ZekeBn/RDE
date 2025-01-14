<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../compras/preferencias_compras.php");
require_once("../proveedores/preferencias_proveedores.php");

$ocnum = intval($_GET['id']);
if ($ocnum == 0) {
	header("location: compras_ordenes.php");
	exit;
}

// consulta a la tabla
$consulta = "
select compras_ordenes.* ,compras_ordenes.idproveedor, prov.nombre as proveedor, tipo_moneda.banderita, tipo_o.tipo as tipo_origen,
cotizaciones.cotizacion as cot_ref
from compras_ordenes 
left join proveedores as prov on prov.idproveedor = compras_ordenes.idproveedor 
left join tipo_origen as tipo_o on tipo_o.idtipo_origen = compras_ordenes.idtipo_origen
left join cotizaciones on cotizaciones.idcot = compras_ordenes.idcot
left join tipo_moneda on tipo_moneda.idtipo = compras_ordenes.idtipo_moneda 
where 
compras_ordenes.ocnum = $ocnum
and compras_ordenes.estado = 2
limit 1";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$ocnum = intval($rs->fields['ocnum']);
$idproveedor_res = intval($rs->fields['idproveedor']);
$proveedor_res = antixss($rs->fields['proveedor']);
$estado_orden = intval($rs->fields['estado_orden']);
// echo $proveedor_res;exit;

if ($ocnum == 0) {
	header("location: compras_ordenes.php");
	exit;
}

$buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);

$buscar = "SELECT idproveedor, idtipo_origen,nombre ,idmoneda, ruc,tipocompra FROM proveedores";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$resultados_proveedores = null;

while (!$rsd->EOF) {

	$idproveedor = trim(antixss($rsd->fields['idproveedor']));
	$idmoneda = trim(antixss($rsd->fields['idmoneda']));
	$idtipo_origen = trim(antixss($rsd->fields['idtipo_origen']));
	$nombre = trim(antixss($rsd->fields['nombre']));
	$tipocompra = trim(antixss($rsd->fields['tipocompra']));
	$ruc = trim(antixss($rsd->fields['ruc']));
	$resultados_proveedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$ruc' onclick=\"cambia_proveedor($idtipo_origen, $idmoneda, $idproveedor, '$nombre',$tipocompra);\">[$idproveedor]-$nombre</a>";

	$rsd->MoveNext();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<?php require_once("../includes/head_gen.php"); ?>
	<script>
		function IsJsonString(str) {
			try {
				JSON.parse(str);
			} catch (e) {
				return false;
			}
			return true;
		}

		function eliminar_orden(ocnum) {
			var parametros = {
				"eliminar_orden_padre": 1,
				"ocn": ocnum
			};
			$.ajax({
				data: parametros,
				url: 'cerrar_orden_padre.php',
				type: 'post',
				beforeSend: function() {
					// $("#generar_compra").text('Cargando...');

				},
				success: function(response) {
					if (IsJsonString(response)) {
						var obj = jQuery.parseJSON(response);
						if (obj.success == true) {
							document.location.href = 'compras_ordenes.php'
						}
					}
				}
			});
		}

		function modal_finalizar_orden() {
			var parametros = {
				"eliminar orden": 1,
				"ocnum": <?php echo $ocnum; ?>
			};
			$.ajax({
				data: parametros,
				url: 'compras_ordenes_finalizar_modal.php',
				type: 'post',
				beforeSend: function() {
					// $("#generar_compra").text('Cargando...');

				},
				success: function(response) {
					alerta_modal("Orden de compra", response);
				}
			});
		}

		function eliminar_proforma(idproforma) {
			var idproforma = idproforma;
			var parametros = {
				"idproforma": idproforma,
				"ocn": <?php echo $ocnum; ?>,
				"eliminar_proforma": 1
			};
			$.ajax({
				data: parametros,
				url: 'sub_ordenes_asociadas1.php',
				type: 'post',
				beforeSend: function() {
					// $("#generar_compra").text('Cargando...');

				},
				success: function(response) {
					$("#grilla_box_ordenes_asociadas").html(response);
				}
			});
		}

		function alerta_modal(titulo, mensaje) {
			$('#modal_ventana').modal('show');
			$("#modal_titulo").html(titulo);
			$("#modal_cuerpo").html(mensaje);
		}

		function ver_orden_detalle(ocnum) {
			event.preventDefault();
			var parametros_array = {
				"ocn": ocnum
			};
			console.log(parametros_array);
			$.ajax({
				data: parametros_array,
				url: '../compras/compras_ordenes_grillaprod_det.php',
				type: 'post',
				cache: false,
				timeout: 3000, // I chose 3 secs for kicks: 5000
				crossDomain: true,
				beforeSend: function() {},
				success: function(response) {
					$("#modal_titulo").html("Detalle de Orden");
					$("#modal_cuerpo").html(response);
					$('#modal_ventana').modal('show');
				},
				error: function(jqXHR, textStatus, errorThrown) {
					errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
			});
		}

		function buscar_embarque(event) {

			event.preventDefault();

			var parametros = {
				"idtransaccion": "idtransaccion",
				"idunico": "idunico"
			};
			$("#titulov").html("Buscar Embarque");

			$.ajax({
				data: parametros,
				url: 'buscar_embarque_modal.php',
				type: 'post',
				cache: false,
				timeout: 3000, // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function() {

				},
				success: function(response) {
					$("#ventanamodal").modal("show");
					$("#cuerpov").html(response);
				}
			});
		}
	</script>

	<style type="text/css">
		tbody tr:hover td {
			color: #73879C !important;
		}

		.activo:hover,
		.finalizado:hover {
			color: #3F5367;
		}

		.activo {
			background: #FFC857;
			font-weight: bold;
			color: white;

		}

		.finalizado {
			background: #9BC1BC;
			font-weight: bold;
			color: white;

		}

		#lista_proveedores {
			width: 100%;
		}

		.a_link_proveedores {
			display: block;
			padding: 0.8rem;
		}

		.a_link_proveedores:hover {
			color: white;
			background: #73879C;
		}

		.dropdown_proveedores {
			position: absolute;
			top: 70px;
			left: 0;
			z-index: 99999;
			width: 100% !important;
			overflow: auto;
			white-space: nowrap;
			background: #fff !important;
			border: #c2c2c2 solid 1px;
		}

		.dropdown_proveedores_input {
			position: absolute;
			top: 37px;
			left: 0;
			z-index: 99999;
			display: none;
			width: 100% !important;
			padding: 5px !important;
		}

		.btn_proveedor_select {
			border: #c2c2c2 solid 1px;
			color: #73879C;
			width: 100%;
		}

		.forzar_cierre {
			width: 100%;
			padding: 1.1rem;
			box-sizing: border-box;
			margin-bottom: 1rem !important;
		}

		.forzar_cierre:hover {
			background: #DE748B;
			color: white;
			border: 1px solid #DE748B;
		}
	</style>
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
					<div class="page-title"></div>
					<div class="clearfix"></div>

					<!-- SECCION -->
					<div class="row">
						<div class="col-md-12 col-sm-12 col-xs-12">
							<div class="x_panel">
								<div class="x_title">
									<h2>Detalles Orden de Compra</h2>
									<ul class="nav navbar-right panel_toolbox">
										<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
										</li>
									</ul>
									<div class="clearfix"></div>
								</div>
								<div class="x_content">
									<a href="javascript:void(0);" onMouseUp="document.location.href='compras_ordenes.php'" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
									<?php if ($preferencias_importacion == "S") { ?>
										<a href="inf_ocdetallev2_pdf_sub_ordenes_pdf.php?idoc=<?php echo $rs->fields['ocnum']; ?>" target="_blank" class="btn btn-sm btn-default" title="Imprimir Reporte" data-toggle="tooltip" data-placement="right" data-original-title="Imprimir"><span class="fa fa-print"></span> Imprimir Orden con Proformas</a>
										<a href="inf_ocdetallev2_pdf.php?idoc=<?php echo $rs->fields['ocnum']; ?>" target="_blank" class="btn btn-sm btn-default" title="Imprimir PDF A4" data-toggle="tooltip" data-placement="right" data-original-title="Imprimir Reporte"><span class="fa fa-print"></span> Imprimir Orden sin Proformas</a>
									<?php } ?>
									<?php if (trim($errores) != "") { ?>
										<div class="alert alert-danger alert-dismissible fade in" role="alert">
											<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
											</button>
											<strong>Errores:</strong><br /><?php echo $errores; ?>
										</div>
									<?php } ?>
									<form id="form1" name="form1" method="post" action="">
										<br>

										<?php if ($estado_orden == 1) { ?>
											<div class="form-group">
												<label class="control-label col-md-3 col-sm-3 col-xs-12">Forzar cierre de Orden de compra</label>
												<div class="col-md-9 col-sm-9 col-xs-12">
													<button type="button" class="btn btn-default forzar_cierre" onclick="modal_finalizar_orden()"><span class="fa fa-ban"></span> Finalizar Orden</button>
												</div>
											</div>
										<?php } ?>

										<div class=" form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<div class="" style="display:flex;">
													<div class="dropdown " id="lista_proveedores">
														<select disabled class="form-control" id="idproveedor" name="idproveedor">
															<option value="" disabled></option>
															<?php if (intval($idproveedor_res) > 0) { ?>
																<option value="<?php echo $idproveedor_res; ?>" selected><?php echo $proveedor_res; ?></option>
															<?php } ?>
														</select>
													</div>
													<!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
														<span  class="fa fa-plus"></span> Agregar
													</a> -->
												</div>
											</div>
										</div>

										<div class="clearfix"></div>
										<?php if ($proveedores_importacion == "S") { ?>
											<div class=" form-group">
												<label class="control-label col-md-3 col-sm-3 col-xs-12">Origen</label>
												<div class="col-md-9 col-sm-9 col-xs-12">
													<?php

													// consulta
													$consulta = "
													SELECT *
													FROM tipo_origen
													order by tipo asc";

													// valor seleccionado
													if (isset($_POST['idtipo_origen'])) {
														$value_selected = htmlentities($_POST['idtipo_origen']);
													} else {
														$value_selected = $rs->fields['idtipo_origen'];
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
														'acciones' => ' required="required" disabled "' . $add,
														'autosel_1registro' => 'N'
													];

													// construye campo
													echo campo_select($consulta, $parametros_array);
													?>
												</div>
											</div>
										<?php } ?>

										<div class="form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha de la Orden</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<input disabled type="date" name="fecha" id="fecha" value="<?php if (isset($_POST['fecha'])) {
																												echo htmlentities($_POST['fecha']);
																											} else {
																												echo date("Y-m-d", strtotime(htmlentities($rs->fields['fecha'])));
																											} ?>" placeholder="Fecha" class="form-control" required="required" />
											</div>
										</div>

										<div class="form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Compra</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<?php

												// consulta
												$consulta = "
												SELECT *
												FROM tipocompra
												order by tipocompra asc";

												// valor seleccionado
												if (isset($_POST['idtipocompra'])) {
													$value_selected = htmlentities($_POST['idtipocompra']);
												} else {
													$value_selected = $rs->fields['tipocompra'];
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
													'acciones' => ' required="required" disabled "' . $add,
													'autosel_1registro' => 'N'
												];

												// construye campo
												echo campo_select($consulta, $parametros_array);
												?>
											</div>
										</div>
										<div class="form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha de entrega </label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<input disabled type="date" name="fecha_entrega" id="fecha_entrega" value="<?php if (isset($_POST['fecha_entrega'])) {
																																echo htmlentities($_POST['fecha_entrega']);
																															} else {
																																echo htmlentities($rs->fields['fecha_entrega']);
																															} ?>" placeholder="Fecha entrega" class="form-control" />
											</div>
										</div>
										<?php if ($proveedores_importacion == "S" && $idtipo_origen_importacion == $rs->fields['idtipo_origen']) { ?>
											<div id="monedas" class="form-group">
												<div class="form-group">
													<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
													<div class="col-md-9 col-sm-9 col-xs-12">
														<?php

														// consulta
														$consulta = "Select * from tipo_moneda where estado=1 and nacional!='S' and cotiza=1";

														// valor seleccionado
														if (isset($_POST['idtipo_moneda'])) {
															$value_selected = htmlentities($_POST['idtipo_moneda']);
														} else {
															$value_selected = $rs->fields['idtipo_moneda'];
														}

														// parametros
														$parametros_array = [
															'nombre_campo' => 'idtipo_moneda',
															'id_campo' => 'idtipo_moneda',

															'nombre_campo_bd' => 'descripcion',
															'id_campo_bd' => 'idtipo',

															'value_selected' => $value_selected,

															'pricampo_name' => 'Seleccionar...',
															'pricampo_value' => '',
															'style_input' => 'class="form-control"',
															'acciones' => ' disabled  "' . $add,
															'autosel_1registro' => 'N'
														];

														// construye campo
														echo campo_select($consulta, $parametros_array);
														?>
													</div>
												</div>

												<div class="form-group">
													<label class="control-label col-md-3 col-sm-3 col-xs-12">Cambio del dia</label>
													<div class="col-md-9 col-sm-9 col-xs-12">
														<input disabled type="text" name="cot_ref" id="cot_ref" aria-describedby="cotRefHelp" placeholder="Cambio del dia" value="<?php if (isset($_POST['cot_ref'])) {
																																														echo htmlentities(formatomoneda($_POST['cot_ref'], 2, "S"));
																																													} else {
																																														echo htmlentities(formatomoneda($rs->fields['cot_ref'], 2, "S"));
																																													} ?>" class="form-control" />
														<small id="cotRefHelp"><small>
													</div>
												</div>
											<?php } ?>

											<div id="grilla_box"><?php require("compras_ordenes_grillaprod_det.php"); ?></div>
											<?php if ($preferencias_importacion == "S") { ?>
												<div id="grilla_box_ordenes_asociadas"><?php require("sub_ordenes_asociadas1.php"); ?></div>
												<!-- <div id="grilla_box_ordenes_asociadas"><?php // require("sub_ordenes_asociadas.php");
																							?></div> -->
												<!-- <div id="grilla_box_productos_comprados"><?php // require("compras__sub_ordenes_grillaprod_det.php");
																								?></div> -->
											<?php } ?>
											<div class="clearfix"></div>

											<div class="form-group">
												<div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;justify-content:center;">
													<button type="button" class="btn btn-primary" onMouseUp="document.location.href='compras_ordenes.php'"><span class="fa fa-reply"></span> volver</button>
												</div>
											</div>

											<input type="hidden" name="MM_update" value="form1" />
											<input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">

											<div class="clearfix"></div>
											<br /><br>
									</form>
								</div>
							</div>
						</div>
					</div>
					<!-- SECCION -->

					<!-- POPUP DE MODAL OCULTO -->
					<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
									<h4 class="modal-title" id="modal_titulo">Titulo</h4>
								</div>
								<div class="modal-body" id="modal_cuerpo">
									Contenido...
								</div>
								<div class="modal-footer" id="modal_pie">
									<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
								</div>
							</div>
						</div>
					</div>
					<!-- POPUP DE MODAL OCULTO -->
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