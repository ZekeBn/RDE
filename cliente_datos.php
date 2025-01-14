<?php

/*-----------------------------PARA USAR CON TIPO DE VENTA SUPERMERCADOS-----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$idcliente = intval($_POST['id']);

$buscar = "
Select 
ruc, razon_social 
from cliente 
where 
idempresa = $idempresa
and idcliente = $idcliente 
and estado <> 6
order by razon_social asc limit 20";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tcli = $rscli->RecordCount();

echo $rscli->fields['ruc'].'-/-'.$rscli->fields['razon_social'];
