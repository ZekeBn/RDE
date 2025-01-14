<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_compras.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "107";
//error_reporting(E_ALL);
require_once("../includes/rsusuario.php");
// consulta a la tabla

if ($idcompra == 0) {
    if (intval($_POST['idcompra']) != 0) {
        $idcompra = intval($_POST['idcompra']);
    }
    if (intval($_GET['idcompra']) != 0) {
        $idcompra = intval($_GET['idcompra']);
    }
}
$errores = "";
$valido = "S";

$editar_deposito = ($_POST['editar_deposito']);



$consulta = "
SELECT despacho.cotizacion as cot_despacho FROM despacho WHERE idcompra = $idcompra 
";
$rs_despa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$cot_despacho = floatval($rs_despa->fields['cot_despacho']);




if ($editar_deposito == 1) {
    $iddeposito = $_POST['iddeposito'];
    $idcompra = $_POST['idcompra'];
    $idregs = $_POST['idregs'];
    $idempresa = $_POST['idempresa'];
    if ($iddeposito == 0) {
        $valido = "N";
        $errores .= "Favor elegir un deposito";

    }

    if ($valido == "S") {
        $consulta = "
        UPDATE compras_detalles 
        SET iddeposito_compra = $iddeposito
        WHERE 
        idregs = $idregs and
        idcompra = $idcompra and
        idempresa = $idempresa
        ";

        $res = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
}
$consulta = "
select compras_detalles.*, compras.usa_cot_despacho , compras_detalles.costo as costo, insumos_lista.descripcion as descripcion, 
(select cn_conceptos.descripcion from cn_conceptos where cn_conceptos.idconcepto = insumos_lista.idconcepto) as concepto,
(select descripcion from gest_depositos where iddeposito=compras_detalles.iddeposito_compra) as deposito_por_defecto
from compras_detalles 
inner join insumos_lista on insumos_lista.idinsumo = compras_detalles.codprod
inner join compras on compras.idcompra = compras_detalles.idcompra
where 
compras_detalles.idcompra = $idcompra
order by insumos_lista.descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$buscar = "Select * from preferencias_compras limit 1";
$rsprefecompras = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$depodefecto = trim($rsprefecompras->fields['usar_depositos_asignados']);


?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">C&oacute;digo</th>
            <th align="center">Producto</th>
			<?php if ($depodefecto == 'S') { ?>
			<th align="center">Depo&sacute;ito asignado</th>
			<?php } ?>
            <th align="center">Concepto</th>
			<th align="center">Cantidad</th>
			<th align="center">Costo <?php if ($idmoneda_select != $id_moneda_nacional) { ?>Moneda Nacional<?php } ?></th>
			<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
				<th align="center">Costo <?php echo $nombre_moneda?></th>
			<?php } ?>

			<th align="center">Subtotal <?php if ($idmoneda_select != $id_moneda_nacional) { ?>Moneda Nacional<?php } ?></th>
			<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
				<th align="center">Subtotal <?php echo $nombre_moneda?></th>
			<?php } ?>

			<th align="center">Iva %</th>
			<th align="center">Lote</th>
			<th align="center">Vencimiento</th>
			<th></th>
			
			
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>


			<td align="center"><?php echo antixss($rs->fields['idinsumo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<?php if ($depodefecto == 'S') { ?>
			<td align="center"><?php echo antixss($rs->fields['deposito_por_defecto']); ?></td>
			<?php } ?>
			<td align="center"><?php echo antixss($rs->fields['concepto']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>

			<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
				<?php if ($rs->fields['usa_cot_despacho'] == "S") { ?>
					<td align="right"><?php echo formatomoneda(($rs->fields['costo'] / $cotizacion) * $cot_despacho) ;  ?></td>
				<?php } else { ?>
					<td align="right"><?php echo formatomoneda(($rs->fields['costo'])) ;  ?></td>
				<?php } ?>
			<?php } else { ?>
				<td align="right"><?php echo formatomoneda($rs->fields['costo']);  ?></td>
			<?php } ?>
			

			<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
				<?php if ($rs->fields['usa_cot_despacho'] == "S") { ?>
					<td align="right"><?php echo formatomoneda($rs->fields['costo'] / $cot_despacho, "2", "S");  ?></td>
				<?php } else { ?>
					<td align="right"><?php echo formatomoneda($rs->fields['costo'] / $cotizacion, "2", "S");  ?></td>
				<?php } ?>
			<?php }?>

			<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
				<?php if ($rs->fields['usa_cot_despacho'] == "S") { ?>
					<td align="right"><?php echo formatomoneda(($rs->fields['subtotal'] / $cotizacion) * $cot_despacho) ;  ?></td>
				<?php } else { ?>
					<td align="right"><?php echo formatomoneda($rs->fields['subtotal']) ;  ?></td>
				<?php } ?>
			<?php } else { ?>
				<td align="right"><?php echo formatomoneda($rs->fields['subtotal']);  ?></td>
			<?php } ?>

			<?php if ($idmoneda_select != $id_moneda_nacional) { ?>
				<?php if ($rs->fields['usa_cot_despacho'] == "S") { ?>
					<td align="right"><?php echo formatomoneda($rs->fields['subtotal'] / $cot_despacho, "2", "S");  ?></td>
				<?php } else { ?>
					<td align="right"><?php echo formatomoneda($rs->fields['subtotal'] / $cotizacion, "2", "S");  ?></td>
				<?php } ?>
			<?php }?>

			<td align="center"><?php echo intval($rs->fields['iva']); ?>%</td>
			<td align="center"><?php echo antixss($rs->fields['lote']); ?></td>
			<td align="center"><?php if ($rs->fields['vencimiento'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['vencimiento']));
			} ?></td>
			<td align="center">
					<!-- <a href="javascript:void(0);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-search"></span></a> -->
					<a href="javascript:void(0);" onclick="editar_deposito_compra(event,<?php echo $rs->fields['idcompra'] ?>,<?php echo $rs->fields['idregs']?>,<?php echo $rs->fields['idempresa'] ?>,'<?php echo $rs->fields['descripcion'] ?>');" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
			</td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>

<?php
// Código PHP


// Verificar si la cadena no es nula

if (($errores) != "") {

    // Generar el código JavaScript con jQuery
    $script = "
    <script>
            $('#titulovError').html('Error');
            $('#cuerpovError').html('$errores');	
			$('#ventanamodalError').modal('show');
    </script>
    ";
    // Imprimir el código JavaScript generado
    echo $script;
}
?>
    
