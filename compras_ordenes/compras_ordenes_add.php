<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../proveedores/preferencias_proveedores.php");
require_once("../compras/preferencias_compras.php");

$consulta = "SELECT idtipo FROM `tipo_moneda` WHERE nacional='S' ";
$rs_nacional = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id_moneda_nacional = $rs_nacional->fields["idtipo"];

if ($_POST['idproveedor']) {
	$idproveedor = $_POST['idproveedor'];
	$buscar = "SELECT nombre,diasvence FROM proveedores WHERE idproveedor = $idproveedor";
	$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
	$proveedor_nombre = $rsd->fields['nombre'];
	$diasvence = $rsd->fields['diasvence'];
}

$buscar = "SELECT * FROM `tipo_origen` WHERE UPPER(tipo) = UPPER('importacion')";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtipo_origen_importacion = intval($rsd->fields['idtipo_origen']);

//SELECT proveedores datos
$buscar = "SELECT diasvence,idproveedor, idtipo_origen,nombre ,idmoneda, ruc,tipocompra,idtipo_servicio,dias_entrega
FROM proveedores
where estado = 1";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$resultados_proveedores = null;

while (!$rsd->EOF) {
	$idproveedor = intval(trim(antixss($rsd->fields['idproveedor'])));
	$dias_entrega = intval(trim(antixss($rsd->fields['dias_entrega'])));
	$idmoneda = intval(trim(antixss($rsd->fields['idmoneda'])));
	$idtipo_origen = intval(trim(antixss($rsd->fields['idtipo_origen'])));
	$idtipo_servicio = intval(trim(antixss($rsd->fields['idtipo_servicio'])));
	$nombre = trim(antixss($rsd->fields['nombre']));
	$tipocompra = intval(trim(antixss($rsd->fields['tipocompra'])));
	$diasvence = floatval($rsd->fields['diasvence']);
	$ruc = trim(antixss($rsd->fields['ruc']));
	$resultados_proveedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-diasvence='$diasvence' data-hidden-value='$ruc' data-hidden-servicio='$idtipo_servicio' data-hidden-entrega='$dias_entrega' onclick=\"cambia_proveedor($idtipo_origen, $idmoneda, $idproveedor, '$nombre',$tipocompra, $dias_entrega,$diasvence);\">[$idproveedor]-$nombre</a>";

	$rsd->MoveNext();
}
//FIN del SLECT
// echo $_POST['tipooc'];exit;

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
	// control de formularios, seguridad para evitar doble envio y ataques via bots
	$_SESSION['form_control'] = md5(rand());

	// recibe parametros
	$fecha = antisqlinyeccion($_POST['fecha'], "text");
	$idcot = antisqlinyeccion($_POST['idcotizacion'], "text");
	$tipocompra = antisqlinyeccion($_POST['idtipocompra'], "int");
	$idtipo_origen = antisqlinyeccion($_POST['idtipo_origen'], "int");
	$idtipo_moneda = antisqlinyeccion($_POST['idtipo_moneda'], "int");

	$consulta = "SELECT cotiza from tipo_moneda where idtipo = $idtipo_moneda";
	$rscotiza = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

	$cotiza_moneda = intval($rscotiza->fields['cotiza']);

	if ($idtipo_origen_importacion == $idtipo_origen) {
		if (($idcot == null || $idcot == "NULL") && $cotiza_moneda == 1) {
			$valido = "N";
			$errores .= " - La cotizacion debe ser cargada verifiquelo.<br />";
		}
	} else {
		if ($multimoneda_local == "S") {
			if (($idcot == null || $idcot == "NULL") && $cotiza_moneda == 1) {
				$valido = "N";
				$errores .= " - La cotizacion debe ser cargada verifiquelo.<br />";
			}
		} else {
			$idtipo_moneda = $id_moneda_nacional;
			$idcot = 0;
		}
	}

	$fecha_entrega = antisqlinyeccion($_POST['fecha_entrega'], "text");

	if ($fecha_entrega == "NULL") {
		$fecha_entrega = "00-00-00";
	}

	$idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
	$estado = 1;
	$registrado_por = $idusu;
	$registrado_el = antisqlinyeccion($ahora, "text");

	$buscar = "SELECT diasvence
	FROM proveedores
	where idproveedor = $idproveedor";
	$rsdp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

	if (trim($_POST['fecha']) == '') {
		$valido = "N";
		$errores .= " - El campo fecha no puede estar vacio.<br />";
	}
	if (intval($_POST['idtipocompra']) == 0) {
		$valido = "N";
		$errores .= " - No indico si la compra sera contado o credito.<br />";
	}
	if ($proveedores_importacion == "S") {
		if (intval($_POST['idtipo_origen']) == 0) {
			$valido = "N";
			$errores .= " - No indico si la compra sera Local o Importaci&ocute;n.<br />";
		}
	} else {
		$idtipo_origen = 0;
	}
	if (intval($_POST['idproveedor']) == 0) {
		$valido = "N";
		$errores .= " - No indico el proveedor.<br />";
	}

	// si todo es correcto inserta
	if ($valido == "S") {

		$consulta = "select max(compras_ordenes.ocnum) as ocnum from compras_ordenes";
		$rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
		$ocnum = $rsmax->fields['ocnum'] + 1;
		$cant_dias = 0;
		$inicia_pago = "NULL";

		if ($tipocompra == 2) {
			$cant_dias = $rsdp->fields["diasvence"];
			$fecha_entrega_aux = str_replace("'", "", $fecha_entrega);
			$inicia_pago = date('Y-m-d', strtotime($fecha_entrega_aux . " +$cant_dias days")); // Convierte el string en un objeto DateTime
			$inicia_pago = "'$inicia_pago'";
		}

		$consulta = "
		insert into compras_ordenes
		(ocnum, fecha, generado_por, tipocompra, fecha_entrega, idproveedor, estado, forma_pago, cant_dias, inicia_pago, registrado_por, registrado_el,idtipo_moneda,idcot,idtipo_origen,estado_orden)
		values
		($ocnum, $fecha, $idusu, $tipocompra, $fecha_entrega, $idproveedor, $estado, 0, $cant_dias, $inicia_pago, $registrado_por, $registrado_el,$idtipo_moneda,$idcot,$idtipo_origen,1)";
		$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

		header("location: compras_ordenes_det.php?id=$ocnum");
		exit;
	}
}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

$buscar = "Select * from tipo_moneda where estado=1 ";
$rsmonedas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<?php require_once("../includes/head_gen.php"); ?>
	<link rel="stylesheet" href="css/compras_ordenes/compras_ordenes_add.css">
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

					<!-- SECCION -->
					<div class="row">
						<div class="col-md-12 col-sm-12 col-xs-12">
							<div class="x_panel">
								<div class="x_title">
									<h2>Agregar Orden de Compra</h2>
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

										<?php if ($tipo_servicio == "S") { ?>
											<div class=" form-group">
												<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo servicio</label>
												<div class="col-md-9 col-sm-9 col-xs-12">
													<?php

													// consulta
													$consulta = "
													SELECT idtipo_servicio, tipo
													FROM tipo_servicio
													where estado = 1
													order by tipo asc";

													// valor seleccionado
													if (isset($_POST['idtipo_servicio'])) {
														$value_selected = htmlentities($_POST['idtipo_servicio']);
													} else {
														$value_selected = $id_pais_nacional;
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
														'acciones' => '  onchange="verificar_tipo_servicio(this.value)" "' . $add,
														'autosel_1registro' => 'N'
													];

													// construye campo
													echo campo_select($consulta, $parametros_array);
													?>
												</div>
											</div>
										<?php } ?>

										<div class=" form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<div class="" style="display:flex;">
													<div class="dropdown " id="lista_proveedores">
														<select onclick="myFunction2(event)" class="form-control" id="idproveedor" name="idproveedor">
															<option value="" disabled selected></option>
															<?php if ($proveedor_nombre) { ?>
																<option value="<?php echo $idproveedor ?>" data-hidden-diasvence="<?php echo $diasvence ?>"><?php echo $proveedor_nombre ?></option>
															<?php } ?>
														</select>
														<input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12" type="text" placeholder="Nombre/ruc Proveedor" id="myInput2" onkeyup="filterFunction2(event)">
														<div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
															<?php echo $resultados_proveedores ?>
														</div>
													</div>
													<!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
														<span  class="fa fa-plus"></span> Agregar
													</a> -->
												</div>
											</div>
										</div>

										<div class="clearfix"></div>
										<!-- <div class="form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo OC *</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
											<select name="tipooc" id="tipooc" class="form-control" required onchange="verificar_tipo(this.value)">
											
											<option value="1" <?php // if($_POST['tipooc'] == 1 or $_POST['tipooc']==0){
																?> selected<?php // }
																			?>>Local</option>
											<option value="2" <?php //if($_POST['tipooc'] == 2){
																?> selected<?php // }
																			?>>Importacion</option>
											</select>
											</div>
										</div> -->

										<?php if ($proveedores_importacion == "S") { ?>
											<div class=" form-group">
												<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Origen *</label>
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
														$value_selected = $id_pais_nacional;
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
														'acciones' => ' required="required" onchange="verificar_tipo(this.value)" "' . $add,
														'autosel_1registro' => 'N'
													];

													// construye campo
													echo campo_select($consulta, $parametros_array);
													?>
												</div>
											</div>
										<?php } ?>
										<div class="form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Orden *</label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<input onblur="validar_fecha_orden(this.value);" type="date" name="fecha" id="fecha" value="<?php if (isset($_POST['fecha'])) {
																																				echo htmlentities($_POST['fecha']);
																																			} else {
																																				echo htmlentities(date('Y-m-d'));
																																			} ?>" placeholder="Fecha" class="form-control" required="required" />
											</div>
										</div>

										<div class="form-group">
											<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipocompra *</label>
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
													$value_selected = $id_pais_nacional;
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
													'acciones' => ' required="required"  "' . $add,
													'autosel_1registro' => 'N'
												];

												// construye campo
												echo campo_select($consulta, $parametros_array);
												?>
											</div>
										</div>

										<div class="form-group">
											<label style="white-space: nowrap;" class="control-label col-md-3 col-sm-3 col-xs-12 nowrap">Fecha de Entrega Estimada </label>
											<div class="col-md-9 col-sm-9 col-xs-12">
												<input onblur="validar_fecha_vencimiento()" type="date" name="fecha_entrega" id="fecha_entrega" value="<?php if (isset($_POST['fecha_entrega'])) {
																																							echo htmlentities($_POST['fecha_entrega']);
																																						} else {
																																							echo htmlentities($rs->fields['fecha_entrega']);
																																						} ?>" placeholder="Fecha entrega" class="form-control" />
											</div>
										</div>

										<?php if ($proveedores_importacion == "S") { ?>
											<div id="monedas" style="display:none;">
												<div class="form-group">
													<label class="control-label col-md-3 col-sm-3 col-xs-12">Moneda *</label>
													<div class="col-md-9 col-sm-9 col-xs-12">
														<?php

														// consulta
														$consulta = "
														SELECT idtipo, descripcion
														FROM tipo_moneda
														where
														estado = 1
														order by descripcion asc";

														// valor seleccionado
														if (isset($_POST['idtipo_moneda'])) {
															$value_selected = htmlentities($_POST['idtipo_moneda']);
														} else {
															$value_selected = $id_moneda_nacional;
														}
														if ($_GET['idmoneda'] > 0) {
															$add = "disabled";
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
															'acciones' => ' required="required" onchange="verificar_cotizacion_moneda(this.value)" "' . $add,
															'autosel_1registro' => 'N'
														];

														// construye campo
														echo campo_select($consulta, $parametros_array);
														?>
													</div>
												</div>
												<div class="form-group" id="box_cotizaciones">
													<?php require_once("./compras_ordenes_select_cotizacion.php") ?>
												</div>
											</div>
										<?php } ?>
										<div class="clearfix"></div>
										<br />

										<div class="form-group">
											<div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
												<button type="submit" class="btn btn-success"><span class="fa fa-check-square-o"></span> Registrar</button>
												<button type="button" class="btn btn-primary" onMouseUp="document.location.href='compras_ordenes.php'"><span class="fa fa-ban"></span> Cancelar</button>
											</div>
										</div>

										<input type="hidden" name="MM_insert" value="form1" />
										<input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
										<br />
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
	<script src="/compras_ordenes/scripts/compras_ordenes_add.php"></script>
</body>

</html>