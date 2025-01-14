<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "436";
require_once("includes/rsusuario.php");



$idclienteagrupadet = intval($_GET['id']);
if ($idclienteagrupadet == 0) {
    header("location: cliente_agrupacion_det.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from cliente_agrupacion_det 
where 
idclienteagrupadet = $idclienteagrupadet
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclienteagrupadet = intval($rs->fields['idclienteagrupadet']);
$idclienteagrupa = intval($rs->fields['idclienteagrupa']);
if ($idclienteagrupadet == 0) {
    header("location: cliente_agrupacion_det.php");
    exit;
}


// recibe parametros
//$idclienteagrupa=antisqlinyeccion($_POST['idclienteagrupa'],"int");


// validaciones basicas
$valido = "S";
$errores = "";


// si todo es correcto actualiza
if ($valido == "S") {

    $consulta = "
		update cliente_agrupacion_det
		set
			estado = 6,
			borrado_por = $idusu,
			borrado_el = '$ahora'
		where
			idclienteagrupadet = $idclienteagrupadet
			and estado = 1
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    header("location: cliente_agrupacion_det.php?id=$idclienteagrupa");
    exit;

}
