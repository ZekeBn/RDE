<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");




$idcaja = intval($_POST['idcaja']);
if ($idcaja == 0) {
    // genera array con los datos
    $arr = [
    'valido' => 'N',
    'errores' => '- No se recibio el idcaja.'.$saltolinea
    ];
    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    // devuelve la respuesta formateada
    echo $respuesta;
    exit;
}


//Comprobar apertura de caja
$parametros_caja_new = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res_caja = caja_abierta_new($parametros_caja_new);
$idcaja = intval($res_caja['idcaja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja_new.php'/>" 	;
    exit;
}
/*
// consulta a la tabla
$consulta="
select *
from caja_super
where
idcaja = $idcaja
and estado = 1
limit 1
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idcaja=intval($rs->fields['idcaja']);*/
if ($idcaja == 0) {
    // genera array con los datos
    $arr = [
    'valido' => 'N',
    'errores' => '- No se encontro el idcaja.'.$saltolinea
    ];
    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    // devuelve la respuesta formateada
    echo $respuesta;
    exit;
}

$consulta = "SELECT * FROM preferencias limit 1 ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$obligaprov = trim($rspref->fields['obligaprov']);
$impresor = trim($rspref->fields['script_ticket']);
$hab_monto_fijo_chica = trim($rspref->fields['hab_monto_fijo_chica']);
$hab_monto_fijo_recau = trim($rspref->fields['hab_monto_fijo_recau']);
$muestraventasciega = trim($rspref->fields['muestra_ventas_ciega']);
$usacajachica = trim($rspref->fields['usa_cajachica']);
$pagoxcajarec = trim($rspref->fields['pagoxcaja_rec']);
$pagoxcajachica = trim($rspref->fields['pagoxcaja_chic']);


// recibe parametros
//Pagos x caja

//print_r($_POST);exit;
$montoabonado = floatval($_POST['montopagoxcaja']);
$concepto = antisqlinyeccion($_POST['obspago'], 'text');
$factu = antisqlinyeccion($_POST['nfactu'], 'text');
$idprovi = intval($_POST['minip']);
$tipocaja = strtoupper(substr(trim($_POST['tipocajapag']), 0, 1));

// validaciones de tipo de caja
// si usa solo caja chica
if ($rspref->fields['pagoxcaja_chic'] == 'S' && $rspref->fields['pagoxcaja_rec'] == 'N') {
    $tipocaja = "C";
}
// si usa solo caja recaudacion
if ($rspref->fields['pagoxcaja_chic'] == 'N' && $rspref->fields['pagoxcaja_rec'] == 'S') {
    $tipocaja = "R";
}
// si usa ambas
if ($rspref->fields['pagoxcaja_chic'] == 'S' && $rspref->fields['pagoxcaja_rec'] == 'S') {
    // evita hack
    if ($tipocaja != 'R' && $tipocaja != 'C') {
        $tipocaja = "R";
    }
}
// si no tiene habilitado ninguno
if ($rspref->fields['pagoxcaja_chic'] == 'N' && $rspref->fields['pagoxcaja_rec'] == 'N') {
    $valido = "N";
    $errores .= "- No tienes permisos para realizar pagos por caja.".$saltolinea;
}

$errores = '';
$valido = "S";
if ($montoabonado == 0) {
    $valido = "N";
    $errores .= "- Debe indicar monto abonado.".$saltolinea;
}
if ($concepto == 'NULL') {
    $valido = "N";
    $errores .= "- Debe indicar motivo del pago.".$saltolinea;
}
if (($obligaprov == 'S') && ($idprovi == 0)) {
    $valido = "N";
    $errores .= "- Debe indicar proveedor de factura.".$saltolinea;

}
if ($valido == 'S') {

    $consulta = "
	insert into gest_pagos
	(idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
	idtipocajamov,tipomovdinero)
	values
	($idcaja, '$ahora', 1, $montoabonado, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
	9,'S'
	)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select idpago from gest_pagos where idtipocajamov = 9 order by idpago desc limit 1
	";
    $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpago = $rsultpag->fields['idpago'];

    $consulta = "
	INSERT INTO gest_pagos_det
	(idpago, monto_pago_det, idformapago) 
	VALUES 
	($idpago, $montoabonado, 1)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    $insertar = "Insert  into pagos_extra 
	(fecha,idcaja,monto_abonado,concepto,idusu,factura,idempresa,idprov,estado,tipocaja,idpago)
	values
	('$ahora',$idcaja,$montoabonado,$concepto,$idusu,$factu,$idempresa,$idprovi,1,'$tipocaja',$idpago)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    //echo $insertar;exit;

    $consulta = "
	select max(unis) as unis from pagos_extra where idusu = $idusu
	";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpagoex = $rspag->fields['unis'];


}



// genera array con los datos
$arr = [
'unis' => $idpagoex,
'valido' => $valido,
'errores' => $errores
];

//print_r($arr);

// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

// devuelve la respuesta formateada
echo $respuesta;
exit;
