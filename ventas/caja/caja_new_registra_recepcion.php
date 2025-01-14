<?php

require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");




$idcaja = intval($_POST['idcaja']);
//echo $idcaja;
if (intval($idcaja) == 0) {
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




// validaciones basicas
$valido = "S";
$errores = "";


// recibe valores
$codigo = md5(trim($_POST['codigoau']));
$codigo = antisqlinyeccion($codigo, 'clave');
$obs = antisqlinyeccion($_POST['obs'], 'text');
$montorecibe = floatval($_POST['montogs']);
$copias = intval($_POST['canticopias']);
$imprimir = trim($_POST['imprimir']);

// validaciones
if (trim($_POST['codigoau']) == '') {
    $valido = "N";
    $errores .= "- No se envio el codigo de autorizacion.".$saltolinea;
}
if (floatval($_POST['montogs']) <= 0) {
    $valido = "N";
    $errores .= "- El monto no puede ser cero.".$saltolinea;
}



$buscar = "Select * from usuarios_autorizaciones where codauto=$codigo and estado=1";
$rscod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$autorizaid = intval($rscod->fields['idusu']);
$imprimetk = trim($rscod->fields['imprimetk']);
if (trim($_POST['codigoau']) != '') {
    if ($autorizaid == 0) {
        $valido = "N";
        $errores .= "- Codigo de autorizacion incorrecto.".$saltolinea;
    }
}

// conversiones
if ($copias == 0) {
    $copias = 1;
}
if ($copias > 5) {
    $copias = 1;
}

if ($valido == 'S') {


    $buscar = "Select * from usuarios_autorizaciones where codauto=$codigo";
    $rscod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $autorizaid = intval($rscod->fields['idusu']);
    $imprimetk = trim($rscod->fields['imprimetk']);

    $consulta = "
	insert into gest_pagos
	(idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
	idtipocajamov,tipomovdinero)
	values
	($idcaja, '$ahora', 1, $montorecibe, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
	7,'E'
	)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select idpago from gest_pagos where idtipocajamov = 7 order by idpago desc limit 1
	";
    $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpago = $rsultpag->fields['idpago'];

    $consulta = "
	INSERT INTO gest_pagos_det
	(idpago, monto_pago_det, idformapago) 
	VALUES 
	($idpago, $montorecibe, 1)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //registramos
    $insertar = "Insert into caja_reposiciones 
	(idcaja,cajero,fecha_reposicion,monto_recibido,entregado_por,codigo_autorizacion,estado,obs,idempresa,idsucursal,idpago)
	values
	($idcaja,$idusu,current_timestamp,$montorecibe,$autorizaid,$codigo,1,$obs,$idempresa,$idsucursal,$idpago)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    $consulta = "
	select max(regserialentrega ) as max  from caja_reposiciones where cajero = $idusu
	";
    $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $regserialentrega = $rsmax->fields['max'];

} // if ($valido == 'S'){


// genera array con los datos
$arr = [
'idrecepcion' => $regserialentrega,
'valido' => $valido,
'errores' => $errores
];
// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
// devuelve la respuesta formateada
echo $respuesta;
exit;
