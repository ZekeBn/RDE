<?php

function calcular_iva_tipos($parametros_array)
{

    $idtipoiva = intval($parametros_array['idtipoiva']);
    // llamar a la funcion del calculo de iva dependiendo de cual funcion corresponda
    if (in_array($idtipoiva, [1,2,3,4])) {
        $res = iva_baseimponible($parametros_array);
    } elseif (in_array($idtipoiva, [5,6])) {
        $res = iva_montoivaincluido($parametros_array);
    } else {
        echo "Tipo de iva no indicado en funciones_iva.php.";
        exit;
    }

    return $res;

}


// calcula el iva sobre la base imponible
function iva_baseimponible($parametros_array)
{

    global $conexion;
    global $ahora;

    $idtipoiva = intval($parametros_array['idtipoiva']);
    $monto_ivaincluido = floatval($parametros_array['monto_ivaincluido']);
    $subtotal = $monto_ivaincluido;

    $consulta = "
	select idtipoiva, iva_porc, iva_describe, iguala_compra_venta, 
	estado, hab_compra, hab_venta
	from tipo_iva 
	where
	idtipoiva = $idtipoiva
	";
    $rsbimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iva_porc = floatval($rsbimp->fields['iva_porc']); // alicuota
    $subtotal_monto_iva = calcular_iva($iva_porc, $subtotal);
    $base_imponible = $subtotal - $subtotal_monto_iva;



    $consulta = "
	SELECT 
	idtipoiva, iva_porc, monto_porc, exento 
	FROM tipo_iva_detalle 
	WHERE 
	idtipoiva = $idtipoiva
	";
    $rsivadet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $res = [];
    $i = 1;
    while (!$rsivadet->EOF) {

        $gravadoml = $base_imponible * ($rsivadet->fields['monto_porc'] / 100);
        $ivaml = $gravadoml * ($rsivadet->fields['iva_porc'] / 100);
        $exento = $rsivadet->fields['exento'];
        $iva_porc_col = $rsivadet->fields['iva_porc'];
        $monto_col = $gravadoml + $ivaml;

        $res[$i]['gravadoml'] = $gravadoml;
        $res[$i]['ivaml'] = $ivaml;
        $res[$i]['exento'] = $exento;
        $res[$i]['iva_porc_col'] = $iva_porc_col;
        $res[$i]['monto_col'] = $monto_col;


        $i++;
        $rsivadet->MoveNext();
    }

    return $res;
}
// calcula el iva sobre la base imponible
function iva_montoivaincluido($parametros_array)
{

    global $conexion;
    global $ahora;

    $idtipoiva = intval($parametros_array['idtipoiva']);
    $monto_ivaincluido = floatval($parametros_array['monto_ivaincluido']);

    $consulta = "
	select idtipoiva, iva_porc, iva_describe, iguala_compra_venta, 
	estado, hab_compra, hab_venta
	from tipo_iva 
	where
	idtipoiva = $idtipoiva
	";
    $rsbimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iva_porc = floatval($rsbimp->fields['iva_porc']); // alicuota
    $subtotal_monto_iva = calcular_iva($iva_porc, $subtotal);
    $base_imponible = $subtotal - $subtotal_monto_iva;



    $consulta = "
	SELECT 
	idtipoiva, iva_porc, monto_porc, exento 
	FROM tipo_iva_detalle 
	WHERE 
	idtipoiva = $idtipoiva
	order by iva_porc desc
	";
    $rsivadet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $res = [];
    $i = 1;
    while (!$rsivadet->EOF) {

        $monto_col = $monto_ivaincluido * ($rsivadet->fields['monto_porc'] / 100);
        $divisor_iva = calcular_iva_divisor($rsivadet->fields['iva_porc']);
        $ivaml = $monto_col / $divisor_iva;
        $gravadoml = $monto_col - $ivaml;
        $exento = $rsivadet->fields['exento'];
        $iva_porc_col = $rsivadet->fields['iva_porc'];


        $res[$i]['gravadoml'] = $gravadoml;
        $res[$i]['ivaml'] = $ivaml;
        $res[$i]['exento'] = $exento;
        $res[$i]['iva_porc_col'] = $iva_porc_col;
        $res[$i]['monto_col'] = $monto_col;


        $i++;
        $rsivadet->MoveNext();
    }

    return $res;
}
