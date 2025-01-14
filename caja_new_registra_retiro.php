<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");




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
$montoentrega = floatval($_POST['montogs']);
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

    $consulta = "
	insert into gest_pagos
	(idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
	idtipocajamov,tipomovdinero)
	values
	($idcaja, '$ahora', 1, $montoentrega, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
	8,'S'
	)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
	select idpago from gest_pagos where idtipocajamov = 8 order by idpago desc limit 1
	";
    $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpago = $rsultpag->fields['idpago'];

    $consulta = "
	INSERT INTO gest_pagos_det
	(idpago, monto_pago_det, idformapago) 
	VALUES 
	($idpago, $montoentrega, 1)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    //registramos
    $insertar = "Insert into caja_retiros 
	(idcaja,cajero,fecha_retiro,monto_retirado,retirado_por,codigo_autorizacion,estado,obs,idempresa,idsucursal,idpago)
	values
	($idcaja,$idusu,'$ahora',$montoentrega,$autorizaid,$codigo,1,$obs,$idempresa,$idsucursal,$idpago)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    $buscar = "Select *,usuario from caja_retiros 
	inner join usuarios on usuarios.idusu=retirado_por 
	where cajero=$idusu order by fecha_retiro desc";
    $rsfr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idret = intval($rsfr->fields['regserialretira']);
    $totalret = intval($rsfr->fields['monto_retirado']);
    $quien = $rsfr->fields['usuario'];
    $obs = $rsfr->fields['obs'];




} // if ($valido == 'S'){


// genera array con los datos
$arr = [
'idretiro' => $idret,
'valido' => $valido,
'errores' => $errores
];
// convierte a formato json
$respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
// devuelve la respuesta formateada
echo $respuesta;
exit;
