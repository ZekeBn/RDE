<?php
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$eliminar_proforma = intval($_POST["eliminar_proforma"]);

if (intval($ocnum) == 0) {
	$ocn = intval($_POST['ocn']);
} else {
	$ocn = $ocnum;
}

if ($eliminar_proforma == 1) {
	$idproforma = intval($_POST["idproforma"]);
	$buscar = "
		select compras.idcompra from compras
		where 
		compras.ocnum = $idproforma
		and compras.estado=1";
	$rs_proforma = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

	$idcompra = intval($rs_proforma->fields['idcompra']);
	$valido = "S";

	if ($idcompra > 0) {
		$valido = "N";
		$errores = "Nose puede eliminar  la proforma ya que posee compras asociadas";
	}
	if ($valido == "S") {
		$buscar = "
		update embarque
				set 
					estado=6
				where 
					ocnum = $idproforma";
		$rs_proforma = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

		$buscar = "
			update compras_ordenes
			set 
				estado=6
			where 
				ocnum = $idproforma";
		$rs_proforma = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

		$valido = "S";
	}
}

//Traemos los productos seleccionados para la compra
$buscar = "
select compras_ordenes.ocnum, embarque.estado_embarque, compras_ordenes.fecha, puertos.descripcion as puerto , embarque.idembarque , compras_ordenes.estado_orden, compras.idcompra
from compras_ordenes 
LEFT JOIN embarque on embarque.ocnum = compras_ordenes.ocnum and embarque.estado=1
LEFT JOIN puertos on puertos.idpuerto = embarque.idpuerto
LEFT JOIN compras on compras.ocnum = compras_ordenes.ocnum
where 
compras_ordenes.ocnum_ref =$ocn
and compras_ordenes.estado = 2";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$estado_orden = intval($rs->fields['estado_orden']);
?>

<br />

<style>
	.sin_embarque {
		background: #ce2d4fa8;
		font-weight: bold;
	}

	.sin_embarque:hover {
		background: #ce2d4f;
		color: #000;
		font-weight: bold;
	}

	.con_embarque {
		background: #D7FFAB;
		font-weight: bold;
	}

	.con_embarque:hover {
		background: #C3EB97;
		color: #000;
		font-weight: bold;
	}

	.transito {
		background: #ce2d4fa8;
		color: white;
		font-weight: bold;

	}

	.completo {
		background: #D7FFAB;
		color: #405467;
		font-weight: bold;
	}
</style>
<script>
	function alerta_modal(titulo, mensaje) {
		$('#modal_ventana').modal('show');
		$("#modal_titulo").html(titulo);
		$("#modal_cuerpo").html(mensaje);
	}

	function crear_suborden() {
		var ocn = <?php echo $ocn ?>;

		var parametros = {
			"ocn": ocn
		};
		$.ajax({
			data: parametros,
			url: 'crear_sub_ordenes.php',
			type: 'post',
			beforeSend: function() {
				// $("#generar_compra").text('Cargando...');

			},
			success: function(response) {
				console.log(response);
				if (JSON.parse(response)["success"] == false) {
					if (parseInt(JSON.parse(response)["ocnum"]) > 0) {
						document.location.href = 'compras_ordenes_det.php?id=' + JSON.parse(response)["ocnum"];
					} else {

						alerta_modal("Error", JSON.parse(response)["error"])
					}

				}
				if (JSON.parse(response)["success"] == true) {
					document.location.href = 'compras_ordenes_det.php?id=' + JSON.parse(response)["ocnum"];

				}
			}
		});
	}
</script>
<?php if (trim($errores) != "") { ?>
	<div class="alert alert-danger alert-dismissible fade in" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
		</button>
		<strong>Errores:</strong><br /><?php echo $errores; ?>
	</div>
<?php } ?>
<div class="table-responsive">
	<h2>Proformas Asociadas</h2>
	<?php if ($estado_orden != 2) { ?>
		<a href="javascript:void(0);" onclick="crear_suborden()" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Proforma</a>
	<?php } ?>
	<hr>
	<br>
	<div class="clearfix"></div>
	<table width="100%" class="table table-bordered jambo_table bulk_action">
		<thead>
			<tr>
				<th align="center"><strong>Orden ID:</strong></th>
				<th align="center"><strong>Fecha</strong></th>
				<th align="center"><strong>Puerto</strong></th>
				<th align="center"><strong>Estado</strong></th>
				<th align="center"><strong>Embarque</strong></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$tot = 0;

			while (!$rscu->EOF) {

				$puerto = $rscu->fields['puerto'];
				$fechacompra = $rscu->fields['fecha'];
				$idembarque = intval($rscu->fields['idembarque']);
				$subocnum = $rscu->fields['ocnum'];
				$estado_embarque = $rscu->fields['estado_embarque'];
				$idcompra = $rscu->fields['idcompra'];

				$class_css = "";
				if ($idembarque > 0) {

					$class_css = "con_embarque";
				} else {
					$class_css = "sin_embarque";
				}
			?>
				<tr>
					<td align="center">
						<h6><?php echo trim($subocnum) ?></h6>
					</td>
					<td align="center">
						<h6><?php echo trim($fechacompra) ?></h6>
					</td>
					<td align="center">
						<h6><?php echo trim($puerto) ?></h6>
					</td>
					<td align="center" class="<?php if ($estado_embarque != 0) {
													echo trim($estado_embarque) == 1 ? "activo" : "finalizado";
												} ?>">
						<h6 style="font-weight: bold;"><?php if ($estado_embarque != 0) {
															echo trim($estado_embarque) == 1 ? "ACTIVO" : "FINALIZADO";
														} ?></h6>
					</td>
					<td align="center">
						<a href="javascript:void(0);" class="btn btn-sm btn-default <?php echo $class_css; ?> " title="Embarque" data-toggle="tooltip" onmouseup="document.location.href='../embarque/embarque_add.php?ocn=<?php echo trim($subocnum) ?>&ocn_ref=<?php echo $ocn ?>'"><span class="fa fa-ship"></span></a>
						<a href="inf_ocdetallev2_pdf.php?idoc=<?php echo $subocnum; ?>" target="_blank" class="btn btn-sm btn-default" title="Imprimir A4" data-toggle="tooltip" data-placement="right" data-original-title="Imprimir A4"><span class="fa fa-print"></span></a>
						<a href="javascript:void(0);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle" onclick="ver_orden_detalle(<?php echo trim($subocnum) ?>);"><span class="fa fa-search"></span></a>
						<?php if ($idcompra == 0) { ?>
							<a href="javascript:void(0);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Eliminar" onclick="eliminar_proforma(<?php echo trim($subocnum) ?>);"><span class="fa fa-trash"></span></a>
						<?php } ?>
					</td>
				</tr>
			<?php $rscu->MoveNext();
			} ?>
		</tbody>
	</table>
</div>