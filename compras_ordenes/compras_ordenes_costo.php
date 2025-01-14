<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$tipocosto = intval($_POST['tipocosto']); // 1 ultimo 2 contrato
$idprod = intval($_POST['idprod']);
$idproveedor = intval($_POST['idproveedor']);

//print_r($_POST);

if ($tipocosto == 1) {

    $consulta = "
	select costo 
	from insumos_lista 
	where 
	idinsumo = $idprod
	limit 1
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
} else {
    $consulta = "
	SELECT precio_costo as costo
	FROM lista_precios_costo_proveedores 
	where 
	idinsumo = $idprod 
	and idproveedor = $idproveedor
	and estado_pc = 1
	limit 1
	";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

echo floatval($rs->fields['costo']);
