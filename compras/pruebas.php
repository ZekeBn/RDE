<?php

require_once("../includes/conexion.php");
$idcompra = intval($_GET['id']);

$buscar = "Select usa_cot_despacho from compras where idcompra = $idcompra";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_cot_despacho_factura = $rs->fields['usa_cot_despacho'];
echo $buscar . "\n";
echo $usa_cot_despacho_factura;
