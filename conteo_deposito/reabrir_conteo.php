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

// si manda un idsub_conteo es un sub conteo unico que se abre quitandole la referencia pero
// manteniendo todas los otros sub conteos relacionados al conteo consolidado tal y como
// se encuentran pudiendo asi editar solo el sub conteo necesario
$idsub_conteo = intval($_GET['idsub_conteo']);

// si todo es correcto verifica para el update o para insertar
if ($idsub_conteo > 0) {
    //verificar
    $consulta = "UPDATE conteo
	SET idconteo_ref = 0,
    estado=1
	WHERE idconteo = $idsub_conteo
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "SELECT tipo_conteo,iddeposito,idinsumo,idconteo FROM conteo
	WHERE idconteo = $idsub_conteo
	";
    $rs_sub_conteo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipo_conteo = $rs_sub_conteo->fields['tipo_conteo'];
    $iddeposito = $rs_sub_conteo->fields['iddeposito'];
    $idinsumo = $rs_sub_conteo->fields['idinsumo'];
    $idconteo = $rs_sub_conteo->fields['idconteo'];

}


if ($tipo_conteo == 2) {
    # conteo por producto que se encuentra en un deposito pero no asi en el mismo
    // alamacenamiento o tipo de almacenamiento

    // header("location: conteo_por_producto_detalle.php?id=$iddeposito&idinsumo=$idinsumo");
    header("location: conteo_stock_contar_producto.php?id=$idconteo&iddeposito=$iddeposito&idinsumo=$idinsumo");
} else {
    // tipo_conteo == 6 es para conteo normal pero eso es en otro modulo reutiliza la tabla por eso
    // es el if
}
exit;
