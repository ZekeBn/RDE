<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");


// print_r($_POST);
// recibe parametros
$descripcion = antisqlinyeccion($_POST['descripcion'], "text");
$nacional = antisqlinyeccion($_POST['nacional'], "text");
$cotiza = antisqlinyeccion($_POST['cotiza'], "int");



// validaciones basicas
$valido = "S";
$errores = "";


if ($descripcion == '') {
    $valido = "N";
    $errores .= " - El campo Denominaci&oacute;n no puede estar vac&iacute;o.<br />";
}
if ($cotiza != 1 && $cotiza != 2) {
    $valido = "N";
    $errores .= " - El campo Cotiza no puede estar vac&iacute;o.<br />";
}
if ($cotiza == 1 && $nacional == "S") {
    $valido = "N";
    $errores .= " - El campo Cotiza no puede ser si cuando es una Moneda Nacional.<br />";
}
if ($nacional == 'S' && $cotiza == 1) {
    $valido = "N";
    $errores .= " - El campo Cotiza no puede estar activado si es una moneda local.<br />";
}
if ($nacional == '') {
    $valido = "N";
    $errores .= " - El campo Moneda por defecto no puede estar vac&iacute;o.<br />";
}

$consulta = "
	select * 
	from tipo_moneda 
	where
	estado = 1
	and idempresa = $idempresa
	and descripcion = $descripcion
	";
$rsrepe = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rsrepe->fields['idtipo'] > 0) {
    $valido = "N";
    $errores .= " - Ya existe otra moneda con la misma denominaci&oacute;n.<br />";
}

// si todo es correcto actualiza
if ($valido == "S") {



    /////////////////fin imagen

    if ($nacional == 'S') {
        $consulta = "
			update tipo_moneda
			set
				nacional='N'
			where
				nacional='S'
				";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    $consulta = "
		insert into tipo_moneda
		(descripcion, estado, banderita, borrable, idempresa,nacional,cotiza)
		values
		($descripcion, 1, NULL, 'S', $idempresa, $nacional,$cotiza)
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
echo json_encode(["errores" => $errores,"valido" => $valido]);
