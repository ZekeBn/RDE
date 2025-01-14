<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "21";
$submodulo = "156";
require_once("includes/rsusuario.php");


// producto por defecto
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$producto = intval($rspref->fields['alumnolista_idprod']);
$ventas_nostock = trim($rspref->fields['ventas_nostock']);
$idproducto = $producto;
if ($idproducto == 0) {
    echo "No se definio el producto a registrar.";
    exit;
}

// retiro directo
$retdirecto = "S";
$valido = "S";
$tipoventa = 2;
$idservcompred = 2;
$idadherente = intval($_POST['idadherente']);
$accion = trim($_POST['accion']);
if ($idadherente == 0) {
    echo "No indicaste el adherente!";
    exit;
}
// valida que exista el adherente
$consulta = "SELECT * FROM adherentes where idempresa = $idempresa and idadherente = $idadherente";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idadherente = intval($rs->fields['idadherente']);
$idcliente = intval($rs->fields['idcliente']);
if ($idadherente == 0) {
    echo "Adherente no existe!";
    exit;
}
//////////// validaciones especiales si es retiro directo ////////////

if ($retdirecto == 'S') {
    // busca el producto
    $consulta = "
		select p1 as precio,idmedida, idtipoproducto
		from productos 
		where
		idprod_serial is not null
		and productos.idempresa = $idempresa
		and idprod_serial = $producto
		order by productos.descripcion asc
		";

    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $precio = antisqlinyeccion($rs->fields['precio'], "float");
    $totalcobrar = $rs->fields['precio'];
    // datos del servicio de comida
    $buscar = "
		Select 
			nomape,
			adherentes.linea_sobregiro,
			adherentes.maximo_mensual,
			adherentes.nombres,
			adherentes.apellidos,
			adherentes.telefono,
			cliente.nombre as nomclie,
			adherentes.idadherente,
			cliente.apellido as apeclie,
			cliente.ruc,
			cliente.documento,
			disponibleserv,
			nombre_servicio,
			adherentes_servicioscom.idserviciocom,
			adherentes_servicioscom.linea_credito,
			adherentes_servicioscom.max_mensual
		from adherentes
		inner join cliente on cliente.idcliente=adherentes.idcliente
		inner join adherentes_servicioscom on adherentes_servicioscom.idadherente=adherentes.idadherente
		inner join servicio_comida on servicio_comida.idserviciocom=adherentes_servicioscom.idserviciocom
		where
			adherentes.idempresa=$idempresa 
			and cliente.idempresa=$idempresa 
			and disponibleserv > 0
			and adherentes.idadherente = $idadherente
			and adherentes_servicioscom.idserviciocom in (
					select producto_serviciocom.idserviciocom
					from productos
					inner join producto_serviciocom on producto_serviciocom.idproducto = productos.idprod_serial
					where 
					producto_serviciocom.idempresa = $idempresa
					and producto_serviciocom.idproducto = $idproducto
					group by idserviciocom
				  )
		order by disponibleserv desc, nomape asc
		limit 1
		";
    $rsop = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tdata = $rsop->RecordCount();
    $idservcom = intval($rsop->fields['idserviciocom']);
    $nombre_servicio = trim($rsop->fields['nombre_servicio']);
    if ($idservcom == 0) {
        $idservcom = $idservcompred;
    }

    // datos del cliente
    if ($idadherente == 0) {
        $consulta = "select * from cliente where idcliente = $idcliente and idempresa = $idempresa";
        $rscliente = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rscliente->fields['idcliente']);
        $ruc = $rscliente->fields['ruc'];
        $razon_social = $rscliente->fields['razon_social'];
        $rucx = explode("-", $ruc);
        $ruc_pri = $rucx[0];
        $ruc_dv = intval($rucx[1]);
    } else {

        $buscar = "Select * from adherentes where idadherente=$idadherente and idempresa=$idempresa";
        $rsad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idcliente = intval($rsad->fields['idcliente']);
        $consulta = "select * from cliente where idcliente = $idcliente and idempresa = $idempresa";
        $rscliente = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rscliente->fields['idcliente']);
        $ruc = $rscliente->fields['ruc'];
        $razon_social = $rscliente->fields['razon_social'];
        $rucx = explode("-", $ruc);
        $ruc_pri = $rucx[0];
        $ruc_dv = intval($rucx[1]);
    }
    // validar venta sin stock si aplica
    if ($ventas_nostock == 2) {


    }

    // si la venta es a credito
    if ($tipoventa == 2) {
        // si tiene permitido operar a credito
        if ($rscliente->fields['permite_acredito'] != 'S') {
            $valido = 'N';
            $errores .= '- El cliente seleccionado no tiene permitido operar a credito.<br />';
        }
        // periodo actual
        $mesactu = date("m");
        $anoactu = date("Y");
        // busca el consumo del mes actual del cliente (solo ventas credito)
        $buscar = "
		Select sum(totalcobrar) as consumo_mes
		from ventas 
		where 
		idcliente=$idcliente 
		and idempresa=$idempresa 
		and MONTH(fecha) = $mesactu
		and YEAR(fecha) = $anoactu
		and tipo_venta = 2
		";
        $rsvconsumo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        // variables necesarias para el calculo
        $maxmensual_tit = intval($rscliente->fields['max_mensual']);
        $lineasobregiro_tit = intval($rscliente->fields['linea_sobregiro']);
        $saldosobregiro_tit = intval($rscliente->fields['saldo_sobregiro']);
        $saldomensual_tit = $maxmensual_tit - intval($rsvconsumo->fields['consumo_mes']);
        $saldosobregiro_posconsumo_tit = intval($saldosobregiro_tit) - intval($totalcobrar);
        // si el consumo a registrar supera el saldo disponible de la linea de credito
        if ($totalcobrar > $saldosobregiro_tit) {
            $valido = 'N';
            $errores .= '- La venta que intentas realizar supera la linea disponible del cliente.<br />';
        }
        // si el consumo a registrar supera el saldo de maximo mensual
        if ($totalcobrar > $saldomensual_tit) {
            $valido = 'N';
            $errores .= '- La venta que intentas realizar supera el maximo mensual permitido para el cliente.<br />';
        }



        // valida linea adherente
        if ($idadherente > 0) {
            // busca el consumo del mes actual del adherente del cliente (solo ventas credito)
            $buscar = "
			Select sum(totalcobrar) as consumo_mes
			from ventas 
			where 
			idadherente=$idadherente 
			and idempresa=$idempresa 
			and MONTH(fecha) = $mesactu
			and YEAR(fecha) = $anoactu
			and tipo_venta = 2
			";
            $rsvconsumo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            // variables necesarias para el calculo
            $maxmensual_adh = intval($rsad->fields['maximo_mensual']);
            $lineasobregiro_adh = intval($rsad->fields['linea_sobregiro']);
            $saldosobregiro_adh = intval($rsad->fields['disponible']);
            $saldomensual_adh = $maxmensual_adh - intval($rsvconsumo->fields['consumo_mes']);
            $saldosobregiro_posconsumo_adh = intval($saldosobregiro_adh) - intval($totalcobrar);
            // si el consumo a registrar supera el saldo disponible de la linea de credito
            if ($totalcobrar > $saldosobregiro_adh) {
                $valido = 'N';
                $errores .= '- La venta que intentas realizar supera la linea disponible del adherente.<br />';
            }
            // si el consumo a registrar supera el saldo de maximo mensual
            if ($totalcobrar > $saldomensual_adh) {
                $valido = 'N';
                $errores .= '- La venta que intentas realizar supera el maximo mensual permitido para el adherente.<br />';
            }

            // si tiene pedido
            if ($pedido > 0) {
                $whereaddproh = " and idtmpventares_cab = $pedido ";
            } else {
                $whereaddproh = " and idtmpventares_cab is null ";
            }
            // Busca si el carrito contiene alimentos prohibidos
            $consulta = "
			select idprod_serial as idproducto, descripcion  as producto
			from productos 
			where 
			borrado = 'N' 
			and idprod_serial = $idproducto
			and idprod_serial in (
								select idproducto 
								from adherentes_prohibidos
								where
								idadherente = $idadherente
								and estado <> 6
								and idempresa = $idempresa
							  )
			limit 10
			";
            $rsproh = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si encontro productos prohibidos
            if (intval($rsproh->fields['idproducto']) > 0) {
                //$errores="si:".$consulta;
                $valido = 'N';
                $errores .= '- La venta que intentas realizar contiene productos prohibidos. ';
                $errores .= "Prohibidos: ";
                $prodprohibidos = "";
                while (!$rsproh->EOF) {
                    $prodprohibidos .= strtolower($rsproh->fields['producto']).", ";
                    $rsproh->MoveNext();
                }
                $errores .= substr(trim($prodprohibidos), 0, -1);
                $errores .= ".<br />";
            }



            // si usa servicio de comida
            if ($idservcom > 0) {


                /* ALIMENTOS NO ASIGNADOS AL SERVICIO DE COMIDA */
                // si tiene pedido
                if ($pedido > 0) {
                    $whereaddproh = " and idtmpventares_cab = $pedido ";
                } else {
                    $whereaddproh = " and idtmpventares_cab is null ";
                }
                // Busca si el carrito contiene alimentos no asignados a ese servicio
                $consulta = "
				select idprod_serial as idproducto, descripcion as producto
				from productos 
				where 
				idprod_serial = $idproducto
				and idprod_serial not in (
									select idproducto 
									from producto_serviciocom
									where
									idserviciocom = $idservcom
									and idempresa = $idempresa
								  )
				limit 10
				";
                $rsprohser = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // si encontro productos no asignados
                if (intval($rsprohser->fields['idproducto']) > 0) {
                    //$errores="si:".$consulta;
                    $valido = 'N';
                    $errores .= '- La venta que intentas realizar contiene productos no asignados al servicio '.$nombre_servicio.'. ';
                    $errores .= "Productos: ";
                    $prodprohibidos = "";
                    while (!$rsprohser->EOF) {
                        $prodprohibidos .= strtolower($rsprohser->fields['producto']).", ";
                        $rsprohser->MoveNext();
                    }
                    $errores .= substr(trim($prodprohibidos), 0, -1);
                    $errores .= ".<br />";
                }

                // lineas maximas por servicio
                $buscar = "
				Select * from adherentes 
				inner join adherentes_servicioscom on adherentes_servicioscom.idadherente = adherentes_servicioscom.idadherente
				where 
				adherentes.idadherente=$idadherente 
				and adherentes.idempresa=$idempresa
				and adherentes_servicioscom.idserviciocom = $idservcom
				and adherentes_servicioscom.idadherente = $idadherente 
				";
                $rsadcom = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                // busca el consumo del mes actual del servicio de comida del adherente del cliente (solo ventas credito)
                $buscar = "
				Select sum(totalcobrar) as consumo_mes
				from ventas 
				where 
				idadherente=$idadherente 
				and idserviciocom = $idservcom
				and idempresa=$idempresa 
				and MONTH(fecha) = $mesactu
				and YEAR(fecha) = $anoactu
				and tipo_venta = 2
				";
                $rsvconsumoscom = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                // variables necesarias para el calculo
                $maxmensual_adhscom = intval($rsadcom->fields['maximo_mensual']);
                $lineasobregiro_adhscom = intval($rsadcom->fields['linea_sobregiro']);
                $saldosobregiro_adhscom = intval($rsadcom->fields['disponibleserv']);
                $saldomensual_adhscom = $maxmensual_adhscom - intval($rsvconsumoscom->fields['consumo_mes']);
                $saldosobregiro_posconsumo_adhscom = intval($saldosobregiro_adhscom) - intval($totalcobrar);
                // si el consumo a registrar supera el saldo disponible de la linea de credito
                if ($totalcobrar > $saldosobregiro_adhscom) {
                    $valido = 'N';
                    $errores .= '- La venta que intentas realizar supera la linea disponible del adherente para este servicio.<br />';
                }
                // si el consumo a registrar supera el saldo de maximo mensual
                if ($totalcobrar > $saldomensual_adhscom) {
                    $valido = 'N';
                    $errores .= '- La venta que intentas realizar supera el maximo mensual permitido para el adherente para este servicio.<br />';
                }

            }


        }

    }


}
// si hay algun error devuelve en formato json
if ($valido != 'S') {
    $errores = str_replace('<br />', $saltolinea, $errores);
    // respuesta json
    $arr = [
    'error' => $errores
    ];
    // convierte a formato json
    $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    // devuelve la respuesta formateada
    echo $respuesta;
    exit;
}
//////////// validaciones especiales si es retiro directo ////////////





$ahorad = date("Y-m-d");
// busca si existe en la tabla de retiros de la fecha
$consulta = "
	SELECT * 
	FROM adherente_retira
	where
	idempresa = $idempresa
	and idadherente = $idadherente
	and fecha = '$ahorad'
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idadherenteex = intval($rs->fields['idadherente']);
$idretiraadh = intval($rs->fields['idretiraadh']);
$idventa = intval($rs->fields['idventa']);
// verifica si ya se retiro
if ($estado == 'A') {
    echo "- La venta ya se registro por otro usuario, debe anularla en en modulo de anulaciones.";
    exit;
}

// establece el estado segun la accion
if ($accion == 'RET') {
    $estadodh = "A";
} else {
    if ($idventa == 0) {
        $estadodh = "I";
    } else {
        echo "- La venta ya se registro, debe anularla en en modulo de anulaciones.";
        exit;
    }
}


// si existe actualiza
if ($idadherenteex > 0) {

    $consulta = "
		update adherente_retira
		set
		estado = '$estadodh',
		fechahora = '$ahora'
		where
		idempresa = $idempresa
		and idadherente = $idadherente
		and fecha = '$ahorad'
		and idretiraadh = $idretiraadh
		and idventa is null
		";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // si no existe inserta
} else {
    if ($estadodh == 'A') {
        $consulta = "
			INSERT INTO adherente_retira
			(idadherente, fecha, fechahora, estado, idempresa) 
			VALUES 
			($idadherente,'$ahorad','$ahora','$estadodh',$idempresa)
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
}

if ($retdirecto == 'S') {
    // busca si existe en la tabla de retiro y el estado es activo
    $consulta = "
	SELECT * 
	FROM adherente_retira
	where
	idempresa = $idempresa
	and idadherente = $idadherente
	and fecha = '$ahorad'
	and estado = 'A'
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idretiraadh = intval($rs->fields['idretiraadh']);
    // si existe
    if ($idretiraadh > 0) {
        // busca el producto
        $consulta = "
		select p1 as precio,idmedida, idtipoproducto
		from productos 
		where
		idprod_serial is not null
		and productos.idempresa = $idempresa
		and idprod_serial = $producto
		order by productos.descripcion asc
		";

        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $precio = antisqlinyeccion($rs->fields['precio'], "float");
        $medida = intval($rs->fields['idmedida']);
        $idtipoproducto = $rs->fields['idtipoproducto'];
        $fechahora = antisqlinyeccion(date("Y-m-d H:i:s"), "text");
        $usuario = $idusu;
        $idsucursal = $idsucursal;
        $idempresa = $idempresa;
        $receta_cambiada = antisqlinyeccion("N", "text");
        $registrado = antisqlinyeccion("N", "text");
        $borrado = antisqlinyeccion("N", "text");
        $combinado = antisqlinyeccion("N", "text");
        ;
        $prod_1 = "NULL";
        $prod_2 = "NULL";


        // registra pedido
        $consulta = "
		INSERT INTO tmp_ventares_cab
		(razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, idempresa,idcanal,delivery,idmesa,
		telefono,delivery_zona,direccion,llevapos,cambio,observacion_delivery,delivery_costo,finalizado) 
		VALUES 
		('$razon_social_pred', '$ruc_pred', NULL, NULL, $precio, $idusu, $fechahora, $idsucursal, $idempresa,1,'N',0,
		NULL,NULL,NULL,NULL,NULL,NULL,NULL,'S')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // obtiene la cabecera insertada
        $consulta = "
		select idtmpventares_cab from tmp_ventares_cab where idusu = $idusu order by idtmpventares_cab desc limit 1
		";
        $rslast = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $lastid = $rslast->fields['idtmpventares_cab'];
        $idtmpventares_cab = $lastid;

        // inserta en el carrito
        $consulta = "
		INSERT INTO tmp_ventares
		(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idtmpventares_cab,finalizado) 
		VALUES 
		($producto, $idtipoproducto,1,$precio,fechahora,$usuario, $registrado, $idsucursal, $idempresa, $receta_cambiada, $borrado, $combinado, $prod_1, $prod_2,$precio,$idtmpventares_cab,'S')
		;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // marca como inactivo para que al registrar la venta vuelva a activarse si no fallo nada
        $consulta = "
		update adherente_retira
		set 
		estado = 'I'
		where
		idempresa = $idempresa
		and idadherente = $idadherente
		and fecha = '$ahorad'
		and estado = 'A'
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // respuesta json
        $arr = [
        'error' => trim($errores),
        'idpedido' => $idtmpventares_cab,
        'idservcom' => $idservcom
        ];

        // convierte a formato json
        $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        // devuelve la respuesta formateada
        echo $respuesta;
        exit;

    }

}

echo "OK";
exit;
