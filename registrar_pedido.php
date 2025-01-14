<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//print_r($_POST);
$idsucu = intval($_POST['idsucu']);
/*if($idsucu > 0){
    $idsucursal=$idsucu;
}*/

$consulta = "
SELECT razon_social, ruc FROM cliente where borrable = 'N' and estado = 1 order by idcliente asc limit 1
";
$rsclipred = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = $rsclipred->fields['razon_social'];
$ruc_pred = $rsclipred->fields['ruc'];

// productos
$consulta = "
select *, 
	(
	select 
	sum(cantidad) as total
	from tmp_ventares 
	where 
	tmp_ventares.registrado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.borrado='N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.idproducto = productos.idprod_serial
	and tmp_ventares.idempresa = $idempresa
	and tmp_ventares.idsucursal = $idsucursal
	) as total
from productos 
where
productos.idprod_serial is not null
and productos.idempresa = $idempresa
and productos.borrado = 'N'
$whereadd
order by productos.combinado desc, productos.descripcion asc
";
//echo $consulta;
$rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



// total productos en carrito
$consulta = "
select count(*) as total
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
and productos.idempresa = $idempresa
and productos.borrado = 'N'
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalprod = intval($rs->fields['total']);

// monto total de productos en carrito
$consulta = "
select sum(subtotal) as total_monto
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
and productos.idempresa = $idempresa
and productos.borrado = 'N'
";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalprod = intval($rs->fields['total_monto']);
// monto total agregados
$consulta = "
SELECT sum(precio_adicional) as montototalagregados, count(idventatmp) as totalagregados
FROM 
tmp_ventares_agregado
where
idventatmp in (
select tmp_ventares.idventatmp
from tmp_ventares 
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idsucursal = $idsucursal
)
";
$rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalag = intval($rsag->fields['montototalagregados']);
$montototalag = 0;

// productos mas agregados
$montototal = $montototalprod + $montototalag;
//echo $montototal;
//exit;

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    // recibe parametros
    $razon_social = trim(strtoupper($_POST['razon_social']));
    $ruc = trim(strtoupper($_POST['ruc']));
    $chapa = trim(strtoupper($_POST['chapa']));
    $observacion = trim(strtoupper($_POST['observacion']));
    $telefono = trim(strtoupper($_POST['telefono']));
    $monto = $montototal;
    $mesa = trim($_POST['mesa']);
    $canal = intval($_POST['canal']);



    // conversiones
    $finalizado = "S";
    if (intval($_POST['canal']) == 3) {
        $delivery = "S";
    } else {
        $delivery = "N";
    }
    if (trim($mesa) == '') {
        $mesa = 0;
    }
    // si no tiene canal asignado asigna el 1
    if (intval($_POST['canal']) == 0) {
        $_POST['canal'] = 1;
        $canal = 1;
    }
    // si el canal no es valido
    if (intval($_POST['canal']) == 2 or intval($_POST['canal']) > 4) {
        $_POST['canal'] = 1;
        $canal = 1;
    }

    // busca costo delivery si corresponde
    if (intval($_POST['canal']) == 3) {
        $zona = antisqlinyeccion($_POST['zona'], "int");
        $consulta = "
		Select idzona,descripcion,costoentrega
		from gest_zonas
		where 
		estado=1 
		and gest_zonas.idzona = $zona
		and gest_zonas.idempresa = $idempresa 
		and gest_zonas.idsucursal = $idsucursal
		order by descripcion asc
		";
        $rsdelcos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $costodelivery = intval($rsdelcos->fields['costoentrega']);
    }


    // validaciones
    $valido = "S";
    if ($totalprod == 0) {
        $errores .= "- Debe agregar al menos 1 producto al carrito.<br />";
        $valido = "N";
    }


    // validar canal calle
    if (intval($_POST['canal']) == 1) {  // si es calle pide chapa
        if ($chapa == '') {
            $errores .= "- Debe indicar el numero de chapa.<br />";
            $valido = "N";
        }
    }
    // validar canal delivery
    if (intval($_POST['canal']) == 3) {
        $delivery = "S";
        /*if(intval($_POST['telefono']) == 0){
            $valido="N";
            $errores.="- Debe completar el telefono.<br />";
        }
        if(intval($_POST['zona']) == 0){
            $valido="N";
            $errores.="- Debe indicar la zona del delivery.<br />";
        }
        if(trim($_POST['direccion']) == ''){
            $valido="N";
            $errores.="- Debe completar la direccion de entrega.<br />";
        }
        if(trim($_POST['llevapos']) == ''){
            $valido="N";
            $errores.="- Debe indicar si lleva el POS o no.<br />";
        }
        if(trim($_POST['llevapos']) == 'N'){
            if(intval($_POST['cambio']) == 0){
                $valido="N";
                $errores.="- Si el pago es en efectivo debe indicar el cambio.<br />";
            }
        }*/
        $iddomicilio = intval($_COOKIE['dom_deliv']);
        if ($iddomicilio == 0) {
            $valido = "N";
            $errores .= "- No se tomaron los datos del delivery.<br />";
        }
    }
    // validar campos compartidos chapa y delivery
    if (intval($_POST['canal']) == 1 or intval($_POST['canal']) == 3) {
        if ($razon_social == '') {
            $errores .= "- Debe indicar la razon social.<br />";
            $valido = "N";
        }
        if ($ruc == '') {
            $errores .= "- Debe indicar el ruc.<br />";
            $valido = "N";
        }
        $ruc_ar = explode("-", $ruc);
        $ruc_pri = intval($ruc_ar[0]);
        $ruc_dv = intval($ruc_ar[1]);
        /*if($ruc_pri <= 0){
            $errores.="- El ruc no puede ser cero o menor.<br />";
            $valido="N";
        }
        if(strlen($ruc_dv) <> 1){
            $errores.="- El digito verificador del ruc no puede tener 2 numeros.<br />";
            $valido="N";
        }*/
        if (calcular_ruc($ruc_pri) <> $ruc_dv) {
            $digitocor = calcular_ruc($ruc_pri);
            $errores .= "- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.<br />";
            $valido = "N";
        }
        if ($ruc == $ruc_pred && $razon_social <> $razon_social_pred) {
            $errores .= "- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.<br />";
            $valido = "N";
        }
        if ($ruc <> $ruc_pred && $razon_social == $razon_social_pred) {
            $errores .= "- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.<br />";
            $valido = "N";
        }
    }
    // valida canal mesa
    if (intval($_POST['canal']) == 4) {
        if (intval($_POST['mesa']) == 0) {
            $valido = "N";
            $errores .= "- Debe indicar el numero de mesa.<br />";
        }
    }




    // limpia variables para insertar
    $razon_social = antisqlinyeccion($razon_social, "text");
    $ruc = antisqlinyeccion($ruc, "text");
    $chapa = antisqlinyeccion($chapa, "text");
    $observacion = antisqlinyeccion($observacion, "text");
    $monto = antisqlinyeccion($monto, "int");
    $fechahora = date("Y-m-d H:i:s");
    $fechahora = antisqlinyeccion($fechahora, "text");
    $delivery = antisqlinyeccion($delivery, "text");
    $finalizado = antisqlinyeccion($finalizado, "text");
    $mesa = antisqlinyeccion($mesa, "int");
    $telefono = antisqlinyeccion($telefono, "int");

    // si es delivery
    if (intval($_POST['canal']) == 3) {
        // datos delivery
        $consulta = "
		select *
		from cliente_delivery
		inner join cliente_delivery_dom on cliente_delivery.idclientedel = cliente_delivery_dom.idclientedel
		where
		iddomicilio = $iddomicilio
		";
        $rsdel = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $telefono = antisqlinyeccion($rsdel->fields['telefono'], "int");
        $zona = antisqlinyeccion(1, "int");
        $direccion = antisqlinyeccion($rsdel->fields['direccion'], "text");

        $delivery_costo = antisqlinyeccion(0, "int");
        $nombre_deliv = antisqlinyeccion($rsdel->fields['nombres'], "text");
        $apellido_deliv = antisqlinyeccion($rsdel->fields['apellidos'], "text");
        $idclientedel = $rsdel->fields['idclientedel'];

        $llevapos = antisqlinyeccion($_POST['llevapos'], "text");
        $cambio = antisqlinyeccion($_POST['cambio'], "int");
        $observacion_delivery = antisqlinyeccion($_POST['observacion_delivery'], "text");


    } else {

        $telefono = antisqlinyeccion($_POST['telefono'], "int");
        $zona = antisqlinyeccion($_POST['zona'], "int");
        $direccion = antisqlinyeccion($_POST['direccion'], "text");
        $llevapos = antisqlinyeccion($_POST['llevapos'], "text");
        $cambio = antisqlinyeccion($_POST['cambio'], "int");
        $observacion_delivery = antisqlinyeccion($_POST['observacion_delivery'], "text");
        $delivery_costo = antisqlinyeccion(0, "int");
        $nombre_deliv = antisqlinyeccion('', "text");
        $apellido_deliv = antisqlinyeccion('', "text");
        $idclientedel = antisqlinyeccion('', "text");
        $iddomicilio = antisqlinyeccion('', "text");

    }

    if ($valido == 'S') {
        // si envio ruc
        if (trim($_POST['ruc']) != '') {
            // busca en clientes facturacion
            $consulta = "
			select * from cliente where ruc = $ruc and estado <> 6 order by idcliente asc limit 1
			";
            $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcliente = intval($rscli->fields['idcliente']);
            // si no existe inserta
            if ($idcliente == 0) {
                $consulta = "
				Insert into cliente 
				(idempresa,nombre,apellido,ruc,documento,direccion,celular,razon_social)
				values
				(1,NULL,NULL,$ruc,NULL,$direccion,$telefono,$razon_social)
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                // busca en clientes el que acabamos de insertar
                $consulta = "
				select * from cliente where ruc = $ruc and estado <> 6 order by idcliente asc limit 1
				";
                $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idcliente = intval($rscli->fields['idcliente']);

            }
        }

        // insertar cabecera temporal
        $consulta = "
		INSERT INTO tmp_ventares_cab
		(razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, idempresa,idcanal,delivery,idmesa,
		telefono,delivery_zona,direccion,llevapos,cambio,observacion_delivery,delivery_costo, 
		nombre_deliv, apellido_deliv, idclientedel, iddomicilio, idterminal) 
		VALUES 
		($razon_social, $ruc, $chapa, $observacion, $monto, $idusu, $fechahora, $idsucursal, $idempresa,$canal,$delivery,$mesa,
		$telefono,$zona,$direccion,$llevapos,$cambio,$observacion_delivery,$delivery_costo,
		$nombre_deliv, $apellido_deliv, $idclientedel, $iddomicilio, $idterminal_usu)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // buscar ultimo id insertado
        $consulta = "
		select idtmpventares_cab
		from tmp_ventares_cab
		where
		idusu = $idusu
		and idsucursal = $idsucursal
		and idempresa = $idempresa
		order by idtmpventares_cab desc
		limit 1
		";
        $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtmpventares_cab = $rscab->fields['idtmpventares_cab'];


        // cambiar de sucursal antes de marcar cabeceras para evitar impresiones el local equivocado
        if ($idsucu > 0 && $idsucu <> $idsucursal) {
            // si es otra sucursal diferente
            if ($idsucu <> $idsucursal) {
                // si quedaron restos en el carrito en la otra sucursal borra
                $consulta = "
				update tmp_ventares
				set
				borrado = 'S'
				where
				usuario = $idusu
				and registrado = 'N'
				and borrado = 'N'
				and finalizado = 'N'
				and idsucursal = $idsucu
				and idempresa = $idempresa
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

            // combo cambia de sucursal
            $consulta = "
			update tmp_combos_listas
			set 
			idsucursal=$idsucu
			where
			idusuario = $idusu
			and idventatmp in (
				select idventatmp
				from tmp_ventares
				where
				usuario = $idusu
				and registrado = 'N'
				and borrado = 'N'
				and finalizado = 'N'
				and idsucursal = $idsucursal
				and idempresa = $idempresa
			)
			;
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            // combinado cambia de sucursal
            $consulta = "
			update tmp_combinado_listas
			set 
			idsucursal=$idsucu
			where
			idusuario = $idusu
			and idventatmp in (
				select idventatmp
				from tmp_ventares
				where
				usuario = $idusu
				and registrado = 'N'
				and borrado = 'N'
				and finalizado = 'N'
				and idsucursal = $idsucursal
				and idempresa = $idempresa
			)
			;
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // detalle cambia de sucursal
            $consulta = "
			update tmp_ventares
			set 
			idsucursal=$idsucu
			where
			usuario = $idusu
			and registrado = 'N'
			and borrado = 'N'
			and finalizado = 'N'
			and idsucursal = $idsucursal
			and idempresa = $idempresa
			;
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            //cabecera cambia de sucursal
            $consulta = "
			update tmp_ventares_cab
			set 
			idsucursal=$idsucu
			where
			idtmpventares_cab = $idtmpventares_cab
			and idsucursal = $idsucursal
			and idempresa = $idempresa
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            // asigna nueva sucursal para marcar
            $idsucursal = $idsucu;
        }


        // marcar como finalizado
        $consulta = "
		update tmp_ventares
		set 
		finalizado = $finalizado,
		idtmpventares_cab = $idtmpventares_cab
		where
		usuario = $idusu
		and registrado = 'N'
		and borrado = 'N'
		and finalizado = 'N'
		and idsucursal = $idsucursal
		and idempresa = $idempresa
		;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // marcar como finalizado en cabecera
        $consulta = "
		update tmp_ventares_cab
		set 
		finalizado = $finalizado
		where
		idtmpventares_cab = $idtmpventares_cab
		and idsucursal = $idsucursal
		and idempresa = $idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //echo "OK";
        //exit;
        // respuesta json correcto
        $arr = [
        'registro' => 'OK',
        'error' => ''
        ];

        $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        echo $respuesta;
        exit;

    } else {
        //echo $errores;
        //exit;
        // respuesta json error
        $errores = str_replace('<br />', $saltolinea, $errores);
        $arr = [
        'error' => $errores
        ];

        $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        echo $respuesta;
        exit;
    }

}
