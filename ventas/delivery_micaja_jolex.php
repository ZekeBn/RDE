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

function null_json($text)
{
    if (trim($text) == '') {
        //$res='null';
        $res = '';
    } else {
        $res = $text;
    }
    return $res;
}

$idventa = intval($_GET['idventa']);
$id_app_rider = 2; // Jolex
$id_app_rider_motorista = intval($_GET['id_app_rider_motorista']);
if ($id_app_rider_motorista == 0) {
    echo "No se indico el motorista de Jolex.";
    exit;
}


function enviar_jolex($url, $datos_post_json, $token_api)
{

    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $datos_post_json,
      CURLOPT_HTTPHEADER => [
        $token_api,
        'Content-Type: application/json'
      ],
    ]);

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

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
(select referencia from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as referencia,
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
/*$consulta="
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
$rsdet=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
while(!$rsdet->EOF){
    $detalle_ped.=floatval($rsdet->fields['cantidad']).' x '.trim($rsdet->fields['producto']).", ";
$rsdet->MoveNext(); }
$detalle_ped=trim($detalle_ped);
$detalle_ped=substr($detalle_ped,0,-1);
*/
// delorean
$consulta = "
select app_rider.id_app_rider, app_rider.token_api, app_rider_sucursales.idsucursal_app, app_rider.idmotorista
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
$idmotorista = intval($rsdelo->fields['idmotorista']);
if (intval($rsdelo->fields['id_app_rider']) == 0) {
    echo "Servicio no activado para esta sucursal.";
    exit;
}
if (intval($rsdelo->fields['idmotorista']) == 0) {
    echo "No se asigno el motorista para esta app.";
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

// busca motorista
$consulta = "
select app_rider_motorista.id_app_rider_motorista, app_rider_motorista.idmotorista_app
from  app_rider_motorista
where 
app_rider_motorista.id_app_rider = 2
and app_rider_motorista.id_app_rider_motorista = $id_app_rider_motorista
limit 1
";
//echo $consulta;
$rsdelomot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmotorista_app = $rsdelomot->fields['idmotorista_app'];


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
    /*
    "clientId": null,
    "clientPhoneId": null,
    "clientAddressId": null,
    "branchId": 2111,
    "driverId": null,
    "observations": "observaciónopcional",
    "paymentMethodId": 12345,
    "total": 2000.00,
    "externalTypeId": 1,
    "allowDuplicateExternalId": false,
    "initialNotification": false,
    */

    $url = "http://jolex.tech-precision.com/api/external/createOrder";

    $client_array = [
        'name' => substr(trim($rs->fields['nombres']), 0, 100),
        'lastName' => substr(trim($rs->fields['apellidos']), 0, 100),
        'documentNumber' => null,
        'ExternalId' => intval($rs->fields['idclientedel']),
    ];
    $clientAddress_array = [
        'address1' => substr(trim($rs->fields['direccion']), 0, 500),
        'address2' => 'x',
        'address3' => 'x',
        'number' => 0,
        'city' => 'x',
        'neighborhood' => 'x',
        'latitude' => null_json($rs->fields['latitud']),
        'longitude' => null_json($rs->fields['longitud']),
        'observations' => $rs->fields['referencia'],
        'intersectionTypeId' => 10, // Casi: 10 Esq: 20 Entre: 30
        'ExternalId' => intval($rs->fields['iddomicilio']),
    ];
    $clientPhone_array = [
        'number' => '0'.substr(trim($rs->fields['telefono']), 0, 100),
    ];


    $datos_post = [
            'externalId' => $idventa,
            'branchId' => intval($rsdelo->fields['idsucursal_app']),
            'clientId' => null,
            'clientPhoneId' => null,
            'clientAddressId' => null,
            'driverId' => $idmotorista_app,
            'observations' => '',
            'paymentMethodId' => 12345,
            'total' => floatval($rs->fields['totalcobrar']),
            'externalTypeId' => '1',
            'allowDuplicateExternalId' => false,
            'initialNotification' => false,
            'client' => $client_array,
            'clientAddress' => $clientAddress_array,
            'clientPhone' => $clientPhone_array,
    ];
    //print_r($datos_post);exit;
    // para el log
    $datos_post_json = json_encode($datos_post, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $datos_post_json_ins = antisqlinyeccion($datos_post_json, "textbox");

    //print_r($datos_post_json);exit;
    // inserta en el log
    $consulta = "
	insert into app_rider_log
	(id_app_rider, idventa, idpedidoexterno, enviado_por, enviado_el, json_enviado, json_respuesta, envio_correcto)
	values
	($id_app_rider, $idventa, NULL, $idusu, '$ahora', $datos_post_json_ins, NULL, 'N')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // obtener id insertado
    $consulta = "
	select max(id_app_rider_log) as id_app_rider_log from app_rider_log where idventa = $idventa
	";
    $rslog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_app_rider_log = intval($rslog->fields['id_app_rider_log']);


    $res = enviar_jolex($url, $datos_post_json, $token_api);
    //echo $res;exit;
    $respuesta = json_decode($res, true);

    $idpedidoexterno = intval($respuesta['Id']);



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
        // cambiar estado a entregado al delivery
        $consulta = "
		update tmp_ventares_cab
		set
			idestadodelivery=2
		where
			idventa = $idventa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // actualizar motorista
        $consulta = "
		update ventas 
		set
		idmotorista = $idmotorista
		where 
		idventa = $idventa
		and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualizar motorista externo si aplica
        // busca si ya existe esa venta en datos extra
        $consulta = "
		select idventa, idventaextra  from ventas_datosextra where idventa = $idventa order by idventaextra asc limit 1
		";
        $rsvenextra = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idventaextra = intval($rsvenextra->fields['idventaextra']);
        // si no existe inserta
        if ($idventaextra == 0) {
            $consulta = "
			insert into ventas_datosextra
			(idventa,id_app_rider,id_app_rider_motorista,idpedidoexterno_rider )
			values
			($idventa, $id_app_rider, $id_app_rider_motorista,$idpedidoexterno)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si existe actualiza
        } else {
            $consulta = "
			update ventas_datosextra
			set
			id_app_rider = $id_app_rider,
			id_app_rider_motorista = $id_app_rider_motorista,
			idpedidoexterno_rider = $idpedidoexterno
			where
			idventaextra = $idventaextra
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        header("location: delivery_micaja_est.php?id=$idventa&ok=s");
        exit;

    } else {
        echo "NO ENVIADO! Confirmacion no recibida.<hr /> Error jolex: ".htmlentities($respuesta['Error'])."<hr />
		<a href='delivery_micaja_est.php?id=$idventa'>[Volver]</a>";
        exit;
    }


} else {
    echo "Este pedido ya se envio anteriormente, no se puede volver a enviar.";
    exit;
}
