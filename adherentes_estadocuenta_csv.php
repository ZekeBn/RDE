<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");
if (intval($_GET['id']) == 0) {
    echo "No indico el cliente.";
    exit;
}

function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return utf8_decode($txt);
}


$idcliente = intval($_GET['id']);
$consulta = "
select * 
from cliente 
where 
idcliente = $idcliente
and idempresa = $idempresa
";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rscli->fields['idcliente']);
if (intval($idcliente) == 0) {
    echo "Cliente inexistente.";
    exit;
}


//filtros
$whereadd = "";
$orderby = "";
// filtro fijo
if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}

// otros filtos
if (intval($_GET['adh']) > 0) {
    $idadherente = antisqlinyeccion(trim($_GET['adh']), "int");
    $whereadd .= " and cuentas_clientes.idadherente = $idadherente ".$saltolinea;
}
if (intval($_GET['sc']) > 0) {
    $idserviciocom = antisqlinyeccion(trim($_GET['sc']), "int");
    $whereadd .= " and cuentas_clientes.idserviciocom = $idserviciocom ".$saltolinea;
}




$consulta = "
SELECT 
cuentas_clientes.idventa , cuentas_clientes.idcta, date(prox_vencimiento) as fecha, 
(select nomape from adherentes where adherentes.idadherente = cuentas_clientes.idadherente) as adherente,
(select nombre_servicio from servicio_comida where servicio_comida.idserviciocom = cuentas_clientes.idserviciocom) as servicio,
REPLACE(COALESCE(cuentas_clientes.deuda_global,0),'.',',') as monto_ticket, 
REPLACE(COALESCE((select sum(cuentas_clientes_pagos.monto_abonado) from cuentas_clientes_pagos where idcuenta = cuentas_clientes.idcta ),0),'.',',') as monto_abonado, 
REPLACE(COALESCE(cuentas_clientes.saldo_activo,0),'.',',') as saldo_activo, 
(select date(cuentas_clientes_pagos.fecha_pago) as fecha_pago from cuentas_clientes_pagos where idcuenta = cuentas_clientes.idcta order by cuentas_clientes_pagos.fecha_pago desc limit 1) as ultimo_pago
FROM cuentas_clientes
where 
cuentas_clientes.idcliente = $idcliente
and cuentas_clientes.estado <> 6
and date(cuentas_clientes.prox_vencimiento) >= '$desde'
and date(cuentas_clientes.prox_vencimiento) <= '$hasta'
$whereadd
order by date(prox_vencimiento) asc
";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$impreso = date("d/m/Y H:i:s");



$datos = "";


// asigna los datos de la consulta a una variable
$array = $rs->fields;

// CONSTRUYE CABECERA
$array = $rs->fields;
foreach ($array as $key => $value) {
    $i++;
    $datos .= limpiacsv($key).';';
}
reset($array);
$datos .= $saltolinea;

//CONSTRUYE CUERPO
$ante = 0;
$fila = 1;
while (!$rs->EOF) {
    $fila++;
    $array = $rs->fields;
    $i = 0;
    foreach ($array as $key => $value) {
        $i++;
        $datos .= limpiacsv($value).';';
    }
    $datos .= $saltolinea;
    $rs->MoveNext();
}



$impreso = date("d/m/Y H:i:s");

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=adherente_cuenta_'.$impreso.'.csv');

echo $datos;
exit;
