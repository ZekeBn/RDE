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
$id_app_rider = 4; // pediboss


$consulta = "
select * from app_rider where id_app_rider = $id_app_rider
";
$rsapp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$dominio_pediboss = $rsapp->fields['dominio'];



//$dominio_pediboss='https://test-server.pediboss.com.py';

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
(select llevapos from tmp_ventares_cab where tmp_ventares_cab.idventa = ventas.idventa) as llevapos,
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
) as idformapago,
(select count(*) from ventas_detalles where idventa = ventas.idventa) as cantidad_productos
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
//Cantidad de ventas
$cantidad_productos = intval($rs->fields['cantidad_productos']);
$llevapos = trim($rs->fields['llevapos']);
if ($llevapos == 'S') {
    $observacion_pediboss = "** LLEVAR POS **";
} else {
    $observacion_pediboss = "N/A";
}

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

// pediboss
$consulta = "
select app_rider.id_app_rider, app_rider.token_api, app_rider.rand_secuencial, app_rider.dominio, app_rider_sucursales.idsucursal_app, app_rider.idmotorista, app_rider_zonas.idzona_app, app_rider.idprod_fijo_app
from  app_rider_sucursales
inner join app_rider on app_rider.id_app_rider  =  app_rider_sucursales.id_app_rider
left join app_rider_zonas on app_rider_zonas.id_app_rider = app_rider_sucursales.id_app_rider
where 
app_rider_sucursales.idsucursal = $idsucursal
and app_rider.estado = 1
and app_rider_sucursales.estado = 1
and app_rider.id_app_rider = $id_app_rider
limit 1
";
//echo $consulta;exit;
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
if (intval($rsdelo->fields['idzona_app']) == 0) {
    echo "No se asigno la zona de pediboss para la zona de delivery de la direccion de este pedido.";
    exit;
}
if (intval($rsdelo->fields['idsucursal_app']) == 0) {
    echo "No se asigno la sucursal de pediboss para la sucursal de este pedido.";
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

    $curl_step_1 = curl_init();
    $curl_step_2 = curl_init();

    $correo_usuario = trim($rsdelo->fields['rand_secuencial']).'randmail@'.trim($rsdelo->fields['rand_secuencial']).'randmail.com';
    $password_usuario = 'Rand_'.trim($rsdelo->fields['rand_secuencial']).'_EkaruWS$$##22';


    $datos_envio_pri = '{
		  "api_key"    : "'.trim($rsdelo->fields['token_api']).'",
		  "first_name" : "'.trim($rs->fields['nombres']).'" ,
		  "last_name"  : "'.trim($rs->fields['apellidos']).'" ,
		  "email"      : "'.$correo_usuario.'" ,
		  "phone_no"   : "'.trim($rs->fields['telefono']).'" ,
		  "password"   : "'.$password_usuario.'"
	  }';

    $datos_post_json_ins_pri = antisqlinyeccion($datos_envio_pri, "textbox");
    //echo $datos_post_json_ins;exit;
    // inserta en el log
    $consulta = "
	insert into app_rider_log_otros
	(id_app_rider, idventa, idpedidoexterno, enviado_por, enviado_el, json_enviado, json_respuesta, envio_correcto)
	values
	($id_app_rider, $idventa, NULL, $idusu, '$ahora', $datos_post_json_ins_pri, NULL, 'N')
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // obtener id insertado
    $consulta = "
	select max(id_app_rider_log) as id_app_rider_log from app_rider_log_otros where idventa = $idventa
	";
    $rslogpri = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $id_app_rider_log_pri = intval($rslogpri->fields['id_app_rider_log']);

    //Paso 1: Creo un nuevo cliente a partir de un numero de celular, email random y password random

    curl_setopt_array($curl_step_1, [
        CURLOPT_URL => $dominio_pediboss.'/open/admin/customer/add',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $datos_envio_pri,
        CURLOPT_HTTPHEADER => [
          'Content-Type: application/json'
        ],
      ]);

    $response = curl_exec($curl_step_1);
    $respuesta_json = json_decode($response, true);

    if ($respuesta_json['data']['status'] == 201) { //Pediboss indica que el usuario ya existe mediante el numero de telefono

        $usuario_id_pediboss = $respuesta_json['data']['customer_id'];

    } elseif ($respuesta_json['status'] == 201) {

        $usuario_id_pediboss = $respuesta_json['data']['vendor_id'];

    } elseif ($respuesta_json['status'] == 200) {

        $usuario_id_pediboss = $respuesta_json['data']['vendor_details']['vendor_id'];

    } else {//Nuevo usuario y devuelve su ID

        //print_r($respuesta_json['data']['vendor_id']);
        $usuario_id_pediboss = $respuesta_json['data']['vendor_id'];

        //Si se creo un nuevo usuario, se actualiza la secuencia
        $consulta = "update app_rider set rand_secuencial = rand_secuencial+1 where id_app_rider = 4";
        //echo $consulta;exit;
        $rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }
    if (intval($usuario_id_pediboss) == 0) {
        $usuario_id_pediboss = $respuesta_json['data']['vendor_details']['vendor_id'];
    }

    $usuario_id_pediboss = intval($usuario_id_pediboss);

    curl_close($curl_step_1);

    // guarda en el log
    $respuesta_json_ins_pri = antisqlinyeccion($response, "textbox");
    $consulta = "
	update app_rider_log_otros
	set
		json_respuesta=$respuesta_json_ins_pri
	where
		id_app_rider_log = $id_app_rider_log_pri
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    //Paso 2: Genero la orden con el ID del cliente
    //Obs.: Para desarrollo, el product_id de item ticket es 4474 y para produccion es (63412 para Bellini)

    $datos_envio = '{
		"api_key":"'.trim($rsdelo->fields['token_api']).'",
		"payment_method":"'.intval($idformapago_app).'",
		"customer_address":"'.substr(trim($rs->fields['direccion']), 0, 250).'",
		"customer_username":"'.substr(trim($rs->fields['nombres'].$rs->fields['apellidos']), 0, 100).'",
		"customer_email":"'.$correo_usuario.'",
		"customer_phone":"'.substr($rs->fields['telefono'], 0, 50).'",
		"job_date_time":"'.date('Y-m-d H:i:s').'",
		"job_latitude":"'.floatval($rs->fields['latitud']).'",
		"job_longitude":"'.floatval($rs->fields['longitud']).'",
		"job_description":"'.trim($observacion_pediboss).'",
		"products":[
			{
				"product_id": '.$rsdelo->fields['idprod_fijo_app'].',
				"quantity":'.(intval($rs->fields['totalcobrar']) - intval($rs->fields['monto_delivery'])).',
				"unit_price":1,
				"total_price":'.(intval($rs->fields['totalcobrar']) - intval($rs->fields['monto_delivery'])).'
			},
			{
				"product_id":'.intval($rsdelo->fields['idzona_app']).',
				"quantity":1,
				"unit_price":1,
				"total_price":'.intval($rs->fields['monto_delivery']).'
			}
		],
		"vendor_id":'.$usuario_id_pediboss.', 
		"is_scheduled":0,
		"amount":'.(intval($rs->fields['totalcobrar']) - intval($rs->fields['monto_delivery'])).',
		"delivery_charge":0,
		"currency_id":'.intval($idformapago_app).',
		"store_id":'.intval($rsdelo->fields['idsucursal_app']).',
		"self_pickup":0
		}';
    //echo $datos_envio;exit;
    // para el log
    //$datos_post_json=json_encode($datos_envio, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $datos_post_json_ins = antisqlinyeccion($datos_envio, "textbox");
    //echo $datos_post_json_ins;exit;
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

    //$url_pediboss=trim($rsdelo->fields['dominio']).'/open/admin/order/create';
    $url_pediboss = $dominio_pediboss.'/open/admin/order/create';

    curl_setopt_array($curl_step_2, [
    CURLOPT_URL => $url_pediboss,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $datos_envio,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    ]);

    $respuesta = curl_exec($curl_step_2);

    curl_close($curl_step_2);

    //echo $respuesta;exit;

    // guarda en el log
    $respuesta_json_ins = antisqlinyeccion($respuesta, "textbox");
    $consulta = "
	update app_rider_log
	set
		json_respuesta=$respuesta_json_ins
	where
		id_app_rider_log = $id_app_rider_log
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    //{"status":1,"data":{"pd":{"msg":"Pedido cargado exitosamente","id_pedido":"17931"}}}
    $respuesta_json = json_decode($respuesta, true);
    $idpedidoexterno = intval($respuesta_json['data']['job_id']);

    // si la respuesta fue correcta guarda
    if ($idpedidoexterno > 0) {

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

        header("location: delivery_micaja_est.php?id=$idventa");
        exit;

    } else {
        echo "NO ENVIADO! Confirmacion no recibida.<br />";
        echo "Respuesta: ".antixss($respuesta_json_ins);
        exit;
    }


} else {
    echo "Este pedido ya se envio anteriormente, no se puede volver a enviar.";
    exit;
}
