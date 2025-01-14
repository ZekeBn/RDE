<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "212";
require_once("includes/rsusuario.php");

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}


$idventa = intval($_GET['idventa']);
$id_app_rider = 1; // delorean

// preferencias caja
$consulta = "
SELECT 
usa_motorista, obliga_motorista, valida_duplic_tipo,
 obliga_cod_transfer
FROM preferencias_caja 
WHERE  
idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_motorista = trim($rsprefcaj->fields['usa_motorista']);
$obliga_motorista = trim($rsprefcaj->fields['obliga_motorista']);
$valida_duplic_tipo = trim($rsprefcaj->fields['valida_duplic_tipo']);
$obliga_cod_transfer = trim($rsprefcaj->fields['obliga_cod_transfer']);


$consulta = "
select *, 
(select nombres from cliente_delivery where idclientedel = ventas.idclientedel) as nombres,
(select apellidos from cliente_delivery where idclientedel = ventas.idclientedel) as apellidos,
(select telefono from cliente_delivery where idclientedel = ventas.idclientedel) as telefono,
(select direccion from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as direccion,
(select latitud from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as latitud,
(select longitud from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as longitud,
(select idestadodelivery from tmp_ventares_cab where tmp_ventares_cab.idventa = ventas.idventa) as idestadodelivery,
(
select sum(subtotal) 
from ventas_detalles
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where
ventas_detalles.idventa = ventas.idventa 
and productos.idtipoproducto = 6
) as monto_delivery,
(
select  gest_pagos_det.idformapago 
from gest_pagos 
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago 
where 
gest_pagos.idventa = ventas.idventa
order by idpagodet asc
limit 1
) as idformapago
from ventas 
where 
idcanal = 3 
and idcaja = $idcaja
and idventa = $idventa
order by fecha desc
limit 1
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idestadodelivery = $rs->fields['idestadodelivery'];
$idmotorista = $rs->fields['idmotorista'];
$idformapago = intval($rs->fields['idformapago']);
if ($rs->fields['idventa'] == 0) {
    header("location: delivery_micaja.php");
    exit;
}
$consulta = "
select idventatmp, idprod_serial,
CASE WHEN 
	ventas_detalles.pchar IS NULL
THEN
	productos.descripcion
ELSE	
	ventas_detalles.pchar
END as producto,  
ventas_detalles.pchar,
productos.idtipoproducto,
CASE WHEN sum(cantidad) > 0 THEN sum(subtotal)/sum(cantidad) ELSE pventa END as pventa,  
sum(cantidad) as cantidad, 
(sum(subtotal)-(sum(subtotal)/(1+iva/100))) as iva_monto, iva, barcode,
 sum(subtotal) as subtotal,
 max(idventadet) as idventadet
from ventas_detalles 
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where 
ventas_detalles.idventa = $idventa
GROUP by idprod_serial, 
CASE WHEN 
	ventas_detalles.pchar IS NULL
THEN
	productos.descripcion
ELSE	
	ventas_detalles.pchar
END,
ventas_detalles.pchar,
 iva, barcode, productos.idtipoproducto
order by max(idventadet) asc
";
$rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
while (!$rsdet->EOF) {
    $detalle_ped .= floatval($rsdet->fields['cantidad']).' x '.trim($rsdet->fields['producto']).", ";
    $rsdet->MoveNext();
}
$detalle_ped = trim($detalle_ped);
$detalle_ped = substr($detalle_ped, 0, -1);

// delorean
$consulta = "
select app_rider.id_app_rider, app_rider.token_api, app_rider_sucursales.idsucursal_app
from  app_rider_sucursales
inner join app_rider on app_rider.id_app_rider  =  app_rider_sucursales.id_app_rider
where 
app_rider_sucursales.idsucursal = $idsucursal
and app_rider.estado = 1
and app_rider_sucursales.estado = 1
and app_rider.id_app_rider = $id_app_rider
limit 1
";
$rsdelo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsdelo->fields['id_app_rider']) == 0) {
    echo "Servicio no activado para esta sucursal.";
    exit;
}
$token_api = $rsdelo->fields['token_api'];
// busca formas de pago
$consulta = "
select app_rider_formaspago.idformapago_app
from  app_rider_formaspago
inner join app_rider on app_rider.id_app_rider  =  app_rider_formaspago.id_app_rider
where 
app_rider_formaspago.idformapago = $idformapago
and app_rider.estado = 1
and app_rider_formaspago.estado = 1
and app_rider.id_app_rider = $id_app_rider
limit 1
";
//echo $consulta;
$rsdelopag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idformapago_app = intval($rsdelopag->fields['idformapago_app']);


// busca si ya se envio y llego correctamente
$consulta = "
select id_app_rider_log, idpedidoexterno, envio_correcto
from app_rider_log
where
idventa = $idventa
and envio_correcto = 'S'
and id_app_rider  = $id_app_rider
order by id_app_rider_log desc
limit 1
";
//echo $consulta;exit;
$rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

//si no se envio este pedido correctamente
if (intval($rsdelolog->fields['id_app_rider_log']) == 0) {


    $url = "https://delorean.studio54.app/gateway/externalOrders";
    $datos_post = [
            'token' => $token_api,
            'id_sucursal' => intval($rsdelo->fields['idsucursal_app']),
            'nombre_cli' => substr(trim($rs->fields['nombres'].$rs->fields['apellidos']), 0, 100),
            'dir_cli' => substr(trim($rs->fields['direccion']), 0, 250),
            'email_cli' => substr('', 0, 100),
            'tel_cli' => substr($rs->fields['telefono'], 0, 50),
            'fact_cli' => substr($rs->fields['razon_social'], 0, 100),
            'ruc_cli' => substr($rs->fields['ruc'], 0, 40),
            'lat_cli' => floatval($rs->fields['latitud']),
            'lon_cli' => floatval($rs->fields['longitud']),
            'deta_pedido' => substr($detalle_ped, 0, 2500),
            'delivery' => intval($rs->fields['monto_delivery']),
            'sub_tot' => intval($rs->fields['totalcobrar']),
            'metodo' => intval($idformapago_app),
    ];
    //print_r($datos_post);exit;
    // para el log
    $datos_post_json = json_encode($datos_post, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $datos_post_json_ins = antisqlinyeccion($datos_post_json, "textbox");
    // inserta en el log
    $consulta = "
	insert into app_rider_log
	(id_app_rider, idventa, idpedidoexterno, enviado_por, enviado_el, json_enviado, json_respuesta, envio_correcto)
	values
	(1, $idventa, NULL, $idusu, '$ahora', $datos_post_json_ins, NULL, 'N')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // obtener id insertado
    $consulta = "
	select max(id_app_rider_log) as id_app_rider_log from app_rider_log where idventa = $idventa
	";
    $rslog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_app_rider_log = intval($rslog->fields['id_app_rider_log']);

    $postdata = http_build_query($datos_post);

    $opts = ['http' =>
        [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postdata
        ]
    ];

    $context = stream_context_create($opts);
    //print_r($context);

    $res = file_get_contents($url, false, $context);
    //{"status":1,"data":{"pd":{"msg":"Pedido cargado exitosamente","id_pedido":"17931"}}}
    $respuesta_json = json_decode($res, true);
    $idpedidoexterno = intval($respuesta_json['data']['pd']['id_pedido']);

    //print_r($respuesta_json);

    if ($idpedidoexterno > 0) {
        $respuesta_json_ins = antisqlinyeccion($res, "textbox");
        $consulta = "
		update app_rider_log
		set
			idpedidoexterno=$idpedidoexterno,
			json_respuesta=$respuesta_json_ins,
			envio_correcto='S'
		where
			id_app_rider_log = $id_app_rider_log
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: delivery_micaja_est.php?id=$idventa");
        exit;

    } else {
        echo "NO ENVIADO! Confirmacion no recibida.";
        exit;
    }


} else {
    echo "Este pedido ya se envio anteriormente, no se puede volver a enviar.";
    exit;
}
