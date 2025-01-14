<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");


//print_r($_POST);
//si manda un idconteo significa que quiere reabrir todas las sub ordenes del conteo
// y eliminar el conteo consolidado por seguridad ya que es solo una referencia sin datos relevantes
$tipo_conteo = "";
$iddeposito = "";
$idinsumo = "";
$idconteo = intval($_GET['idconteo']);

// si manda un idsub_conteo es un sub conteo unico que se abre quitandole la referencia pero
// manteniendo todas los otros sub conteos relacionados al conteo consolidado tal y como
// se encuentran pudiendo asi editar solo el sub conteo necesario
$idsub_conteo = intval($_GET['idsub_conteo']);

// si todo es correcto verifica para el update o para insertar
if ($idsub_conteo > 0) {
    //verificar
    $consulta = "SELECT idconteo_ref FROM conteo
	WHERE idconteo = $idsub_conteo
	";
    $rs_sub_conteo_ref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idconteo_referencia = $rs_sub_conteo_ref->fields['idconteo_ref'];

    $consulta = "UPDATE conteo
	SET idconteo_ref = 0
	WHERE idconteo = $idsub_conteo
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "SELECT tipo_conteo,iddeposito,idinsumo FROM conteo
	WHERE idconteo = $idsub_conteo
	";
    $rs_sub_conteo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_conteo = $rs_sub_conteo->fields['tipo_conteo'];
    $iddeposito = $rs_sub_conteo->fields['iddeposito'];
    $idinsumo = $rs_sub_conteo->fields['idinsumo'];

    /// si no hay detalles elimina el conteo consolidado
    $consulta = "SELECT count(conteo_detalles.unicose) as idconteo_ref
	from conteo_detalles
	INNER JOIN conteo on conteo.idconteo = conteo_detalles.idconteo
	LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = conteo_detalles.idpasillo
	INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
	INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
	INNER JOIN medidas on medidas.id_medida = conteo_detalles.idmedida_ref
	where 
	conteo.idconteo_ref = $idconteo_referencia
	and conteo.estado = 2
	ORDER BY conteo_detalles.idconteo, conteo_detalles.idalm 
	";
    $rs_sub_conteo_detalles = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $num_detalles = intval($rs_sub_conteo_detalles->fields['idconteo_ref']);

    if ($num_detalles == 0) {

        $consulta = "DELETE from conteo where idconteo = $idconteo_referencia
			";

        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

}


// si todo es correcto verifica para el update o para insertar
if ($idconteo > 0) {
    //verificar
    $consulta = "UPDATE conteo
		SET idconteo_ref = 0
		WHERE idconteo_ref = $idconteo
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "SELECT tipo_conteo,iddeposito,idinsumo FROM conteo
	WHERE idconteo = $idconteo
	";
    $rs_sub_conteo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_conteo = $rs_sub_conteo->fields['tipo_conteo'];
    $iddeposito = $rs_sub_conteo->fields['iddeposito'];
    $idinsumo = $rs_sub_conteo->fields['idinsumo'];

    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "DELETE from conteo where idconteo = $idconteo";

    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
if ($tipo_conteo == 2) {
    # conteo por producto que se encuentra en un deposito pero no asi en el mismo
    // alamacenamiento o tipo de almacenamiento

    header("location: conteo_por_producto_detalle.php?id=$iddeposito&idinsumo=$idinsumo");
    // header("location: conteo_stock_contar_producto.php?id=$idconteo&iddeposito=$iddeposito&idinsumo=$idinsumo");
} else {
    // tipo_conteo == 6 es para conteo normal pero eso es en otro modulo reutiliza la tabla por eso
    // es el if
}
exit;
