<?php

require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");

// funciones para stock
require_once("../../includes/funciones_stock.php");
require_once("../../includes/funciones_iva.php");
// require_once("../../includes/funciones_pulseras.php");
// require_once("../../includes/funciones_cobros.php");
// require_once("../../includes/funciones_electronica.php");

// funciones para factura electronica
if ($facturador_electronico == 'S') {

    require_once('facturaElectronica/Validate.php');
    require_once("facturaElectronica/GeneradorXMLFacturaElectronica.php");
    $obj_electro = new GeneradorXMLFacturaElectronica();
    $tipo_documento_set = 1; // 1 factura, 2 nota credito (interno nuestro no segun ninguna tabla de la set)
    $slot_datos = slot_vigente();
    $slot_firma = trim($slot_datos['nombre_slot']);    //'80052292_test';   // nombre del slot con que se dio de alta la firma digital en el servidor

    if ($slot_firma == '') {
        echo "No esta cargado el slot de la firma electronica.";
        exit;
    }

}


function redondeo_sedeco($precio, $ceros = 1, $direccion = 'N')
{
    // temporal, ideal reemplazar a denominacion minima 500 por ejemplo
    if ($ceros == 3) {
        if (substr($precio, -3, 1) >= 5) {
            $ceros_ajustado = $ceros - 1;
            $precio = substr_replace($precio, 5, -3, 1);
            $precio = floor($precio / 10 ** $ceros_ajustado) * (10 ** $ceros_ajustado);
            $ceros = $ceros_ajustado;
        }
    }
    // direccion  A: hacia arriba // B: hacia abajo N: Normal
    if ($direccion == 'A') {
        $precio_redondeado = ceil($precio / 10 ** $ceros) * (10 ** $ceros);
    } elseif ($direccion == 'B') {
        $precio_redondeado = floor($precio / 10 ** $ceros) * (10 ** $ceros);
    } else {
        $precio_redondeado = round($precio / 10 ** $ceros) * (10 ** $ceros);
    }
    return $precio_redondeado;
}



//echo 't:'.$idterminal_pc;exit;

$errores = "";
$valido = "S";

//print_r($_POST);
//exit;

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal and tipocaja = 1 order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
$idcaja_compartida = intval($rscaja->fields['idcaja_compartida']);
if ($idcaja_compartida > 0) {
    $idcaja = $idcaja_compartida;
}
if ($idcaja == 0) {
    $errores .= "- La caja debe estar abierta para poder realizar una venta.";
    $valido = "N";
}
if ($estadocaja == 3) {
    $errores .= "- La caja debe estar abierta para poder realizar una venta.";
    $valido = "N";
}

// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ventas_nostock = trim($rspref->fields['ventas_nostock']);
$mueve_stock = trim($rspref->fields['mueve_stock']);
$autoimpresor = trim($rspref->fields['autoimpresor']);
$ventacaj_impcocina = trim($rspref->fields['ventacaj_impcocina']);
$usa_vendedor = $rspref->fields['usa_vendedor'];
$obliga_vendedor = trim($rspref->fields['obliga_vendedor']);
$factura_obliga = trim($rspref->fields['factura_obliga']);
$controlalevante = intval($rspref->fields['controlalevante']);



// sucursales
$consulta = "
select preimpreso_forzar, forzar_excluyerepven from sucursales where idsucu = $idsucursal limit 1
";
$rssucauto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$forzar_excluyerepven = $rssucauto->fields['forzar_excluyerepven'];
if ($rssucauto->fields['preimpreso_forzar'] == 'S') {
    $autoimpresor = 'N';
}

// preferencias caja
$consulta = "
SELECT 
usa_motorista, obliga_motorista, valida_duplic_tipo,
 obliga_cod_transfer, usa_orden_compra, obliga_orden_compra, 
 redondeo_pref, ceros_redondeo, direccion_redondeo
FROM preferencias_caja 
WHERE  
idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_motorista = trim($rsprefcaj->fields['usa_motorista']);
$obliga_motorista = trim($rsprefcaj->fields['obliga_motorista']);
$valida_duplic_tipo = trim($rsprefcaj->fields['valida_duplic_tipo']);
$obliga_cod_transfer = trim($rsprefcaj->fields['obliga_cod_transfer']);
$numero_orden_compra = antisqlinyeccion($_POST['ocnumero'], "int");
$usar_oc = trim($rsprefcaj->fields['usa_orden_compra']);
$obligar_oc = trim($rsprefcaj->fields['obliga_orden_compra']);
$redondeo_pref = trim($rsprefcaj->fields['redondeo_pref']);
$ceros_redondeo = intval($rsprefcaj->fields['ceros_redondeo']);
$direccion_redondeo = trim($rsprefcaj->fields['direccion_redondeo']); // siempre hacia abajo por ley sedeco

if ($usar_oc == 'S') {
    if ($obligar_oc == 'S' && intval($numero_orden_compra) == 0) {
        $valido = "N";
        $errores .= "Debe indicar numero der Orden de compra, ya que es obligatoria.";
    }
}

//echo 		$mueve_stock;
//exit;
// comprobar existencia de deposito del tipo salon de ventas
$consulta = "
SELECT * 
FROM gest_depositos
where
tiposala = 2
and idempresa = $idempresa
and idsucursal = $idsucursal
and estado = 1
";
//echo $consulta;
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rsdep->fields['iddeposito']);
if ($iddeposito == 0) {
    $errores .= "No existe deposito de ventas asignado a la sucursal actual.";
    $valido = "N";
}
// si se permite facturar desde un deposito
if ($rsco->fields['factura_deposito'] == 'S') {
    // si envio el deposito
    if (intval($_POST['iddeposito']) > 0) {
        $iddeposito = intval($_POST['iddeposito']);
        // comprobar existencia de deposito del tipo salon de ventas
        $consulta = "
		SELECT * 
		FROM gest_depositos
		where
		iddeposito = $iddeposito
		and estado = 1
		limit 1
		";
        $rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iddeposito = intval($rsdep->fields['iddeposito']);
        $idsucursal_deposito = intval($rsdep->fields['idsucursal']);
        if ($iddeposito == 0) {
            $errores .= "No existe el deposito indicado.";
            $valido = "N";
        }
        /// comprueba si tiene permiso para ese deposito
        $consulta = "
		select * 
		from usuarios_depositos 
		where 
		idusuario = $idusu 
		and iddeposito = $iddeposito
		limit 1
		";
        $rsdepper = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $iddeposito = intval($rsdepper->fields['iddeposito']);
        if ($iddeposito == 0) {
            $errores .= "No tienes permisos para facturar en el deposito indicado.";
            $valido = "N";
        }

    }

}



// parametros de entrada
$retirotab = substr(trim($_POST['retirotab']), 0, 1);
//echo $retirotab;
//exit;
///print_r($_POST);
$idcliente = intval($_POST['idcliente']);
if ($_POST['codigo_vrapida'] > 0) {
    $codigo_vrapida = antisqlinyeccion($_POST['codigo_vrapida'], "text");
    $consulta = "
	select idcliente from cliente where ruc = $codigo_vrapida and estado <> 6 limit 1
	";
    $rsvrap = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rsvrap->fields['idcliente']);
    if ($idcliente == 0) {
        $consulta = "
		select idcliente from cliente where documento = $codigo_vrapida  and estado <> 6 limit 1
		";
        $rsvrap = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente = intval($rsvrap->fields['idcliente']);
    }
}
// carga de saldo a pulsera
if ($_POST['pulsera_cargauto'] == 'S') {
    $idpulsera = intval($_POST['idpulsera_carga']);
    $pulsera_monto = floatval($_POST['pulsera_monto']);
    $consulta = "
	select idproducto_carga from pulseras_preferencias limit 1
	";
    $rspulspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto_carga = intval($rspulspref->fields['idproducto_carga']);
    if ($idproducto_carga == 0) {
        $valido = 'N';
        $errores .= '- No se definio el producto a facturar en las cargas, definalo en las preferencias de la pulsera.<br />';
    }
    if ($pulsera_monto <= 0) {
        $valido = 'N';
        $errores .= '- No se indico el monto a cargar en la pulsera.<br />';
    }
    if ($idpulsera <= 0) {
        $valido = 'N';
        $errores .= '- No se indico la pulsera a cargar.<br />';
    }
    //limpiar carrrito
    $consulta = "
	update tmp_ventares 
	set borrado = 'S'
	where
	usuario = $idusu
	and finalizado = 'N'
	and registrado = 'N'
	and idsucursal = $idsucursal
	and borrado = 'N'
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
	INSERT INTO tmp_ventares
	(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab) 
	VALUES 
	($idproducto_carga,1,1,$pulsera_monto, '$ahora',$idusu, 'N', $idsucursal, $idempresa,'N', 'N', 'N', NULL, NULL,$pulsera_monto,NULL,NULL,NULL)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
if (trim($_POST['cortesia_diaria']) == 'S') {
    if (intval($_POST['cortesia_diaria_ci']) == 0) {
        $valido = 'N';
        $errores .= '- No indico la cedula de la cortesia.<br />';
    } else {
        $cortesia_diaria_ci = intval($_POST['cortesia_diaria_ci']);
        $ahorad = date("Y-m-d");
        $consulta = "
		select cortesia_diaria.idcortesiadiaria, cortesia_diaria.registrado_el, sucursales.nombre as sucursal
		from cortesia_diaria 
		inner join sucursales on sucursales.idsucu = cortesia_diaria.idsucursal
		where 
		cortesia_diaria.cedula = $cortesia_diaria_ci 
		and cortesia_diaria.fecha = '$ahorad'
		and cortesia_diaria.estado = 1
		limit 1
		";
        $rscort = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rscort->fields['idcortesiadiaria']) > 0) {
            $registrado_el_cort = date("d/m/Y H:i", strtotime($rscort->fields['registrado_el']));
            $sucursal_cort = antixss($rscort->fields['sucursal']);
            $valido = 'N';
            $errores .= "- Esta cedula ya recibio una cortesia hoy <strong>$registrado_el_cort</strong> en <strong>$sucursal_cort</strong>.<br />";
        }

    }
}
// cobro de entrada al local
if ($_POST['pulsera_entrada'] == 'S') {
    $idpulsera = intval($_POST['idpulsera']);
    $consulta = "
	select idproducto_entrada from pulseras where idpulsera = $idpulsera
	";
    $rspuls = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto_entrada = $rspuls->fields['idproducto_entrada'];
    $consulta = "
	select productos_sucursales.precio, productos.descripcion
	from productos 
	inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
	where 
	idprod_serial = $idproducto_entrada 
	and productos_sucursales.idsucursal = $idsucursal
	";
    $rsent = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $pulsera_monto = $rsent->fields['precio'];
    if ($idproducto_entrada == 0) {
        $valido = 'N';
        $errores .= '- No se definio el producto a facturar en las cargas, definalo en las preferencias de la pulsera.<br />';
    }
    /*if($pulsera_monto <= 0){
        $valido='N';
        $errores.='- No se indico el monto a cargar en la pulsera.<br />';
    }*/
    if ($idpulsera <= 0) {
        $valido = 'N';
        $errores .= '- No se indico la pulsera a cargar.<br />';
    }
    //limpiar carrrito
    $consulta = "
	update tmp_ventares 
	set borrado = 'S'
	where
	usuario = $idusu
	and finalizado = 'N'
	and registrado = 'N'
	and idsucursal = $idsucursal
	and borrado = 'N'
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
	INSERT INTO tmp_ventares
	(idproducto, idtipoproducto, cantidad, precio, fechahora, usuario, registrado, idsucursal, idempresa, receta_cambiada, borrado, combinado, idprod_mitad1, idprod_mitad2,subtotal,idlistaprecio,idpedidocat,idtmpventares_cab) 
	VALUES 
	($idproducto_entrada,1,1,$pulsera_monto, '$ahora',$idusu, 'N', $idsucursal, $idempresa,'N', 'N', 'N', NULL, NULL,$pulsera_monto,NULL,NULL,NULL)
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}
// consumision de productos en pulsera
if (intval($_POST['cod_pulsera']) > 0) {
    $cod_pulsera = antisqlinyeccion($_POST['cod_pulsera'], "text");
    $consulta = "
	select idpulsera, idclientepulsera, pulsera_saldo from pulseras where estado = 1 and barcode = $cod_pulsera order  by idpulsera asc limit 1
	";
    $rspuls = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpulsera = intval($rspuls->fields['idpulsera']);
    $pulsera_saldo = floatval($rspuls->fields['pulsera_saldo']);
    if ($idpulsera == 0) {
        $valido = 'N';
        $errores .= '- La pulsera indidcada no existe o no esta activa.<br />';
    } else {
        $idcliente = intval($rspuls->fields['idclientepulsera']);
        if ($idcliente > 0) {
            $consulta = "
			select idcliente from cliente where idcliente = $idcliente  and estado <> 6 limit 1
			";
            $rsvrap = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcliente_ex = intval($rsvrap->fields['idcliente']);
            if ($idcliente_ex == 0) {
                $valido = 'N';
                $errores .= "- El cliente [$idcliente] asignado a la pulsera [$idpulsera] no existe o fue borrado.<br />";
                $idcliente = 0;
            }
        }
    }
}




$idsucursal_clie = antisqlinyeccion($_POST['idsucursal_clie'], "int");
$idzona = trim($_POST['idzona']);
//explodear
$idf = explode("-", $idzona);
$idzona = intval($idf[0]);
//echo 'a'.$idzona; exit;
//$idzona=1;
$fac_suc = intval($_POST['fac_suc']);
$fac_pexp = intval($_POST['fac_pexp']);
$fac_nro = intval($_POST['fac_nro']);
$tipoventa = intval($_POST['condventa']); // credito o contado
$mediopago = intval($_POST['mediopago']); // efectivo, tarjeta, cheque, etc
$canal = intval($_POST['canal']);
$domicilio = intval($_POST['domicilio']);
$idadherente = intval($_POST['idadherente']);
$idadherente_constante = $idadherente; // no borrar se usa para otra cosa
$idservcom = intval($_POST['idservcom']);
$pin = antisqlinyeccion($_POST['pin'], 'text');
$us_self = antisqlinyeccion($_POST['us_self'], 'text');
$nombre_deliv = "NULL";
$apellido_deliv = "NULL";
$idclientedel = "NULL";
$iddomicilio = "NULL";
$observacion = antisqlinyeccion($_POST['observacion'], 'text');
$idvendedor = intval($_POST['idvendedor']);
$idmotorista = intval($_POST['idmotorista']);
$idcanalventa = antisqlinyeccion($_POST['idcanalventa'], 'int');
if (intval($_SESSION['idcanalventa']) > 0) {
    $idcanalventa = intval($_SESSION['idcanalventa']);
}
$fecha_venta = trim($_POST['fecha_venta']);
$codpedido_externo = antisqlinyeccion($_POST['codpedido_externo'], 'text');
if ($venta_retroactiva == 'S') {
    if (trim($fecha_venta) == '') {
        $fecha_venta = $ahora;
    } else {
        $fecha_venta = date("Y-m-d H:i:s", strtotime($_POST['fecha_venta']));
    }
} else {
    $fecha_venta = $ahora;
}
if ($fac_nro > 0) {
    $ticketofactura = "FAC";
} else {
    $ticketofactura = "TK";
}
if ($idadherente > 0) {
    $factura_obliga = "N";
}
if ($ticketofactura == "FAC") {
    if ($fac_suc == 0) {
        $valido = 'N';
        $errores .= '- No se indico la sucursal de la factura.<br />';
    }
    if ($fac_pexp == 0) {
        $valido = 'N';
        $errores .= '- No se indico el punto exped de la factura.<br />';
    }
}
if ($factura_obliga == 'S') {
    if ($fac_nro == 0) {
        $valido = 'N';
        $errores .= '- No se indico la factura.<br />';
    }
}

if ($usa_vendedor == 'S') {
    if ($obliga_vendedor == 'S') {
        if (intval($idvendedor) == 0) {
            $valido = 'N';
            $errores .= '- No indicaste el vendedor.<br />';
        }
    }
} else {
    $idvendedor = 0;
}
if (trim($_POST['pin_vendedor']) != '') {
    $pin_vendedor = antisqlinyeccion(trim($_POST['pin_vendedor']), "text");
    $consulta = "
	select * 
	from vendedor 
	where 
	pin = $pin_vendedor
	and estado = 1
	limit 1
	";
    $rsven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idvendedor = intval($rsven->fields['idvendedor']);
    if (intval($idvendedor) == 0) {
        $valido = 'N';
        $errores .= '- El pin indicado no corresponde a ningun vendedor activo.<br />';
    }
}
// pre impreso o autoimpresor
if ($ticketofactura == 'FAC') {
    $factura_suc = intval($factura_suc);
    $factura_pexp = intval($factura_pexp);
    $ahorad = date("Y-m-d", strtotime($ahora));
    $consulta = "
	SELECT tipoimpreso 
	FROM facturas 
	where 
	estado = 'A'
	and valido_hasta >= '$ahorad'
	and valido_desde <= '$ahorad'
	and sucursal = $factura_suc
	and punto_expedicion = $factura_pexp
	and idtipodocutimbrado = 1
	order by fin desc
	limit 1
	";
    $rstipoimpre = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (trim($rstipoimpre->fields['tipoimpreso']) != '') {
        if (trim($rstipoimpre->fields['tipoimpreso']) == 'AUT') {
            $autoimpresor = "S";
        }
        if (trim($rstipoimpre->fields['tipoimpreso']) == 'PRE') {
            $autoimpresor = "N";
        }
    }
}
// canal de venta
if ($idcanalventa > 0) {
    $consulta = "
	SELECT idcanalventa, canal_venta, obliga_codexterno 
	FROM canal_venta 
	where 
	idcanalventa = $idcanalventa
	limit 1
	";
    $rscv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (trim($rscv->fields['obliga_codexterno']) == 'S') {
        if (trim($_POST['codpedido_externo']) == '') {
            $valido = 'N';
            $errores .= '- Debe indicar el codigo externo para el canal de ventas seleccionado.<br />';
        }
    }

}
// cliente previo
if (intval($_SESSION['idclienteprevio']) > 0) {
    $idclienteprevio = intval($_SESSION['idclienteprevio']);
    $consulta = "
	select idcliente, idvendedor, idcanalventacli
	from cliente 
	where 
	idcliente = $idclienteprevio
	and estado <> 6 
	limit 1
	";
    $rscprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclienteprevio = $rscprev->fields['idcliente'];
    if ($idclienteprevio > 0) {
        $idcliente = $idclienteprevio;
        $idvendedor = intval($rscprev->fields['idvendedor']);
        $idcanalventa = intval($rscprev->fields['idcanalventacli']);
    }
}

// validar pin si corresponde
if ($_SESSION['self'] == 'S') {
    if (trim($_POST['pin']) == '') {
        $valido = 'N';
        $errores .= '- No completo el pin.<br />';
    } else {

        $consulta = "
		SELECT * 
		FROM clientes_codigos
		where
		idempresa = $idempresa
		and estado_self = 1
		and us_self = $us_self
		and pass_self = $pin
		limit 1
		";
        $rspinval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        if (intval($rspinval->fields['idcodclie']) == 0) {
            $valido = 'N';
            $errores .= '- El pin ingresado no es correcto.<br />';
        }
        // registrar intento y bloquear si supera una cantidad x

    }
}

if ($domicilio > 0) {
    $buscar = "
	Select *, referencia 
	from cliente_delivery 
	inner join cliente_delivery_dom	on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
	where 
	iddomicilio=$domicilio 
	and cliente_delivery.idempresa=$idempresa 
	limit 1
	";
    $rscasa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idclientedel = $rscasa->fields['idclientedel'];
    $iddomicilio = $rscasa->fields['iddomicilio'];
    $direccion = trim($rscasa->fields['direccion']);
    $direccion = str_replace("'", "", $direccion);
    if (trim($rscasa->fields['referencia']) != '') {
        $direccion .= ' - '.trim($rscasa->fields['referencia']);
    }
    $telefono = trim($rscasa->fields['telefono']);
    $nombre_deliv = antisqlinyeccion(trim($rscasa->fields['nombres']), 'text');
    $apellido_deliv = antisqlinyeccion(trim($rscasa->fields['apellidos']), 'text');
    if ($idzona == 0) {
        $valido = 'N';
        $errores .= '- Debes seleccionar la zona del delivery.<br />';
        $idzona = 0;
    }
}
$idmesa = intval($_POST['mesa']);
//echo $idmesa;
$monto_recibido = floatval($_POST['monto_recibido']); // solo para calcular vuelto, no se usa insert
$descuento = floatval($_POST['descuento']);
$motivo_descuento = antisqlinyeccion(trim($_POST['motivo_descuento']), 'text');
$pedido = intval($_POST['pedido']); // si ya existe el temporal envia el idtmpventares_cab
$chapa = antisqlinyeccion($_POST['chapa'], 'text');
$numcheque = 0;
$banco = intval($_POST['banco']);
$adicional = antisqlinyeccion($_POST['adicional'], 'text'); // para texto
$adicional_int = solonumeros($_POST['adicional']); // para numero




$montomixto = intval($_POST['montomixto']); // para numero
$numtarjeta = 0;
$numerotrans = 0;
$montotransferido = 0;
$montocheque = 0;
// si es un pedido
if ($pedido == 0 && $idmesa == 0) {
    $generatmpcab = 'S';
} else {
    $generatmpcab = 'N';
}


if (intval($idzona) > 0) {
    //$buscar="Select costoentrega from gest_zonas where idzona=$idzona and idempresa=$idempresa and idsucursal=$idsucursal and estado=1 limit 1";
    //$rszonita = $conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    //$delivery_costo=intval($rszonita->fields['costoentrega']);
    $delivery_costo = 0;
    $delivery = antisqlinyeccion('S', 'text');
} else {
    $delivery_costo = 0;
    $idzona = 0;
    $delivery = antisqlinyeccion('N', 'text');
}
$delivery_costo = 0;


// si es venta por caja
if ($generatmpcab == 'S') {
    // calcula monto de la venta

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
	";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $totalprod = intval($rs->fields['total']);



    // total producto delivery en carrito
    $consulta = "
	select count(*) as total_deliv
	from tmp_ventares 
	inner join productos on tmp_ventares.idproducto = productos.idprod_serial
	where 
	tmp_ventares.registrado = 'N'
	and tmp_ventares.usuario = $idusu
	and tmp_ventares.borrado = 'N'
	and tmp_ventares.finalizado = 'N'
	and tmp_ventares.idsucursal = $idsucursal
	and productos.idtipoproducto = 6
	";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_deliv = intval($rs->fields['total_deliv']);


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
	";

    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $montototalprod = intval($rs->fields['total_monto']);
    // monto total agregados
    /*$consulta="
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
    $rsag = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $montototalag=intval($rsag->fields['montototalagregados']);*/
    $montototalag = 0;

    // descuento por cliente
    $consulta = "
	select porce_desc from cliente where idcliente = $idcliente limit 1
	";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (floatval($rscli->fields['porce_desc']) > 0) {
        $descuento = round($montototalprod * ($rscli->fields['porce_desc'] / 100), 0);
        $motivo_descuento = "'DESCUENTO CLIENTE'";
    }
    //*************  VALIDACIONES si es facturador electronico ****************/
    /*if($facturador_electronico == 'S'){
        // si es factura
        if($ticketofactura == 'FAC'){
            $consulta="
            select email, borrable, direccion, numero_casa, idtipooperacionset
            from cliente
            where
            idcliente = $idcliente
            limit 1
            ";
            $rscli = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            $borrable=trim($rscli->fields['borrable']);
            $idtipooperacionset=trim($rscli->fields['idtipooperacionset']);
            // si no es el cliente generico
            if($borrable == 'S'){
                if(trim($rscli->fields['email']) == ''){
                    $valido='N';
                    $errores.='- El email no puede estar vacio cuando es una factura electronica.<br />';
                }
                if(trim($rscli->fields['direccion']) != ''){
                    if(intval($rscli->fields['numero_casa']) == 0){
                        $valido='N';
                        $errores.='- El numero de casa no puede estar vacio cuando hay un domicilio cargado.<br />';
                    }
                    if(intval($rscli->fields['numero_casa']) < 111){
                        $valido='N';
                        $errores.='- El numero de casa no puede ser menor a 111.<br />';
                    }

                }

            } // if($borrable == 'S'){
        } // if($ticketofactura == 'FAC'){
    }*/ // if($facturador_electronico == 'S'){
    //*************  VALIDACIONES si es facturador electronico ****************/


    // redondeo por ley sedeco
    if ($redondeo_pref == 'S') {
        $montototalprod_redondo = redondeo_sedeco($montototalprod, $ceros_redondeo, $direccion_redondeo);
        if ($montototalprod_redondo != $montototalprod) {
            if ($descuento > 0) {
                $motivo_descuento = "'DESCUENTO'";
                $pchar_descuento = "'DESCUENTO'";
            } else {
                $motivo_descuento = "'REDONDEO SEDECO RES 347'";
                $pchar_descuento = "'REDONDEO SEDECO RES 347'";
            }
            $descuento = round($montototalprod - $montototalprod_redondo + $descuento, 0);

        }
    }

    $descuento_sobre_factura = $descuento; // por que abajo se vuelve a usar la variable en descuento por producto

    //echo $montototalprod_redondo; echo $motivo_descuento;  echo $descuento; exit;
    // monto total venta
    $montototal = $montototalprod + $montototalag;
    $totalventa = $montototal - $descuento + $delivery_costo;

    if ($montototal < $descuento_sobre_factura) {
        $valido = 'N';
        $errores .= '- El descuento no puede superar el monto de la factura.<br />';
    }


} else {  // si es venta por tablet u otro
    //Total de la venta real
    if ($pedido > 0) {
        $buscar = "Select sum(monto) as total, idsolicitudcab 
		from  tmp_ventares_cab
		where 
		idtmpventares_cab=$pedido and registrado='N' 
		and idempresa = $idempresa and idsucursal = $idsucursal";
    } else {
        $buscar = "Select sum(monto) as total 
		from  tmp_ventares_cab 
		where  
		finalizado='S' 
		and registrado='N' 
		and estado=1 
		and tmp_ventares_cab.idsucursal = $idsucursal
		and tmp_ventares_cab.idempresa = $idempresa
		and idmesa = $idmesa";
    }
    $rsmo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $totalvendido = $rsmo->fields['total'];
    $idsolicitudcab = $rsmo->fields['idsolicitudcab'];
    if ($idsolicitudcab > 0) {
        if ($tipoventa != 2) {
            $valido = 'N';
            $errores .= '- La factura debe ser a credito cuando hay una solicitud vinculada al pedido.<br />';
        }
    }

    $consulta = "
	select * from cliente where idcliente = $idcliente limit 1
	";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rscli->fields['porce_desc'] > 0) {
        $descuento = round($totalvendido * ($rscli->fields['porce_desc'] / 100), 0);
        $motivo_descuento = "'DESCUENTO CLIENTE'";
    }

    // redondeo por ley sedeco
    if ($redondeo_pref == 'S') {
        $montototalprod_redondo = redondeo_sedeco($montototalprod, $ceros_redondeo, $direccion_redondeo);
        if ($montototalprod_redondo != $montototalprod) {
            if ($descuento > 0) {
                $motivo_descuento = "'DESCUENTO'";
                $pchar_descuento = "'DESCUENTO'";
            } else {
                $motivo_descuento = "'REDONDEO SEDECO RES 347'";
                $pchar_descuento = "'REDONDEO SEDECO RES 347'";
            }
            $descuento = round($montototalprod - $montototalprod_redondo + $descuento, 0);

        }
    }

    $descuento_sobre_factura = $descuento; // por que abajo se vuelve a usar la variable en descuento por producto
    $totalventa = $totalvendido + $delivery_costo - $descuento;

    if ($totalvendido < $descuento_sobre_factura) {
        $valido = 'N';
        $errores .= '- El descuento no puede superar el monto de la factura.<br />';
    }
}

// si es el modulo viejo
if (trim($_POST['modviejo']) == 'S') {

    // si es pago mixto
    if ($mediopago == 3) {
        $monto_recibido = $totalventa - $montomixto;
        $efectivo = $monto_recibido;
        $tarjeta = $montomixto;
        $tipotarjeta = 1;
        // insertar en carrito	efectivo
        $consulta = "
		insert into carrito_cobros_ventas
		(idformapago, monto_forma, registrado_por, registrado_el)
		values
		(1, $efectivo, $idusu, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // insertar en carrito	tarjeta
        $consulta = "
		insert into carrito_cobros_ventas
		(idformapago, monto_forma, registrado_por, registrado_el)
		values
		(2, $tarjeta, $idusu, '$ahora')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


}
// si medio de pago es mixto
if ($mediopago == 3) {
    // total en carrito pagos
    $consulta = "
	select sum(monto_forma) as total_monto_forma
	from carrito_cobros_ventas
	where
	registrado_por = $idusu
	";
    $rscarpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_carrito_pagventas = $rscarpag->fields['total_monto_forma'];
    if ($totalventa <> $total_carrito_pagventas) {
        $totalventa_txt = formatomoneda($totalventa, 4, 'N');
        $total_carrito_pagventas_txt = formatomoneda($total_carrito_pagventas, 4, 'N');
        $valido = "N";
        $errores .= "- El monto de la venta ($totalventa_txt) debe ser el mismo que los pagos registrados ($total_carrito_pagventas_txt) cuando es un pago mixto.";

    }
}

// si se bloquea la venta sin stock
if ($ventas_nostock == 2) {
    // si es venta por caja
    if ($generatmpcab == 'S') {

        $consulta = "
		select * from 
        (
            select tmp_ventares.idproducto, productos.descripcion, COALESCE(sum(cantidad),0) as cantidad_carrito,
                COALESCE((
                 select gest_depositos_stock_gral.disponible
                from insumos_lista
                inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
                where
                insumos_lista.idproducto = tmp_ventares.idproducto
                and gest_depositos_stock_gral.iddeposito = $iddeposito
                and insumos_lista.idproducto is not null
                ),0) as cantidad_stock
            
    		from tmp_ventares 
    		inner join productos on tmp_ventares.idproducto = productos.idprod_serial
    		where 
    		tmp_ventares.registrado = 'N'
    		and tmp_ventares.usuario = $idusu
    		and tmp_ventares.borrado = 'N'
    		and tmp_ventares.finalizado = 'N'
    		and tmp_ventares.idsucursal = $idsucursal
    		and (select insumos_lista.hab_invent from insumos_lista where insumos_lista.idproducto = tmp_ventares.idproducto)  = 1
    		group by tmp_ventares.idproducto, productos.descripcion
    		Limit 5
		) supera_stock 
		where
		cantidad_carrito > cantidad_stock
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;exit;
        // si hay productos en el carrito con stock 0 o menor
        if (intval($rs->fields['idproducto']) > 0) {
            $valido = 'N';
            $errores .= '- Esta intentando vender productos sin stock.<br />';
            $errores .= "Sin Stock: ".$saltolinea;
            $prodsinstock = "";
            while (!$rs->EOF) {
                $stock_car = formatomoneda(floatval($rs->fields['cantidad_carrito']));
                $stock_dep = formatomoneda(floatval($rs->fields['cantidad_stock']));
                $prodsinstock .= strtolower($rs->fields['descripcion'])." (Stock: $stock_dep, Venta: $stock_car),".$saltolinea;
                $rs->MoveNext();
            }
            $errores .= substr(trim($prodsinstock), 0, -1);
            $errores .= ".<br />";

        }
        // si tiene receta, verifica que cada ingrediente haya en stock:


        // si es venta por tablet u otro
    } else {
        // si no es mesa
        if ($pedido > 0) {

            $buscar = "
			select * from 
			(
				select tmp_ventares.idproducto, productos.descripcion, COALESCE(sum(cantidad),0) as cantidad_carrito,
					COALESCE((
					 select gest_depositos_stock_gral.disponible
					from insumos_lista
					inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
					where
					insumos_lista.idproducto = tmp_ventares.idproducto
					and gest_depositos_stock_gral.iddeposito = $iddeposito
					and insumos_lista.idproducto is not null
					),0) as cantidad_stock

				from  tmp_ventares_cab 
				inner join tmp_ventares on tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
				inner join productos on tmp_ventares.idproducto = productos.idprod_serial
				where 
				tmp_ventares_cab.idtmpventares_cab=$pedido 
				and tmp_ventares.borrado = 'N'
				and (select insumos_lista.hab_invent from insumos_lista where insumos_lista.idproducto = tmp_ventares.idproducto)  = 1
				group by tmp_ventares.idproducto, productos.descripcion
				Limit 5
			) supera_stock 
			where
			cantidad_carrito > cantidad_stock
			";
            // si es mesa
        } else { // if ($pedido > 0){

            $buscar = "
			select * from 
			(
				select tmp_ventares.idproducto, productos.descripcion, COALESCE(sum(cantidad),0) as cantidad_carrito,
						COALESCE((
						 select gest_depositos_stock_gral.disponible
						from insumos_lista
						inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
						where
						insumos_lista.idproducto = tmp_ventares.idproducto
						and gest_depositos_stock_gral.iddeposito = $iddeposito
						and insumos_lista.idproducto is not null
						),0) as cantidad_stock

				from  tmp_ventares_cab 
				inner join tmp_ventares on tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
				inner join productos on tmp_ventares.idproducto = productos.idprod_serial
				where 
					tmp_ventares_cab.idmesa = $idmesa
					and tmp_ventares.borrado = 'N'
					and tmp_ventares_cab.estado=1 
					and tmp_ventares_cab.idsucursal = $idsucursal
					and tmp_ventares_cab.finalizado='S' 
					and tmp_ventares_cab.registrado='N' 
					and (select insumos_lista.hab_invent from insumos_lista where insumos_lista.idproducto = tmp_ventares.idproducto)  = 1
				group by tmp_ventares.idproducto, productos.descripcion
				Limit 5
			) supera_stock 
			where
			cantidad_carrito > cantidad_stock
			";
        } // if ($pedido > 0){

        $rsmo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        // si hay productos en el carrito con stock 0 o menor
        /*if(intval($rsmo->fields['idproducto']) > 0){
            $valido='N';
            $errores.='- Esta intentando vender productos sin stock.<br />';
            $errores.="Sin Stock: ";
            $prodsinstock="";
            while(!$rsmo->EOF){
                $prodsinstock.=strtolower($rsmo->fields['descripcion']).", ";
            $rsmo->MoveNext(); }
            $errores.=substr(trim($prodsinstock),0,-1);
            $errores.=".<br />";
        }*/
        if (intval($rsmo->fields['idproducto']) > 0) {
            $valido = 'N';
            $errores .= '- Esta intentando vender productos sin stock.<br />';
            $errores .= "Sin Stock: ".$saltolinea;
            $prodsinstock = "";
            while (!$rsmo->EOF) {
                $stock_car = formatomoneda(floatval($rsmo->fields['cantidad_carrito']));
                $stock_dep = formatomoneda(floatval($rsmo->fields['cantidad_stock']));
                $prodsinstock .= strtolower($rsmo->fields['descripcion'])." (Stock: $stock_dep, Venta: $stock_car),".$saltolinea;
                $rsmo->MoveNext();
            }
            $errores .= substr(trim($prodsinstock), 0, -1);
            $errores .= ".<br />";

        } // if(intval($rsmo->fields['idproducto']) > 0){

    }

}

// conversiones
if ($fac_nro == 0) {
    $factura = 'NULL';
    $fac_suc = '';
    $fac_pexp = '';
} else {
    $factura = antisqlinyeccion(trim(agregacero($fac_suc, 3).agregacero($fac_pexp, 3).agregacero($fac_nro, 7)), 'text');
}

// si envio factura
if ($ticketofactura == 'FAC') {

    // si es autoimpresor reemplaza el numero de factura enviada
    if ($autoimpresor == 'S') {
        // omite la factura escrita y fuerza la que debe ser segun bd
        $proxfactura = prox_factura_auto($factura_suc, $factura_pexp, $idempresa);
        //$factura=antisqlinyeccion(trim(agregacero($factura_suc,3).agregacero($factura_pexp,3).agregacero($proxfactura,7)),'text');
    } else {
        $proxfactura = $fac_nro;
    }

    $factura = antisqlinyeccion(trim(agregacero($factura_suc, 3).agregacero($factura_pexp, 3).agregacero($proxfactura, 7)), 'text');
    $timbradodatos = timbrado_tanda($factura_suc, $factura_pexp, $idempresa, $proxfactura, 1, $fecha_venta);
    $fac_nro = $proxfactura;
    $fac_suc = $factura_suc;
    $fac_pexp = $factura_pexp;



    $idtandatimbrado = $timbradodatos['idtanda'];
    $timbrado = solonumeros($timbradodatos['timbrado']);
    $valido_hasta = $timbradodatos['valido_hasta'];
    $valido_desde = $timbradodatos['valido_desde'];
    $inicio_timbrado = $timbradodatos['inicio'];
    $fin_timbrado = $timbradodatos['fin'];
    if (intval($idtandatimbrado) == 0) {
        $valido = 'N';
        $errores .= '- No existe tanda de timbrado para la sucursal y punto de expedicion: '.agregacero($factura_suc, 3).'-'.agregacero($factura_pexp, 3).$saltolinea;
    }
    if ($valida_duplic_tipo == 'FT') {
        if (trim($timbrado) == '') {
            $timbrado = 0;
        }
        $whereaddfac = " and timbrado = $timbrado ";
    }

    $consulta = "
	select idventa, factura, fecha 
	from ventas
	 where 
	 factura = $factura 
	 and estado <> 6 
	 $whereaddfac
	 limit 1;
	";
    $rsfacex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $fechafacex = date("d/m/Y", strtotime($rsfacex->fields['fecha']));
    if ($rsfacex->fields['idventa'] > 0) {
        $valido = 'N';
        $errores .= '- Ya existe otra factura con la misma numeracion: '.$factura.', registrada en fecha: '.$fechafacex.'.<br />';
        // si hay timbrado activo
        if (intval($idtandatimbrado) > 0) {
            $idtandatimbrado = intval($idtandatimbrado);
            $consulta = "
			SELECT tipoimpreso
			FROM timbrado
			inner join facturas on facturas.idtimbrado = timbrado.idtimbrado
			where 
			facturas.idtanda = $idtandatimbrado
			limit 1
			";
            $rstimbprox = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si es autoimpresor
            if (trim($rstimbprox->fields['tipoimpreso']) == 'AUT') {
                // cambia correlatividad si es autoimpresor
                $consulta = "
				update lastcomprobantes
				set
					numfac=$fac_nro,
					factura=$factura
				where 
				idsuc=$factura_suc
				and pe=$factura_pexp
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } // if($rstimbprox->fields['tipoimpreso'] == 'AUT'){
        } // if(intval($idtandatimbrado) > 0){
    } // if($rsfacex->fields['idventa'] > 0){
} // if($ticketofactura == 'FAC'){


$totalcobrar = $totalventa;
if ($montomixto < 0) {
    $montomixto = 0;
}
// valores por defecto que luego se cambian
$tipotarjeta = "NULL";
$efectivo = 0;
$tarjeta = 0;

// validar medio pago para factura electronica
if ($facturador_electronico == 'S') {
    // si no es pago mixto
    if ($mediopago != 3) {
        $consulta = "
		select idformapago_set from formas_pago where idforma = $mediopago
		";
        $rspagset = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // forma pago set tarjeta credito y debito
        if ($rspagset->fields['idformapago_set'] == 3 or $rspagset->fields['idformapago_set'] == 4) {
            $iddenominaciontarjeta = intval($_POST['iddenominaciontarjeta']);
            if ($iddenominaciontarjeta == 0) {
                $valido = 'N';
                $errores .= '- Debes indicar la denominacion de tarjeta (visa, mastercard, etc) cuando la forma de pago es tarjeta de credito o debito.<br />';
            }
        }
        // cheque
        if ($rspagset->fields['idformapago_set'] == 2) {
            // numero cheque y banco
            $idbanco = intval($_POST['banco']);
            if ($idbanco == 0) {
                $valido = 'N';
                $errores .= '- Debes indicar el banco cuando la forma de pago es cheque.<br />';
            }
            $numcheque = trim($_POST['adicional']);
            if ($numcheque == '') {
                $valido = 'N';
                $errores .= '- Debes indicar numero de cheque cuando la forma de pago es cheque.<br />';
            }
        }
        // pago multiple
    } else {
        $consulta = "
		SELECT carrito_cobros_ventas.*, formas_pago.idformapago_set
		FROM carrito_cobros_ventas 
		inner join formas_pago on formas_pago.idforma = carrito_cobros_ventas.idformapago
		WHERE 
		registrado_por = $idusu
		order by idcarritocobrosventas asc
		";
        //echo $consulta;
        $rscarcobven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rscarcobven->EOF) {
            $monto_pago_det = $rscarcobven->fields['monto_forma'];
            $idformapago = $rscarcobven->fields['idformapago'];
            $idformapago_set = $rscarcobven->fields['idformapago_set'];
            // forma pago set tarjeta credito y debito
            if ($rscarcobven->fields['idformapago_set'] == 3 or $rscarcobven->fields['idformapago_set'] == 4) {
                $iddenominaciontarjeta = intval($rscarcobven->fields['iddenominaciontarjeta']);
                if ($iddenominaciontarjeta == 0) {
                    $valido = 'N';
                    $errores .= '- Debes indicar la denominacion de tarjeta (visa, mastercard, etc) cuando la forma de pago es tarjeta de credito o debito.(pm)<br />';
                }
            }
            // cheque
            if ($rscarcobven->fields['idformapago_set'] == 2) {
                // numero cheque y banco
                $idbanco = intval($rscarcobven->fields['idbanco']);
                if ($idbanco == 0) {
                    $valido = 'N';
                    $errores .= '- Debes indicar el banco cuando la forma de pago es cheque.(pm)<br />';
                }
                $numcheque = trim($rscarcobven->fields['cheque_numero']);
                if ($numcheque == '') {
                    $valido = 'N';
                    $errores .= '- Debes indicar numero de cheque cuando la forma de pago es cheque.(pm)<br />';
                }
            }


            $rscarcobven->MoveNext();
        }
    }
}

// efectivo
if ($mediopago == 1) {
    $efectivo = $totalcobrar;
}
// tarjeta de credito
if ($mediopago == 2) {
    $tarjeta = $totalcobrar;
    $tipotarjeta = 1;
}
// mixto
if ($mediopago == 3) {
    $monto_recibido = $totalcobrar - $montomixto;
    $efectivo = $monto_recibido;
    $tarjeta = $montomixto;
    $tipotarjeta = 1;
}
// tarjeta de debito
if ($mediopago == 4) {
    $tarjeta = $totalcobrar;
    $tipotarjeta = 2;
}
// cheque
if ($mediopago == 5) {
    $montocheque = $totalcobrar;
    $numcheque = antisqlinyeccion($adicional_int, "text");
}
// transferencia
if ($mediopago == 6) {
    $montotransferido = $totalcobrar;
}
// credito
if ($mediopago == 7 || $mediopago == 8) {
    $monto_recibido = $totalcobrar;
}

//echo $mediopago;
//echo 'a'.$fac_suc;exit;
if ($mediopago == 9) {
    //Venta rapida
    $mediopago = 1;
    $monto_recibido = $totalcobrar;
    $efectivo = $totalcobrar;
    $factura = 'NULL';
    $fac_suc = '';
    $fac_pexp = '';
    $fac_nro = 0;
    //$ticketofactura='TK';
}

// calcular vuelto
$vuelto = ($monto_recibido - $totalcobrar);
if ($mediopago != 1) {
    $vuelto = 0;
}


if ($idzona > 0) {
    $canal = 3; // Delivery
    $delivery = antisqlinyeccion('S', 'text');
    $esta = "1";
    if (intval($rspref->fields['delivery_caja']) == 1) {
        $rendido = 'S'; // entra dinero en caja
    } else {
        $rendido = 'N'; // debe marcar como rendido
    }
} else {
    $esta = '2';
    $rendido = 'S';
}
if ($idmesa > 0) {
    $canal = 4; // Mesa
    $rendido = 'S';
}
$idcanal = $canal;

// validaciones
if ($idcliente == 0) {
    $valido = 'N';
    $errores .= '- Debes seleccionar un cliente.<br />';
}
if ($tipoventa == 0) {
    $valido = 'N';
    $errores .= '- Debes indicar si es venta a credito o contado.<br />';
}
if ($canal == 0) {
    $valido = 'N';
    $errores .= '- No se indico el canal de ventas.<br />';
}
if (intval($mediopago) == 0) {
    $valido = 'N';
    $errores .= '- No se indico la forma de pago.<br />';
}

// validaciones condicionadas
// si el canal es delivery
if ($canal == 3) {
    if ($idzona == 0) {
        $valido = 'N';
        $errores .= '- Debes indicar la zona de delivery.<br />';
    }
    if ($obliga_motorista == 'S') {
        if ($idmotorista == 0) {
            $valido = 'N';
            $errores .= '- Debes indicar el motorista de delivery.<br />';
        }
    }
}
// si el canal es mesa
if ($canal == 4) {
    if ($idmesa == 0) {
        $valido = 'N';
        $errores .= '- Debes indicar la mesa.<br />';
    }
}
// si hay descuento obliga a poner motivo
if (floatval($_POST['descuento']) > 0) {
    if (trim($_POST['motivo_descuento']) == '') {
        $valido = 'N';
        $errores .= '- Debes indicar el motivo de descuento.<br />';
    }
}
// si es pago por transferencia
if ($mediopago == 6) {
    // si se obliga a poner codigo
    if ($obliga_cod_transfer == 'S') {
        if (intval($adicional_int) == 0) {
            $valido = 'N';
            $errores .= '- Debes indicar el codigo de transferencia.<br />';
        }
    }
}
//echo 'desc:'.$descuento;exit;
/// el descuento puede vernir por variable en vez de por post
if (floatval($descuento) > 0) {
    // busca si existe el producto descuento debe estar borrado
    $consulta = "
	select idprod_serial from productos where idtipoproducto = 8 limit 1
	";
    $rsdesc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idproducto_descuento = intval($rsdesc->fields['idprod_serial']);
    // si no existe crea
    if ($idproducto_descuento == 0) {
        $valido = 'N';
        $errores .= '- No existe el articulo descuento, ingrese a productos y se creara automaticamente.'.$saltolinea;
    }
}
// valida que el monto sea igual a la venta si no es efectivo
if ($mediopago != 1 && $mediopago != 3) {
    /*if($totalcobrar <> $monto_recibido){
        $valido='N';
        $errores.='- El monto recibido debe ser igual a la venta cuando el medio de pago no es efectivo o mixto.<br />';
    }*/
}

// si es una mesa no puede ser delivery
if ($idmesa > 0) {
    if ($idzona > 0) {
        $valido = 'N';
        $errores .= '- No puedes enviar delivery a una mesa.<br />';
    }
}



//Busquedas


// datos del cliente
if ($idadherente == 0) {
    $consulta = "select * from cliente where idcliente = $idcliente and idempresa = $idempresa";
    $rscliente = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rscliente->fields['idcliente']);
    $ruc = trim($rscliente->fields['ruc']);
    $razon_social = $rscliente->fields['razon_social'];
    $rucx = explode("-", $ruc);
    $ruc_pri = intval(trim($rucx[0]));
    $ruc_dv = substr(intval($rucx[1]).'', 0, 1);
} else {

    $buscar = "Select * from adherentes where idadherente=$idadherente and idempresa=$idempresa";
    $rsad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcliente = intval($rsad->fields['idcliente']);
    $consulta = "select * from cliente where idcliente = $idcliente and idempresa = $idempresa";
    $rscliente = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rscliente->fields['idcliente']);
    $ruc = trim($rscliente->fields['ruc']);
    $razon_social = $rscliente->fields['razon_social'];
    $rucx = explode("-", $ruc);
    $ruc_pri = intval(trim($rucx[0]));
    $ruc_dv = substr(intval($rucx[1]).'', 0, 1);
}

// si envio factura
/**if($ticketofactura == 'FAC'){
    $consulta="
    select idventa, factura from ventas where factura = $factura and estado <> 6 limit 1;
    ";
    $rsfacex = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    if($rsfacex->fields['idventa'] > 0){
        $valido='N';
        $errores.='- Ya existe otra factura con la misma numeracion.<br />';
    }
}*/

/*
if ($idadherente == 0){
    $idadherente='NULL';
}
if($idservcom == 0){
    $idservcom='NULL';
}*/
// validaciones de existencia de datos
if ($idcliente == 0) {
    $valido = 'N';
    $errores .= '- El cliente seleccionado no existe.<br />';
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
		select idproducto, (select descripcion from productos where idprod_serial = tmp_ventares.idproducto) as producto
		from tmp_ventares 
		where 
		borrado = 'N' 
		and registrado = 'N'
		and idempresa = $idempresa
		and usuario = $idusu
		$whereaddproh
		and idproducto in (
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
			select idproducto, (select descripcion from productos where idprod_serial = tmp_ventares.idproducto) as producto
			from tmp_ventares 
			where 
			borrado = 'N' 
			and registrado = 'N'
			and idempresa = $idempresa
			and usuario = $idusu
			$whereaddproh
			and idproducto not in (
								select idproducto 
								from producto_serviciocom
								where
								idserviciocom = $idservcom
								and idempresa = $idempresa
							  )
			limit 10
			";
            $rsprohser = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si encontro productos prohibidos
            if (intval($rsprohser->fields['idproducto']) > 0) {
                //$errores="si:".$consulta;
                $valido = 'N';
                $errores .= '- La venta que intentas realizar contiene productos no asignados al servicio seleccionado. ';
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

// valida saldo de la pulsera
if (intval($_POST['cod_pulsera']) > 0) {
    $consumo_a_cargar = floatval($totalcobrar);
    $consumo_a_cargar_txt = formatomoneda($consumo_a_cargar);
    $pulsera_saldo_txt = formatomoneda($pulsera_saldo);
    if ($consumo_a_cargar > $pulsera_saldo) {
        $valido = 'N';
        $errores .= "- El consumo que intentas cargar ($consumo_a_cargar_txt) supera el saldo de la pulsera ($pulsera_saldo_txt).<br />";
    }
}

// bloquear factura con monto cero
$factura_val = str_replace("NULL", "", str_replace("'", "", $factura));
if (trim($factura_val) != '') {
    if ($totalcobrar <= 0) {
        $valido = 'N';
        $errores .= "- No se puede emitir factura legal con monto cero.";
    }
}

////////////////////////////////// PEDIDOS //////////////////////////////////
if ($generatmpcab == 'S') {

    // validaciones para pedidos
    if ($totalprod == 0) {
        $errores .= "- Debe agregar al menos 1 producto al carrito.<br />";
        $valido = "N";
    }
    // si es delivery y no tiene cargado el producto delivery
    if ($canal == 3) {
        if ($rsco->fields['permite_delivery_sinitem'] != 'S') {
            if (intval($total_deliv) <= 0) {
                $errores .= "- Debe agregar el producto delivery.<br />";
                $valido = "N";
            }
        }
    }
    // si tiene cargado el producto delivery y el canal no es delivery
    if ($canal != 3) {
        if ($rsco->fields['permite_delivery_sinitem'] != 'S') {
            if (intval($total_deliv) > 0) {
                $errores .= "- Debe tomar los datos del delivery cuando se agrega este producto.<br />";
                $valido = "N";
            }
        }
    }

    if ($valido == 'S') {
        $esdelivery = str_replace("'", "", $delivery);
        if (intval($idzona) > 0) {
            $esdelivery = "S";
        }
        // si no es delivery
        if ($esdelivery != 'S') {
            // si se envio la sucursal del cliente
            if (intval($idsucursal_clie) > 0) {
                $consulta = "
				select direccion, telefono
				from sucursal_cliente 
				where 
				idsucursal_clie = $idsucursal_clie
				";
                $rssuccli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $direccion = antisqlinyeccion($rssuccli->fields['direccion'], "text");
                $telefono = antisqlinyeccion($rssuccli->fields['telefono'], "int");
                // si no envio busca cual es
            } else {
                $direccion = antisqlinyeccion($direccion, "text");
                $telefono = antisqlinyeccion($telefono, "int");
                $consulta = "
				select idsucursal_clie 
				from sucursal_cliente 
				where 
				idcliente = $idcliente 
				and estado = 1 
				order by idsucursal_clie asc limit 1
				";
                $rssuccli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idsucursal_clie = antisqlinyeccion($rssuccli->fields['idsucursal_clie'], "int");
            }
        } else {
            $direccion = antisqlinyeccion($direccion, "text");
            $telefono = antisqlinyeccion($telefono, "int");
        }


        // insertar cabecera temporal
        $consulta = "
		INSERT INTO tmp_ventares_cab
		(razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, idempresa,idcanal,delivery,idmesa,clase,tipoventa,delivery_zona,delivery_costo,nombre_deliv,apellido_deliv,idclientedel,iddomicilio,idmotoristaped,idcanalventa,direccion,telefono,idterminal) 
		VALUES 
		('$razon_social', '$ruc', $chapa, $observacion, $montototal, $idusu, '$ahora', $idsucursal, $idempresa,$canal,$delivery,$idmesa,1,$tipoventa,$idzona,$delivery_costo,$nombre_deliv,$apellido_deliv,$idclientedel,$iddomicilio,$idmotorista,$idcanalventa,$direccion,$telefono,$idterminal_usu)
		";
        //echo $consulta;
        //exit;
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

        //datos delivery
        if (intval($idzona) > 0) {



            $llevapos = intval($_POST['llevapos']);
            if ($llevapos == 0 || $llevapos == 1) {
                $llevapos = 'N';
            } else {

                $llevapos = 'S';
            }
            $cambio = floatval($_POST['cambiode']);
            $observadelivery = str_replace("'", "", $_POST['observadelivery']);
            $observacion_delivery = antisqlinyeccion($observadelivery, 'text');

            $direccion = str_replace("'", "", $direccion);
            $direccion = str_replace("NULL", "", $direccion);
            $telefono = str_replace("'", "", $telefono);
            $telefono = str_replace("NULL", "", $telefono);
            $direccion = antisqlinyeccion($direccion, "text");
            $telefono = antisqlinyeccion($telefono, "int");
            $consulta = "
			update tmp_ventares_cab
			set
			telefono=$telefono,
			delivery_zona=$idzona,
			direccion=$direccion,
			llevapos='$llevapos',
			cambio=$cambio,
			observacion_delivery=$observacion_delivery

			where
			idusu = $idusu
			and idempresa = $idempresa
			and idtmpventares_cab = $idtmpventares_cab
			";
            //echo $consulta;
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        $setadd = "";
        if ($ventacaj_impcocina == 'S') {
            $setadd = ", impreso_coc = 'S' ";
        }

        // marcar como finalizado
        $consulta = "
		update tmp_ventares
		set 
		finalizado = 'S',
		idtmpventares_cab = $idtmpventares_cab
		$setadd
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
		finalizado = 'S'
		where
		idtmpventares_cab = $idtmpventares_cab
		and idsucursal = $idsucursal
		and idempresa = $idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // asigna pedido para la venta mas abajo
        $pedido = $idtmpventares_cab;

        /*}else{
            echo $errores;
            exit;*/
    }

}

////////////////////////////////// PEDIDOS //////////////////////////////////
//Ya con el ID de cabecera, antes de generar la venta en si, le convertimos si es diplomatico el cliente

$buscar = "Select diplomatico, carnet_diplomatico from cliente where idcliente=$idcliente";
$rsbb1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$dip = trim($rsbb1->fields['diplomatico']);

if ($dip == 'S') {
    // construye para enviar a la funcion
    $parametros_array = [
        'idatc' => $idatc,
        'idtmpventares_cab' => $pedido,
        'diplo' => $dip,
        'idempresa' => $idempresa,
        'idsucursal' => $idsucursal

    ];

    // envia a la funcion
    $res = diplomatico_preticket($parametros_array);
    $diplomatico = "'S'";
    $carnet_diplomatico = antisqlinyeccion($rsbb1->fields['carnet_diplomatico'], "text");

    // monto total de productos en carrito
    if ($pedido > 0 && $idatc == 0) {
        $consulta = "
		select sum(subtotal) as total_monto
		from tmp_ventares 
		inner join productos on tmp_ventares.idproducto = productos.idprod_serial
		where 
		tmp_ventares.idtmpventares_cab = $pedido
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $totalcobrar = intval($rs->fields['total_monto']);
    }

} else {
    $diplomatico = "'N'";
    $carnet_diplomatico = antisqlinyeccion('', "text");
}




////////////////////////////////// REGISTRAR VENTA //////////////////////////////////
if (($pedido > 0 or $idmesa > 0)) {



    //Cabecera temporal
    if (intval($idmesa) == 0) {
        $buscar = "SELECT * FROM tmp_ventares_cab where idtmpventares_cab=$pedido and registrado='N' and idsucursal = $idsucursal";
        $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idcanal = intval($rscab->fields['idcanal']);
        $idmesatmp = intval($rscab->fields['idmesa_tmp']);
        $idwebpedido = intval($rscab->fields['idwebpedido']);
        $ideventoped = intval($rscab->fields['ideventoped']);
        if ($idwebpedido > 0) {
            $_SESSION['idwebpedido'] = $idwebpedido;
        }
    } else {
        $buscar = "SELECT sum(monto) as monto FROM tmp_ventares_cab where idmesa=$idmesa and finalizado = 'S' and registrado = 'N' and estado = 1
		and idsucursal = $idsucursal";
        $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    }
    // cabecera para datos extras del primer pedido si hay mas de uno
    if (intval($idmesa) == 0) {
        $buscar = "
		SELECT * 
		FROM tmp_ventares_cab 
		where 
		idtmpventares_cab=$pedido
		and finalizado = 'S' 
		and registrado='N'
		and estado = 1
		and idempresa = $idempresa 
		and idsucursal = $idsucursal
		";
        $rscabpri = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    } else {
        $buscar = "
		SELECT *
		FROM tmp_ventares_cab 
		where 
		idmesa=$idmesa 
		and finalizado = 'S' 
		and registrado = 'N' 
		and estado = 1 
		and idempresa = $idempresa 
		and idsucursal = $idsucursal
		order by idtmpventares_cab asc
		limit 1
		";
        $rscabpri = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    }
    // valida que no se haya registrado antes para evitar duplicidad
    if (intval($rscabpri->fields['idtmpventares_cab']) == 0) {
        $errores .= "- El pedido que intentas registrar ya fue registrado anteriormente.";
        $valido = "N";
    }
    $idsolicitudcab = $rscabpri->fields['idsolicitudcab'];
    $idapp = $rscabpri->fields['idapp'];
    $operador_pedido = intval($rscabpri->fields['idusu']);
    $totalvendido = floatval($rscab->fields['monto']);
    $totalvendido_deldesc = floatval($rscab->fields['monto']) + floatval($rscab->fields['delivery_costo']) - $descuento;
    $iva10 = (floatval($totalvendido_deldesc) / 11);
    $tventa5 = 0;
    $iva5 = 0;
    $tventaex = 0;


    $whereadd_depo = "";
    // si el descuento stock es producto sucursal
    if ($idtipodesc_depo == 2) {
        $whereadd_depo = "
		(select iddeposito from producto_deposito where idsucursal = $idsucursal and idproducto = tmp_ventares.idproducto ) as iddeposito_det,
		";
    }
    // si el descuento stock es producto terminal
    if ($idtipodesc_depo == 3) {
        $idterminal_pc = intval($idterminal_pc);
        $whereadd_depo = "
		(select iddeposito from producto_deposito_terminal where idterminal = $idterminal_pc and idproducto = tmp_ventares.idproducto ) as iddeposito_det,
		";
    }

    //cuerpo
    if ($idmesa == 0) {
        $consulta_det_ped = "
		SELECT * ,
		$whereadd_depo
		(select idinsumo from insumos_lista where idproducto = tmp_ventares.idproducto) as idinsumo
		
		FROM tmp_ventares
		 where 
		idtmpventares_cab=$pedido 
		and borrado='N'
		and cantidad > 0
		and idtmpventares_cab in 
						(
						SELECT idtmpventares_cab 
						FROM tmp_ventares_cab 
						where 
						idtmpventares_cab=$pedido  
						and finalizado = 'S' 
						and registrado = 'N' 
						and estado = 1
						and idsucursal = $idsucursal
						)
		 ";
        $rsdet = $conexion->Execute($consulta_det_ped) or die(errorpg($conexion, $consulta_det_ped));
        // el where debe ser igual al de arriba
        $consulta_mayor = "
		select idventatmp, subtotal
		FROM tmp_ventares
		where 
		idtmpventares_cab=$pedido 
		and borrado='N'
		and cantidad > 0
		and idtmpventares_cab in 
						(
						SELECT idtmpventares_cab 
						FROM tmp_ventares_cab 
						where 
						idtmpventares_cab=$pedido  
						and finalizado = 'S' 
						and registrado = 'N' 
						and estado = 1
						and idsucursal = $idsucursal
						)
		order by subtotal desc
		limit 1
		";


    } else {
        $consulta_det_ped = "
		SELECT * ,
		$whereadd_depo
		(select idinsumo from insumos_lista where idproducto = tmp_ventares.idproducto) as idinsumo
		
		FROM tmp_ventares 
		where  
		borrado='N' 
		and cantidad > 0
		and idtmpventares_cab in 
							(
							SELECT idtmpventares_cab 
							FROM tmp_ventares_cab 
							where 
							idmesa=$idmesa 
							and finalizado = 'S' 
							and registrado = 'N' 
							and estado = 1
							and idsucursal = $idsucursal
							)
		";
        $rsdet = $conexion->Execute($consulta_det_ped) or die(errorpg($conexion, $consulta_det_ped));
        // el where debe ser igual al de arriba
        $consulta_mayor = "
		select idventatmp, subtotal
		FROM tmp_ventares
		where  
		borrado='N' 
		and cantidad > 0
		and idtmpventares_cab in 
							(
							SELECT idtmpventares_cab 
							FROM tmp_ventares_cab 
							where 
							idmesa=$idmesa 
							and finalizado = 'S' 
							and registrado = 'N' 
							and estado = 1
							and idsucursal = $idsucursal
							)
		order by subtotal desc
		limit 1
		";
    }


    // si esta activado el bloqueo de stock (parche frutaleza)
    if ($ventas_nostock == 2) {
        // recorre y genera todos los productos sin stock
        while (!$rsdet->EOF) {
            $idprod_dep = intval($rsdet->fields['idinsumo']);
            $idprod_csv = $idprod_dep.',';
            $rsdet->MoveNext();
        }
        $rsdet->MoveFirst();
        $idprod_csv = substr(trim($idprod_csv), 0, -1);

        $consulta = "
		select idproducto 
		from  gest_depositos_stock_gral
		WHERE
		disponible <= 0
		and idproducto in ($idprod_csv)
		and iddeposito = $iddeposito
		and (select insumos_lista.hab_invent from insumos_lista where insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto)  = 1
		limit  1
		";
        //echo $consulta;exit;
        $rsst = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsst->fields['idproducto']) > 0) {
            $valido = 'N';
            $errores .= "- Existen productos sin stock, no se puede procesar esta venta.";

        }


    }
    //exit;



    if ($valido == 'S') {

        // prorratear descuento si es facturador electronico
        if ($facturador_electronico == 'S') {
            if ($descuento > 0) {
                $total_venta_sindesc = $totalcobrar + $descuento;
                $descuento_porc_prorat = ($descuento / $total_venta_sindesc) * 100;
                $total_descontado = $totalcobrar;
                //echo 'a'.$totalcobrar;exit;
                $subtotal_new_acum = 0;
                while (!$rsdet->EOF) {
                    $idventatmp = intval($rsdet->fields['idventatmp']);
                    $subtotal_old = floatval($rsdet->fields['subtotal']);
                    $subtotal_new = round($subtotal_old - (($subtotal_old * $descuento_porc_prorat) / 100), 0);
                    $subtotal_new_acum += $subtotal_new;
                    $consulta = "
						update tmp_ventares 
						set 
						subtotal_orig = $subtotal_old, 
						subtotal = $subtotal_new
						where
						idventatmp = $idventatmp
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                    $rsdet->MoveNext();
                }
                $rsdet->MoveFirst();
                // si la sumatoria de los nuevos subtotales es menor al total de la factura ya descontado
                if ($subtotal_new_acum < $total_descontado) {
                    $diferencia = $total_descontado - $subtotal_new_acum;
                    // busca el producto mas caro
                    $rsmaxtmp = $conexion->Execute($consulta_mayor) or die(errorpg($conexion, $consulta_mayor));
                    $idventatmp_mascaro = $rsmaxtmp->fields['idventatmp'];
                    $subtotal_mascaro = $rsmaxtmp->fields['subtotal'];
                    $subtotal_ajustado = $subtotal_mascaro + $diferencia;
                    $consulta = "
						update tmp_ventares set subtotal=$subtotal_ajustado where idventatmp = $idventatmp
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                }
                // si la sumatoria de los nuevos subtotales es mayor al total de la factura ya descontado
                if ($subtotal_new_acum > $total_descontado) {
                    $diferencia = $subtotal_new_acum - $total_descontado;
                    // busca el producto mas caro
                    $rsmaxtmp = $conexion->Execute($consulta_mayor) or die(errorpg($conexion, $consulta_mayor));
                    $idventatmp_mascaro = $rsmaxtmp->fields['idventatmp'];
                    $subtotal_mascaro = $rsmaxtmp->fields['subtotal'];
                    $subtotal_ajustado = $subtotal_mascaro - $diferencia;
                    $consulta = "
						update tmp_ventares set subtotal=$subtotal_ajustado where idventatmp = $idventatmp
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
                // volver a ejecutar el det luego de las actualizaciones
                $rsdet = $conexion->Execute($consulta_det_ped) or die(errorpg($conexion, $consulta_det_ped));
            }
        }


        //Track ID
        //$tcid=$idempresa.$idsucursal.$idusu;
        $tcid = $idsucursal.$idusu;
        $v = date('YmdHis');
        $tcid = substr($v.$tcid, 0, 18); //202104011999333

        // datos timbrado
        $idtandatimbrado = antisqlinyeccion($idtandatimbrado, "int");
        $timbrado = antisqlinyeccion($timbrado, "int");
        $factura_suc = antisqlinyeccion($factura_suc, "int");
        $factura_pexp = antisqlinyeccion($factura_pexp, "int");
        $idapp = antisqlinyeccion($idapp, "int");
        $idsalon_usu = antisqlinyeccion($idsalon_usu, "int");

        // campos fijos por ahora
        $id_indicador_presencia = 1; // 1 Operación presencial
        $idtipotranset = 1; // 1 Venta de mercadería
        if (intval($idtipooperacionset) == 0) {
            $idtipooperacionset = 2;
        }
        if ($idadherente > 0 && $tipoventa == 2) {
            $idtipotran = 2; // consumo
        } else {
            $idtipotran = 1; // venta
        }

        /*---------------------VENTAS*------------------------*/
        $insertar = "Insert into ventas
			(fecha,idcliente,tipo_venta,idempresa,sucursal,factura,recibo,ruchacienda,dv,
			total_venta,idtransaccion,trackid,registrado_por,totaliva10,totaliva5,texe,idpedido,otrosgs,descneto,deliv,totalcobrar,tipoimpresion,vendedor,total_cobrado,estado,obs,idmesa,idcanal,idcaja,idzona,operador_pedido,formapago,vuelto,recibido,idadherente,idserviciocom,idclientedel,iddomicilio,
			idtandatimbrado,factura_sucursal,factura_puntoexpedicion,timbrado,ruc,iddeposito,
			diplomatico,carnet_diplomatico,idmotorista,idcanalventa,codpedido_externo,
			idsucursal_clie,idapp, idsalon_asig,ocnumero,
			id_indicador_presencia, idtipotranset, idtipooperacionset, idtipotran
			)
			VALUES
			('$fecha_venta',$idcliente,$tipoventa,$idempresa,$idsucursal,$factura,NULL,$ruc_pri,$ruc_dv,$totalvendido,0,$tcid,$idusu,$iva10,$iva5,$tventaex
			,$pedido,$delivery_costo,$descuento,'',$totalcobrar,1,$idvendedor,$totalcobrar,$esta,$motivo_descuento,$idmesa,$idcanal, 
			$idcaja,$idzona,$operador_pedido,$mediopago,$vuelto,$monto_recibido,$idadherente,$idservcom,$idclientedel,$iddomicilio,
			$idtandatimbrado,$factura_suc,$factura_pexp,$timbrado,'$ruc',$iddeposito,
			$diplomatico,$carnet_diplomatico,$idmotorista,$idcanalventa,$codpedido_externo,
			$idsucursal_clie,$idapp, $idsalon_usu,$numero_orden_compra,
			$id_indicador_presencia, $idtipotranset, $idtipooperacionset, $idtipotran
			)";
        //echo $insertar;exit;
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        //Traemos el id de la venta
        $buscar = "Select max(idventa) as mayor from ventas where idempresa = $idempresa and registrado_por = $idusu";
        $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idventa = intval($rsm->fields['mayor']);

        if (intval($_SESSION['idpresupuesto']) > 0) {
            $idpres = intval($_SESSION['idpresupuesto']);
            //actualizamos con el id de venta
            $update = "Update presupuestos set idventa=$idventa where idunico=$idpres";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            $_SESSION['idpresupuesto'] = '';
            $idpres = 0;
        }






        //Crear cuenta si es credito
        if ($tipoventa == 2) {
            // venta que acaba de insertarse
            if (trim($_POST['primervto']) != '') {
                $primervto = date("Y-m-d", strtotime($_POST['primervto']));
            } else {
                $primervto = $fecha_venta;
            }

            //Generamos la cuenta
            $insertar = "
				Insert into cuentas_clientes 
				(idempresa,sucursal,deuda_global,saldo_activo,idcliente,estado,registrado_el,registrado_por,idventa,
				idadherente,idserviciocom,idsucursal_clie,prox_vencimiento)
				values
				($idempresa,$idsucursal,$totalcobrar,$totalcobrar,$idcliente,1,'$ahora',$idusu,$idventa,
				$idadherente,$idservcom,$idsucursal_clie,'$primervto')
				";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            //Credito
            $buscar = "Select max(idcta) as mayor from cuentas_clientes where  registrado_por = $idusu";
            $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $mayor = intval($rs1->fields['mayor']);

            if (intval($idsolicitudcab) == 0) {


                $insertar = "
					INSERT INTO cuentas_clientes_det
					(idcta, nro_cuota, vencimiento, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, fch_ult_pago, fch_cancela, dias_atraso, dias_pago, dias_comb, estado) 
					select cuentas_clientes.idcta, 1, '$primervto', cuentas_clientes.deuda_global, 0, 0, cuentas_clientes.saldo_activo, NULL, NULL, 0, 0, 0, 1
					from cuentas_clientes
					where 
					estado = 1
					and idcta = $mayor
					";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            } else {
                $insertar = "
					INSERT INTO cuentas_clientes_det
					(
					idcta, nro_cuota, vencimiento, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota,
					fch_ult_pago, fch_cancela, dias_atraso, dias_pago, dias_comb, estado
					 ) 
					select 
					$mayor, nro_cuota, vencimiento, (amortizacion+interes), 0, 0, (amortizacion+interes),
					NULL, NULL, 0, 0, 0, 1
					from cuadro_amortizacion
					where
					nro_soli = $idsolicitudcab
					order by nro_cuota asc
					";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
                $consulta = "
					update cuentas_clientes set prox_vencimiento = '$primervto' where idcta = $mayor
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }

            $consulta = "
				INSERT INTO adherente_estadocuenta 
				( fechahora, fechasola, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim)
				SELECT registrado_el as fechahora, date(registrado_el) as fechasola, 'D' as tipomov, idcliente, idadherente,
				 idserviciocom, deuda_global as monto, idventa, NULL as idpago, idcta, idempresa , NULL as idpagodiscrim
				from cuentas_clientes 
				where 
				idcta = $mayor
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualizar el saldo de sobregiro
            $consulta = "
				update cliente 
				set 
				saldo_sobregiro = $saldosobregiro_posconsumo_tit
				where 
				idcliente = $idcliente 
				and idempresa = $idempresa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            if ($idadherente > 0) {
                // actualizar el saldo de sobregiro
                $consulta = "
					update adherentes 
					set 
					disponible = $saldosobregiro_posconsumo_adh
					where 
					idadherente = $idadherente 
					and idempresa = $idempresa
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                if ($idservcom > 0) {
                    // actualizar el saldo de sobregiro
                    $consulta = "
						update adherentes_servicioscom 
						set 
						disponibleserv = $saldosobregiro_posconsumo_adhscom
						where 
						idadherente = $idadherente 
						and idserviciocom = $idservcom 
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
            }
            // paga las cuentas con los saldos a favor
            //acredita_saldoafavor($idcliente);
            // actualiza los saldos disponibles
            //actualiza_saldos_clientes($idcliente,$idadherente,$idservcom);

            // recorre cada concepto y  actualiza los saldos disponibles
            $buscar = "
				SELECT *
				from adherentes 
				inner join adherentes_servicioscom on adherentes_servicioscom.idadherente=adherentes.idadherente 
				inner join servicio_comida on servicio_comida.idserviciocom=adherentes_servicioscom.idserviciocom
				where
				adherentes.idcliente=$idcliente 
				and adherentes.idempresa=$idempresa
				order by nomape asc";
            $tad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            // actualiza saldos
            while (!$tad->EOF) {
                $idserviciocom = intval($tad->fields['idserviciocom']);
                $idadherente = intval($tad->fields['idadherente']);
                actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);
                $tad->MoveNext();
            }


        }
        // si es al contado
        if ($tipoventa == 1) {
            $insertar = "Insert into gest_pagos
				(idcliente,fecha,medio_pago,total_cobrado,chequenum,banco,numtarjeta,montotarjeta,
				factura,recibo,tickete,ruc,tipo_pago,idempresa,sucursal,efectivo,codtransfer,montotransfer,
				montocheque,cajero,idventa,vueltogs,idpedido,
				delivery,idmesa,idcaja,rendido,tipotarjeta,
				idtipocajamov,tipomovdinero)
				values
				($idcliente,'$ahora',$mediopago,$totalcobrar,$numcheque,$banco,$numtarjeta,$tarjeta,
				$factura,'','','$ruc',$tipoventa,$idempresa,$idsucursal,$efectivo,
				$numerotrans,$montotransferido,$montocheque,$idusu,$idventa,$vuelto,$pedido,
				$delivery_costo,$idmesa,$idcaja,'$rendido',$tipotarjeta,
				1,'E')";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            $consulta = "
				select max(idpago) as idpago from gest_pagos where idventa = $idventa limit 1
				";
            $rsmaxpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idpago_gest = $rsmaxpag->fields['idpago'];
            // pago mixto
            if ($mediopago == 3) {
                $consulta = "
					SELECT carrito_cobros_ventas.*, formas_pago.idformapago_set
					FROM carrito_cobros_ventas 
					inner join formas_pago on formas_pago.idforma = carrito_cobros_ventas.idformapago
					WHERE 
					registrado_por = $idusu
					order by idcarritocobrosventas asc
					";
                $rscarcobven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                while (!$rscarcobven->EOF) {
                    $monto_pago_det = $rscarcobven->fields['monto_forma'];
                    $idformapago = $rscarcobven->fields['idformapago'];
                    $idformapago_set = $rscarcobven->fields['idformapago_set'];
                    $consulta = "
						INSERT INTO gest_pagos_det
						(idpago, monto_pago_det, idformapago) 
						VALUES 
						($idpago_gest, $monto_pago_det, $idformapago)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $consulta = "
						select idpagodet 
						from gest_pagos_det 
						where 
						idpago = $idpago_gest 
						order by idpagodet desc
						limit 1
						";
                    $rspagdetmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $idpagodet = intval($rspagdetmax->fields['idpagodet']);
                    // forma pago set tarjeta credito y debito
                    if ($rscarcobven->fields['idformapago_set'] == 3 or $rscarcobven->fields['idformapago_set'] == 4) {
                        $iddenominaciontarjeta = intval($rscarcobven->fields['iddenominaciontarjeta']);
                        $consulta = " 
							insert into gest_pagos_det_datos
							(idpagodet,id_denominacion_tarjeta)
							values
							($idpagodet,$iddenominaciontarjeta)
							";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    }
                    // cheque
                    if ($rscarcobven->fields['idformapago_set'] == 2) {
                        // numero cheque y banco
                        $idbanco = intval($rscarcobven->fields['idbanco']);
                        $numcheque = antisqlinyeccion(agregacero(substr($rscarcobven->fields['cheque_numero'], 0, 8), 8), "text");
                        $consulta = " 
							insert into gest_pagos_det_datos
							(idpagodet,idbanco,cheque_numero)
							values
							($idpagodet,$idbanco,$numcheque)
							";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    }

                    $rscarcobven->MoveNext();
                }
                // pago normal (No mixto)
            } else {

                $consulta = "
					select idformapago_set from formas_pago where idforma = $mediopago
					";
                $rspagset = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $consulta = "
					INSERT INTO gest_pagos_det
					(idpago, monto_pago_det, idformapago) 
					VALUES 
					($idpago_gest, $totalcobrar, $mediopago)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $consulta = "
					select idpagodet 
					from gest_pagos_det 
					where 
					idpago = $idpago_gest 
					order by idpagodet desc
					limit 1
					";
                $rspagdetmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idpagodet = intval($rspagdetmax->fields['idpagodet']);


                // forma pago set tarjeta credito y debito
                if ($rspagset->fields['idformapago_set'] == 3 or $rspagset->fields['idformapago_set'] == 4) {
                    $iddenominaciontarjeta = intval($_POST['iddenominaciontarjeta']);
                    $consulta = " 
						insert into gest_pagos_det_datos
						(idpagodet,id_denominacion_tarjeta)
						values
						($idpagodet,$iddenominaciontarjeta)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
                // cheque
                if ($rspagset->fields['idformapago_set'] == 2) {
                    // numero cheque y banco
                    $idbanco = intval($_POST['banco']);
                    $numcheque = antisqlinyeccion(agregacero(substr($_POST['adicional'], 0, 8), 8), "text");
                    $consulta = " 
						insert into gest_pagos_det_datos
						(idpagodet,idbanco,cheque_numero,id_denominacion_tarjeta,id_forma_procesamiento_pago)
						values
						($idpagodet,$idbanco,$numcheque,NULL,NULL)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                }


            }

            // actualizar cabecera
            /* masivo
            update gest_pagos
            set
            efectivo = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and gest_pagos_det.idformapago = 1),0),
            montotarjeta = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and (gest_pagos_det.idformapago = 2 or idformapago = 4)),0),
            montotransfer = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and gest_pagos_det.idformapago = 6),0),
            montocheque = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and gest_pagos_det.idformapago = 5),0)
            where
            idpago in (
            select gest_pagos_det.idpago from gest_pagos_det
            )
            */
            $consulta = "
				update gest_pagos 
				set 
				efectivo = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and gest_pagos_det.idformapago = 1),0),
				montotarjeta = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and (gest_pagos_det.idformapago = 2 or idformapago = 4)),0),
				montotransfer = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and gest_pagos_det.idformapago = 6),0),
				montocheque = COALESCE((select sum(gest_pagos_det.monto_pago_det) from gest_pagos_det where idpago = gest_pagos.idpago and gest_pagos_det.idformapago = 5),0)
				where
				idpago = $idpago_gest
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }


        //Detalles de la venta
        while (!$rsdet->EOF) {
            $idprod = trim($rsdet->fields['idproducto']);
            $buscar = "Select idprod,tipoiva,idmedida,idtipoiva from productos where idprod_serial=$idprod";
            $rsfg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $codigoproducto = antisqlinyeccion($rsfg->fields['idprod'], 'text');
            $tipoiva = intval($rsfg->fields['tipoiva']); // alicuota
            $idtipoiva = intval($rsfg->fields['idtipoiva']);
            $idmedida = intval($rsfg->fields['idmedida']);
            //CANTIDAD DE PRODUCTO FINAL VENDIDO
            $cantidad = floatval($rsdet->fields['cantidad']);
            //PRECIO DE VENTA DE PRODUCTO FINAL VENDIDO
            $precioventa = floatval($rsdet->fields['precio']);
            $subtotal = floatval($rsdet->fields['subtotal']);
            $idventatmp = intval($rsdet->fields['idventatmp']);
            $idprod1 = antisqlinyeccion($rsdet->fields['idprod_mitad1'], 'int');
            $idprod2 = antisqlinyeccion($rsdet->fields['idprod_mitad2'], 'int');
            $combo = antisqlinyeccion($rsdet->fields['combo'], 'text');
            $combo_es = $rsdet->fields['combo'];
            $combinado = antisqlinyeccion($rsdet->fields['combinado'], 'text');
            $combinado_es = $rsdet->fields['combinado'];
            $idtipoproducto = $rsdet->fields['idtipoproducto'];
            $descuento = floatval($rsdet->fields['descuento']);
            $utilidadprox = 0;
            $disponible = 0;
            $menor = 0;
            $costobase = 0;
            $idlistaprecio = antisqlinyeccion($rsdet->fields['idlistaprecio'], 'int');
            $iddeposito_det = intval($rsdet->fields['iddeposito_det']);

            // si el producto no tiene asignado un deposito de descuento de stock, descuenta del deposito por defecto
            if ($iddeposito_det == 0) {
                $iddeposito_det = $iddeposito;
            }

            /*if($idtipoproducto == 2){
                $combo_es="S";
            }*/

            // calcular base imponible
            if ($dip != 'S') {
                if (intval($idtipoiva) == 0) {
                    $idtipoiva = 1;
                }
                $consulta = "
					select idtipoiva, iva_porc, iva_describe, iguala_compra_venta, estado, hab_compra, hab_venta,
					id_forma_afectacion_tributaria_iva
					from tipo_iva 
					where
					idtipoiva = $idtipoiva
					";
                $rsbimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $iva_porc = floatval($rsbimp->fields['iva_porc']); // alicuota
                $subtotal_monto_iva = calcular_iva($iva_porc, $subtotal);
                $base_imponible = $subtotal - $subtotal_monto_iva;
                $id_forma_afectacion_tributaria_iva = intval($rsbimp->fields['id_forma_afectacion_tributaria_iva']);
            } else {
                $iva_porc = 0; // alicuota
                $subtotal_monto_iva = calcular_iva($iva_porc, $subtotal);
                $base_imponible = $subtotal - $subtotal_monto_iva;
                $tipoiva = 0;
                $idtipoiva = 3;
                $id_forma_afectacion_tributaria_iva = 2; // 2 exonerado tabla forma_afectacion_tributaria_iva
            }

            if ($idtipoproducto == 7) {
                $pchar = antisqlinyeccion(substr($rsdet->fields['observacion'], 0, 150), "text");
            } else {
                $pchar = "NULL";
            }



            //Manejo en Base a receta: el id de producto en la tabla de costo individual, es el que se debe usar .
            //Insertamos en Venta Detalle Como el manej es x receta, no hacer caso de las  columnas disponibles ni menor
            $insertar = "Insert into ventas_detalles 
				(idventatmp,cantidad,pventa,subtotal,idventa,idemp,sucursal,idprod,costo,utilidad,iva,registrado_el,descuento,
				idprod_mitad1,idprod_mitad2,existencia,serialcostos,
				base_imponible,idtipoiva,subtotal_monto_iva,pchar,idlistaprecio,iddeposito_det,
				id_forma_afectacion_tributaria_iva)
				values
				($idventatmp,$cantidad,$precioventa,$subtotal,$idventa,$idempresa,$idsucursal,'$idprod',$costobase,
				$utilidadprox,$tipoiva,'$ahora',$descuento,$idprod1,$idprod2,$disponible,$menor,
				$base_imponible,$idtipoiva,$subtotal_monto_iva,$pchar,$idlistaprecio,$iddeposito_det,
				$id_forma_afectacion_tributaria_iva)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


            //traemos el id de ventas detalles insertado para usar en ventas_detalles de la receta
            $buscar = "Select  max(idventadet) as mayor from ventas_detalles where idventa=$idventa and idprod='$idprod'";
            $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $mayorid = intval($rsmayor->fields['mayor']);
            $mi = $mayorid;
            $idventadet = $mayorid;

            $parametros_array = [
                'idtipoiva' => $idtipoiva,
                'monto_ivaincluido' => $subtotal
            ];
            $res_iva = calcular_iva_tipos($parametros_array);
            foreach ($res_iva as $iva_linea) {
                //print_r($iva_linea);exit;
                $gravadoml = $iva_linea['gravadoml'];
                $ivaml = $iva_linea['ivaml'];
                $exento = $iva_linea['exento'];
                $iva_porc_col = $iva_linea['iva_porc_col'];
                $monto_col = $iva_linea['monto_col'];

                //ventas_detalles_impuesto
                $consulta = "
					INSERT INTO ventas_detalles_impuesto
					(idventadet, idventa, idproducto, idtipoiva, iva_porc_col, monto_col,
					gravadoml,  ivaml,  exento) 
					VALUES 
					($idventadet, $idventa, $idprod, $idtipoiva, $iva_porc_col, $monto_col,
					$gravadoml,  $ivaml,  '$exento'
					)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }


            /*$consulta="
            SELECT
            idtipoiva, iva_porc, monto_porc, exento
            FROM tipo_iva_detalle
            WHERE
            idtipoiva = $idtipoiva
            ";
            $rsivadet=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            while(!$rsivadet->EOF){

                $gravadoml=$base_imponible*($rsivadet->fields['monto_porc']/100);
                $ivaml=$gravadoml*($rsivadet->fields['iva_porc']/100);
                $exento=$rsivadet->fields['exento'];
                $iva_porc_col=$rsivadet->fields['iva_porc'];
                $monto_col=$gravadoml+$ivaml;

                //ventas_detalles_impuesto
                $consulta="
                INSERT INTO ventas_detalles_impuesto
                (idventadet, idventa, idproducto, idtipoiva, iva_porc_col, monto_col,
                gravadoml,  ivaml,  exento)
                VALUES
                ($idventadet, $idventa, $idprod, $idtipoiva, $iva_porc_col, $monto_col,
                $gravadoml,  $ivaml,  '$exento'
                )
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

            $rsivadet->MoveNext(); }*/



            //Paso 1): Traer la receta del producto
            $buscar = "Select * from recetas where idproducto='$idprod' and recetas.idempresa = $idempresa";

            $rsre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idreceta = intval($rsre->fields['idreceta']);
            // si tiene receta
            if ($idreceta > 0) {
                //Lista de componenentes de la receta
                $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva,
					insumos_lista.mueve_stock as mueve_stock_ins
					 from recetas_detalles 
					 inner join ingredientes on ingredientes.idingrediente=recetas_detalles.ingrediente
					 inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
					 where 
					 recetas_detalles.idprod=$idprod
					
					 and recetas_detalles.idreceta=$idreceta
					 and recetas_detalles.idempresa = $idempresa 
					 and insumos_lista.idempresa = $idempresa
					 and ingredientes.idingrediente not in (
					 										select tmp_ventares_sacado.idingrediente 
															from tmp_ventares_sacado
															where
															tmp_ventares_sacado.idventatmp = $idventatmp
															and tmp_ventares_sacado.idproducto = $idprod
															)
					 ";
                $rsrecu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                while (!$rsrecu->EOF) {


                    $ir = intval($rsrecu->fields['idreceta']);
                    $in = intval($rsrecu->fields['ingrediente']);
                    $pp = intval($rsrecu->fields['idprod']);
                    $insu = intval($rsrecu->fields['idinsumo']);
                    $idmedida_med = $rsrecu->fields['idmedida'];
                    $mueve_stock_ins = trim($rsrecu->fields['mueve_stock_ins']);
                    /*if ($idmedida_med==4){
                        //es unitario, tomamos la cantidad de la receta
                        $cant=floatval($rsrecu->fields['cantidad'])*floatval($cantidad);
                    } else {
                        //cantidad vendida
                        $cant=floatval($cantidad);
                    }*/
                    if (floatval($rsrecu->fields['cantidad']) > 0) {
                        // cantidad receta por cantidad vendida
                        $cant = floatval($rsrecu->fields['cantidad']) * floatval($cantidad);
                    } else {
                        // cantidad receta es cero entonces cantidad vendida
                        $cant = floatval($cantidad);
                    }

                    // descuenta stock general
                    if ($mueve_stock != 'N' && $mueve_stock_ins != 'N') {
                        descontar_stock_general($insu, $cant, $iddeposito_det);
                        $costo_insumo_utilizado = descuenta_stock_vent($insu, $cant, $iddeposito_det);
                        movimientos_stock($insu, $cant, $iddeposito_det, 2, '-', $idventa, $ahora);
                        // costo unitario promedio de los insumos utilizados
                        $costo_insumo_unitario = round($costo_insumo_utilizado / $cant, 4);
                        // costo del insumo actual utilizado
                        $costo_acum_insu[$insu] = $costo_insumo_utilizado;
                        // costo acumulado de todos los insumos
                        $costo_acumulado += $costo_insumo_utilizado;
                    } else {
                        $costo_insumo_utilizado = 0;
                        //$costo_acum_insu[$insu]=$costo_insumo_utilizado;
                        $costo_insumo_unitario = 0;
                        $costo_acumulado = 0;
                    }
                    $costo_insumo_unitario = floatval($costo_insumo_unitario);
                    $costo_insumo_utilizado = floatval($costo_insumo_utilizado);

                    $insertar = "Insert into venta_receta
						(idventa,idventadet,idprod,idingrediente,idinsumo,cantidad,idmedida,costo,costo_unitario,fechahora)
						values
						($idventa,$idventadet,'$pp',$in,$insu,$cant,$idmedida_med,$costo_insumo_utilizado,$costo_insumo_unitario,'$ahora')";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



                    $rsrecu->MoveNext();
                }
                //Por ultimo actualizamos el costo de la receta //
                /*$buscar="Select sum(costo)/sum(cantidad) as costog from venta_receta where idprod='$pp' and idventa=$idventa";
                $rscostoglobal=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
                $costof=floatval($rscostoglobal->fields['costog']);
                $utilidadpp=$precioventa-$costof;
                $update="Update ventas_detalles set costo=$costof,utilidad=$utilidadpp where idventa=$idventa and
                idprod='$pp' and idventadet=$mi";
                $conexion->Execute($update) or die(errorpg($conexion,$update));
                $update="update  ventas_detalles
                set subtotal_costo = costo*cantidad
                where
                cantidad > 0
                and idventa=$idventa
                and idprod='$pp' and idventadet=$mi";
                $conexion->Execute($update) or die(errorpg($conexion,$update));*/
                $consulta = "
					update ventas_detalles 
					set 
					subtotal_costo = COALESCE((
									select sum(costo) 
									from venta_receta 
									where 
									idventadet = ventas_detalles.idventadet
									),0)
					WHERE
					idventadet = $idventadet
					and idventa = $idventa
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $consulta = "
					update ventas_detalles 
					set 
					costo = COALESCE(subtotal_costo/cantidad,0)
					WHERE
					idventadet = $idventadet
					and idventa = $idventa
					and cantidad > 0
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $consulta = "
					update ventas_detalles 
					set 
					utilidad = subtotal-subtotal_costo
					WHERE
					idventadet = $idventadet
					and idventa = $idventa
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
            // si es un combinado
            if ($combinado_es == 'S') {
                if ($idprod1 > 0 && $idprod2 > 0) {
                    $prod_1 = $idprod1;
                    $prod_2 = $idprod2;
                    $produ_comb = $prod_1.','.$prod_2;
                    $produ_comb_ar = explode(",", $produ_comb);
                    $totalmitades = count($produ_comb_ar);
                    $i = 1;
                    foreach ($produ_comb_ar as $productos_ind) {



                        $idprod_combi = $productos_ind;

                        //Paso 1): Traer la receta del producto
                        $buscar = "Select * from recetas where idproducto='$idprod_combi' and recetas.idempresa = $idempresa";
                        $rsre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                        $idreceta = intval($rsre->fields['idreceta']);

                        // si tiene receta
                        if ($idreceta > 0) {

                            //Lista de componenentes de la receta
                            $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva,
								 insumos_lista.mueve_stock as mueve_stock_ins
								 from recetas_detalles 
								 inner join ingredientes on ingredientes.idingrediente=recetas_detalles.ingrediente
								 inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
								 where 
								 recetas_detalles.idprod=$idprod_combi
								 and recetas_detalles.idreceta=$idreceta
								 and recetas_detalles.idempresa = $idempresa 
								 and insumos_lista.idempresa = $idempresa
								 and ingredientes.idingrediente not in (
																		select tmp_ventares_sacado.idingrediente 
																		from tmp_ventares_sacado
																		where
																		tmp_ventares_sacado.idventatmp = $idventatmp
																		and tmp_ventares_sacado.idproducto = $idprod_combi
																		)
								 ";
                            $rsrecu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));





                            //Mayor ID serial detallep producto
                            $buscar = "Select  max(idventadet) as mayor from ventas_detalles where idventa=$idventa and idprod='$idprod_combi'";
                            $rsmayorid = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                            $mi = intval($rsmayorid->fields['mayor']);
                            while (!$rsrecu->EOF) {



                                $ir = intval($rsrecu->fields['idreceta']);
                                $in = intval($rsrecu->fields['ingrediente']);
                                $pp = intval($rsrecu->fields['idprod']);
                                $insu = intval($rsrecu->fields['idinsumo']);
                                $ca = floatval($rsrecu->fields['cantidad'] / $totalmitades);
                                $co = floatval($rsrecu->fields['costo']);
                                $med = intval($rsrecu->fields['idmedida']);
                                $va = intval($rsrecu->fields['tipoiva']);
                                $mueve_stock_ins = trim($rsrecu->fields['mueve_stock_ins']);

                                // descuenta stock general
                                if ($mueve_stock != 'N' && $mueve_stock_ins != 'N') {
                                    descontar_stock_general($insu, $ca, $iddeposito_det);
                                    $costo_insumo_utilizado = descuenta_stock_vent($insu, $ca, $iddeposito_det);
                                    movimientos_stock($insu, $ca, $iddeposito_det, 2, '-', $idventa, $ahora);
                                    // costo unitario promedio de los insumos utilizados
                                    $costo_insumo_unitario = round($costo_insumo_utilizado / $ca, 4);
                                    // costo del insumo actual utilizado
                                    $costo_acum_insu[$insu] = $costo_insumo_utilizado;
                                    // costo acumulado de todos los insumos
                                    $costo_acumulado += $costo_insumo_utilizado;
                                } else {
                                    $costo_insumo_utilizado = 0;
                                    //$costo_acum_insu[$insu]=$costo_insumo_utilizado;
                                    $costo_insumo_unitario = 0;
                                    $costo_acumulado = 0;
                                }

                                $costo_insumo_utilizado = floatval($costo_insumo_utilizado);


                                //------------VENTA RECETA
                                $insertar = "Insert into venta_receta
									(idventa,idventadet,idprod,idingrediente,idinsumo,cantidad,idmedida,costo,fechahora)
									values
									($idventa,$mi,'$pp',$in,$insu,$ca,$med,$costo_insumo_utilizado,'$ahora')";
                                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                                $rsrecu->MoveNext();
                            }


                        }




                        $i++;
                    } //foreach($produ_comb_ar as $productos_ind){

                }
            }

            //si es un combo
            if ($combo_es == 'S') {

                $whereadd_depo_comb = "";
                // si el descuento stock es producto sucursal
                if ($idtipodesc_depo == 2) {
                    $whereadd_depo_comb = "
						,(select iddeposito from producto_deposito where idsucursal = $idsucursal and idproducto = tmp_combos_listas.idproducto ) as iddeposito_det
						";
                }
                // si el descuento stock es producto terminal
                if ($idtipodesc_depo == 3) {
                    $idterminal_pc = intval($idterminal_pc);
                    $whereadd_depo_comb = "
						,(select iddeposito from producto_deposito_terminal where idterminal = $idterminal_pc and idproducto = tmp_combos_listas.idproducto ) as iddeposito_det
						";
                }


                // busca los productos que componen el combo
                $idprod_princ = $idprod;
                $consulta = "
					select *
					$whereadd_depo_comb
					from tmp_combos_listas
					where
					tmp_combos_listas.idventatmp = $idventatmp
					";
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                while (!$rsgrupos->EOF) {

                    $idprod_combo = $rsgrupos->fields['idproducto'];
                    $iddeposito_det = intval($rsgrupos->fields['iddeposito_det']);
                    // si el producto no tiene asignado un deposito de descuento de stock, descuenta del deposito por defecto
                    if ($iddeposito_det == 0) {
                        $iddeposito_det = $iddeposito;
                    }

                    //Paso 1): Traer la receta del producto
                    $buscar = "Select * from recetas where idproducto='$idprod_combo' and recetas.idempresa = $idempresa";
                    $rsre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $idreceta = intval($rsre->fields['idreceta']);

                    // si tiene receta
                    if ($idreceta > 0) {

                        //Lista de componenentes de la receta
                        $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva,
							 insumos_lista.mueve_stock as mueve_stock_ins
							 from recetas_detalles 
							 inner join ingredientes on ingredientes.idingrediente=recetas_detalles.ingrediente
							 inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
							 where 
							 recetas_detalles.idprod=$idprod_combo
							 and recetas_detalles.idreceta=$idreceta
							 and recetas_detalles.idempresa = $idempresa 
							 and insumos_lista.idempresa = $idempresa
							 and ingredientes.idingrediente not in (
																	select tmp_ventares_sacado.idingrediente 
																	from tmp_ventares_sacado
																	where
																	tmp_ventares_sacado.idventatmp = $idventatmp
																	and tmp_ventares_sacado.idproducto = $idprod_combo
																	)
							 ";
                        $rsrecu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                        //Mayor ID serial detallep producto
                        /*$buscar="Select  max(idventadet) as mayor from ventas_detalles where idventa=$idventa and idprod='$idprod_combo'";
                        $rsmayorid=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));*/

                        $mi = intval($rsmayorid->fields['mayor']);
                        $mi = $idventadet;
                        while (!$rsrecu->EOF) {


                            $ir = intval($rsrecu->fields['idreceta']);
                            $in = intval($rsrecu->fields['ingrediente']);
                            $pp = intval($rsrecu->fields['idprod']);
                            $insu = intval($rsrecu->fields['idinsumo']);
                            $ca = floatval($rsrecu->fields['cantidad']);
                            $co = floatval($rsrecu->fields['costo']);
                            $med = intval($rsrecu->fields['idmedida']);
                            $va = intval($rsrecu->fields['tipoiva']);
                            $mueve_stock_ins = trim($rsrecu->fields['mueve_stock_ins']);

                            // descuenta stock general
                            if ($mueve_stock != 'N' && $mueve_stock_ins != 'N') {
                                descontar_stock_general($insu, $ca, $iddeposito_det);
                                $costo_insumo_utilizado = descuenta_stock_vent($insu, $ca, $iddeposito_det);
                                movimientos_stock($insu, $ca, $iddeposito_det, 2, '-', $idventa, $ahora);
                                // costo unitario promedio de los insumos utilizados
                                $costo_insumo_unitario = round($costo_insumo_utilizado / $ca, 4);
                                // costo del insumo actual utilizado
                                $costo_acum_insu[$insu] = $costo_insumo_utilizado;
                                // costo acumulado de todos los insumos
                                $costo_acumulado += $costo_insumo_utilizado;
                            } else {
                                $costo_insumo_utilizado = 0;
                                //$costo_acum_insu[$insu]=$costo_insumo_utilizado;
                                $costo_insumo_unitario = 0;
                                $costo_acumulado = 0;
                            }
                            $costo_insumo_utilizado = floatval($costo_insumo_utilizado);
                            //------------VENTA RECETA
                            $insertar = "Insert into venta_receta
								(idventa,idventadet,idprod,idingrediente,idinsumo,cantidad,idmedida,costo,fechahora)
								values
								($idventa,$mi,'$pp',$in,$insu,$ca,$med,$costo_insumo_utilizado,'$ahora')";
                            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                            $rsrecu->MoveNext();
                        }


                    }

                    $rsgrupos->MoveNext();
                }

            }
            // si es combinado extendido (logica parecida a combo)
            if ($idtipoproducto == 4) {

                $whereadd_depo_combi = "";
                // si el descuento stock es producto sucursal
                if ($idtipodesc_depo == 2) {
                    $whereadd_depo_combi = "
						,(select iddeposito from producto_deposito where idsucursal = $idsucursal and idproducto = tmp_combinado_listas.idproducto_partes ) as iddeposito_det
						";
                }
                // si el descuento stock es producto terminal
                if ($idtipodesc_depo == 3) {
                    $idterminal_pc = intval($idterminal_pc);
                    $whereadd_depo_combi = "
						,(select iddeposito from producto_deposito_terminal where idterminal = $idterminal_pc and idproducto = tmp_combinado_listas.idproducto_partes ) as iddeposito_det
						";
                }

                // busca los productos que componen el combinado
                $consulta = "
					select * 
					$whereadd_depo_combi
					from tmp_combinado_listas
					where
					tmp_combinado_listas.idventatmp = $idventatmp
					";
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $totalmitades_ex = intval($rsgrupos->recordCount());
                // recorre cada producto
                while (!$rsgrupos->EOF) {

                    $idproducto_partes = $rsgrupos->fields['idproducto_partes'];
                    $idprod_combo = $idproducto_partes;
                    $iddeposito_det = intval($rsgrupos->fields['iddeposito_det']);
                    if ($iddeposito_det == 0) {
                        $iddeposito_det = $iddeposito;
                    }


                    //Paso 1): Traer la receta del producto
                    $buscar = "Select * from recetas where idproducto='$idproducto_partes' and recetas.idempresa = $idempresa";
                    $rsre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $idreceta = intval($rsre->fields['idreceta']);

                    // si tiene receta
                    if ($idreceta > 0) {

                        //Lista de componenentes de la receta
                        $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva,
							insumos_lista.mueve_stock as mueve_stock_ins
							 from recetas_detalles 
							 inner join ingredientes on ingredientes.idingrediente=recetas_detalles.ingrediente
							 inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
							 where 
							 recetas_detalles.idprod=$idproducto_partes
							 and recetas_detalles.idreceta=$idreceta
							 and recetas_detalles.idempresa = $idempresa 
							 and insumos_lista.idempresa = $idempresa
							 and ingredientes.idingrediente not in (
																	select tmp_ventares_sacado.idingrediente 
																	from tmp_ventares_sacado
																	where
																	tmp_ventares_sacado.idventatmp = $idventatmp
																	and tmp_ventares_sacado.idproducto = $idproducto_partes
																	)
							 ";
                        $rsrecu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                        //Mayor ID serial detallep producto
                        $buscar = "Select  max(idventadet) as mayor from ventas_detalles where idventa=$idventa and idprod='$idproducto_partes'";
                        $rsmayorid = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                        $mi = intval($rsmayorid->fields['mayor']);
                        while (!$rsrecu->EOF) {


                            $ir = intval($rsrecu->fields['idreceta']);
                            $in = intval($rsrecu->fields['ingrediente']);
                            $pp = intval($rsrecu->fields['idprod']);
                            $insu = intval($rsrecu->fields['idinsumo']);
                            $ca = floatval($rsrecu->fields['cantidad'] / $totalmitades_ex);
                            $co = floatval($rsrecu->fields['costo']);
                            $med = intval($rsrecu->fields['idmedida']);
                            $va = intval($rsrecu->fields['tipoiva']);
                            $mueve_stock_ins = trim($rsrecu->fields['mueve_stock_ins']);

                            // descuenta stock general
                            if ($mueve_stock != 'N' && $mueve_stock_ins != 'N') {
                                descontar_stock_general($insu, $ca, $iddeposito_det);
                                $costo_insumo_utilizado = descuenta_stock_vent($insu, $ca, $iddeposito_det);
                                movimientos_stock($insu, $ca, $iddeposito_det, 2, '-', $idventa, $ahora);
                                // costo unitario promedio de los insumos utilizados
                                $costo_insumo_unitario = round($costo_insumo_utilizado / $ca, 4);
                                // costo del insumo actual utilizado
                                $costo_acum_insu[$insu] = $costo_insumo_utilizado;
                                // costo acumulado de todos los insumos
                                $costo_acumulado += $costo_insumo_utilizado;
                            } else {
                                $costo_insumo_utilizado = 0;
                                //$costo_acum_insu[$insu]=$costo_insumo_utilizado;
                                $costo_insumo_unitario = 0;
                                $costo_acumulado = 0;
                            }
                            $costo_insumo_utilizado = floatval($costo_insumo_utilizado);
                            //------------VENTA RECETA
                            $insertar = "Insert into venta_receta
								(idventa,idventadet,idprod,idingrediente,idinsumo,cantidad,idmedida,costo,fechahora)
								values
								($idventa,$idventadet,'$pp',$in,$insu,$ca,$med,$costo_insumo_utilizado,'$ahora')";
                            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                            $rsrecu->MoveNext();
                        }


                    }

                    $rsgrupos->MoveNext();
                }


            }


            //Sumar todos los costos de venta receta y updatear el costo global en detalles de la venta y utilidad

            $consulta = "
				update  venta_receta 
				set 
				costo_unitario = costo/cantidad
				where
				idventadet = $idventadet
				and idventa = $idventa
				and cantidad > 0
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
				update ventas_detalles 
				set 
				subtotal_costo = COALESCE((
								select sum(costo) 
								from venta_receta 
								where 
								idventadet = ventas_detalles.idventadet
								),0)
				WHERE
				idventadet = $idventadet
				and idventa = $idventa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
				update ventas_detalles 
				set 
				costo = COALESCE(subtotal_costo/cantidad,0)
				WHERE
				idventadet = $idventadet
				and idventa = $idventa
				and cantidad > 0
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
				update ventas_detalles 
				set 
				utilidad = subtotal-subtotal_costo
				WHERE
				idventadet = $idventadet
				and idventa = $idventa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            $rsdet->MoveNext();
        }
        // prorratear descuento
        if ($facturador_electronico != 'S') {
            if ($descuento_sobre_factura > 0 && $idproducto_descuento > 0) {
                $descuento_negativo = $descuento_sobre_factura * -1;
                //echo $descuento_negativo.'a';exit;
                //$year_det_f=date("Y");
                $texto_descuento = "NULL";
                if (trim($pchar_descuento) != '') {
                    $texto_descuento = trim($pchar_descuento);
                }

                // insertar 1 registro en ventas detalles
                $consulta = "
					INSERT INTO ventas_detalles
					(
					pventa, cantidad, subtotal, idventa, idemp, sucursal, idprod, pchar, 
					retirado_por, costo, subtotal_costo, utilidad, idtipoiva, iva, base_imponible,
					registrado_el, descuento, subtotal_sindesc, descuento_porc, subtotal_monto_iva,
					idprod_mitad1, idprod_mitad2, existencia, serialcostos,
					subtotal_monto_iva_excluido_diplomatico, iva_excluido_diplomatico,
					subtotal_anterior_diplomatico, pventa_anterior_diplomatico,
					pventa_monto_iva_anterior, cortesia, rechazo, idmotivorecha,
					idventatmp
					)	    
					VALUES 
					(
					$descuento_negativo, 1, $descuento_negativo, $idventa, 1, $idsucursal, $idproducto_descuento, $texto_descuento,
					NULL, 0, 0, $descuento_negativo, 0, 0, 0,
					'$ahora', 0, $descuento_negativo, 0, 0,
					NULL, NULL, 0, 0,
					0, NULL,
					0, 0,
					0, NULL, NULL, NULL,
					NULL
					)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $consulta = "
					select idventadet from ventas_detalles where idventa = $idventa order by idventadet desc limit 1 
					";
                $rsvdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idventadet = $rsvdet->fields['idventadet'];

                $parametros_array = [
                    'idventa' => $idventa,
                    'descuento' => $descuento_sobre_factura,

                ];
                $res = prorratear_descuento_impuesto($parametros_array);
                //print_r($res);exit;
                // insertar en ventas detalles impuesto

                foreach ($res as $key => $value) {
                    $iva_porc = $key;

                    $monto_iva = $value['monto_iva'];
                    $monto_col = $value['monto_columna'];
                    $gravadoml = $monto_col - $monto_iva;
                    if ($iva_porc == 0) {
                        $exento = 'S';
                    } else {
                        $exento = 'N';
                    }
                    if (floatval($monto_col) <> 0) {
                        $consulta = "
							INSERT INTO ventas_detalles_impuesto
							(idventadet, idventa, idproducto, idtipoiva, iva_porc_col, monto_col, gravadoml,
							 ivaml, exento) 
							VALUES 
							($idventadet, $idventa, $idproducto_descuento, 4, $iva_porc, $monto_col, $gravadoml,
							 $monto_iva,  '$exento')
							";
                        //echo $consulta;exit;
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    }

                }

            } /// if($descuento > 0 && $idproducto_descuento > 0){
        } // if($facturador_electronico != 'S'){


        // updates para factura
        $consulta = "
			update ventas_agregados set 
			idventa_cab = (select ventas_detalles.idventa from ventas_detalles where ventas_agregados.idventadet = ventas_detalles.idventadet and ventas_detalles.idventa = $idventa) 
			where 
			idventa_cab is null;
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			update ventas_agregados set porc_iva_ag = 10 where idventa_cab = $idventa;
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			update ventas_agregados set monto_iva_ag = (precio_adicional-((precio_adicional)/(1+porc_iva_ag/100)));
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			UPDATE `ventas_detalles` set subtotal_monto_iva=(subtotal-((subtotal)/(1+iva/100))) where idventa = $idventa;
			";
        //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        $consulta = "
			update ventas_detalles set subtotal_sindesc = subtotal+descuento where idventa = $idventa;
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			update ventas_detalles set descuento_porc = ((descuento/subtotal_sindesc)*100) where idventa = $idventa and descuento > 0 and subtotal_sindesc > 0;
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
			update ventas set 
			razon_social = (select cliente.razon_social from cliente where cliente.idcliente = ventas.idcliente limit 1) 
			where 
			razon_social is null 
			and idventa = $idventa;
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //+COALESCE((select sum(monto_iva_ag) from ventas_agregados where idventa_cab = ventas.idventa),0)
        $consulta = "
			update ventas set 
			monto_iva = 
			COALESCE(( select sum(subtotal_monto_iva) from ventas_detalles where ventas_detalles.idventa = ventas.idventa ),0)
			
			-COALESCE((ventas.descneto/11),0),
			totalcobrar=COALESCE(( select sum(subtotal) from ventas_detalles where ventas_detalles.idventa = ventas.idventa ),0)
			where
			idventa = $idventa;
			;
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        //Teminado marcamos el pedido
        if ($idmesa == 0) {
            $update = "
				Update tmp_ventares_cab 
				set 
				idventa=$idventa,
				registrado='S',
				estado=3 
				where 
				idtmpventares_cab=$pedido 
				";
            $conexion->Execute($update) or die(errorpg($conexion, $update));



            //si hay mesa temporal ponerla y cambiar el canal
            if ($idmesatmp > 0) {
                $update = "
					update tmp_ventares_cab
					set 
					idmesa = $idmesatmp,
					idcanal = 4
					where 
					idtmpventares_cab=$pedido
					and idmesa_tmp is not null
					";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
                $update = "
					update ventas
					set 
					idmesa = $idmesatmp,
					idcanal = 4
					where 
					idventa=$idventa
					";
                $conexion->Execute($update) or die(errorpg($conexion, $update));

            }
        } else {
            $update = "
				Update tmp_ventares_cab 
				set 
				idventa=$idventa, 
				registrado='S',
				estado=3 
				where
				idmesa=$idmesa 
				and finalizado = 'S' 
				and registrado = 'N' 
				and estado = 1
				and idempresa = $idempresa 
				and idsucursal = $idsucursal
				";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            // actualiza el detalle temporal

        }

        $update = "
			update tmp_ventares 
			set 
			registrado = 'S' 
			where 
			registrado = 'N' 
			and (
				select idtmpventares_cab 
				from tmp_ventares_cab 
				where 
				registrado = 'S'
				and idtmpventares_cab = tmp_ventares.idtmpventares_cab
				limit 1
				) is not null
			";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        // si es por tablet de almuerzo
        if ($retirotab == 'S') {
            $ahorad = date("Y-m-d");
            $consulta = "
				update adherente_retira 
				set 
				idventa = $idventa,
				estado = 'A'
				where 
				idadherente = $idadherente_constante
				and fecha = '$ahorad'
				and idempresa = $idempresa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //echo $consulta;
            //exit;
        }

        //echo "a";exit;
        // si completo el campo factura y no es autoimpresor
        if ($autoimpresor != 'S' && $ticketofactura == 'FAC') {
            //echo $fac_suc ;exit;
            $numfac = intval($fac_nro);
            $ano = date("Y");
            // busca si existe algun registro
            $buscar = "
				Select idsuc, numfac as mayor  
				from lastcomprobantes
				 where 
				 idsuc=$fac_suc 
				 and pe=$fac_pexp 
				 and idempresa=$idempresa 
				 order by ano desc 
				 limit 1
				 ";
            // echo $buscar;exit;
            $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
            if (intval($rsfactura->fields['idsuc']) == 0) {
                $consulta = "
					INSERT INTO lastcomprobantes
					(idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
					numhoja, hojalevante, idempresa) 
					VALUES
					($fac_suc, $factura, $fac_nro, NULL, 0, NULL, 0, $ano, $fac_pexp, NULL, 
					NULL, 0, '', $idempresa)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
                $consulta = "
					update lastcomprobantes
					set 
					numfac = $proxfactura,
					factura = $factura
					where
					idempresa = $idempresa
					and idsuc=$fac_suc
					and pe=$fac_pexp
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }



        }
        // si completo el campo factura y si es autoimpresor
        if ($autoimpresor == 'S' && $ticketofactura == 'FAC') {
            $ano = date("Y");

            // busca si existe algun registro
            $buscar = "
				Select idsuc, numfac as mayor  
				from lastcomprobantes 
				where 
				idsuc=$factura_suc 
				and pe=$factura_pexp 
				and idempresa=$idempresa
				order by ano desc
				limit 1
				";
            $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexionpg, $buscar));
            $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
            if (intval($rsfactura->fields['idsuc']) == 0) {
                $consulta = "
					INSERT INTO lastcomprobantes
					(idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
					numhoja, hojalevante, idempresa) 
					VALUES
					($factura_suc, $factura, $fac_nro, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
					NULL, 0, '', $idempresa)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            } else {
                $consulta = "
					update lastcomprobantes
					set 
					numfac = $maxnfac,
					factura = $factura
					where
					idempresa = $idempresa
					and idsuc=$factura_suc
					and pe=$factura_pexp
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
        }
        //AGREGADO 11/02/2020
        //SI trae un valor adicional - voucher tar etc , almacenamos en la tabla de adicionales
        if ($adicional_int > 0 && $idventa > 0) {
            $insertar = "Insert into ventas_reg_adicionales
				 (idventa,monto_abonado,valor_adicional,registrado_el,registrado_por,estado)
				 values
				 ($idventa,$totalcobrar,$adicional_int,current_timestamp,$idusu,1)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        }
        // borrar carrito cobro del usuario
        $consulta = "
			DELETE 
			FROM carrito_cobros_ventas 
			WHERE 
			registrado_por = $idusu
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // excluye de algunos reportes
        $consulta = "
			update ventas set 
			excluye_repven = 1
			WHERE
			(
				select idventa 
				from ventas_detalles 
				 inner join productos on productos.idprod_serial = ventas_detalles.idprod 
				where 
				productos.excluye_reporteventa = 1
				and ventas_detalles.idventa = $idventa
				limit 1
			) is not null
			and ventas.idventa = $idventa
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        if ($forzar_excluyerepven == 1) {
            $consulta = "
				update ventas set excluye_repven = 1 where idventa = $idventa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        // si vino por la web
        if (intval($_SESSION['idwebpedido']) > 0) {
            $idwebpedido = intval($_SESSION['idwebpedido']);
            $consulta = "
				update ventas set idwebpedido = $idwebpedido where idventa = $idventa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
				update web_pedidos 
				set 
				idventa = $idventa,
				facturado = 'S',
				facturado_por = $idusu,
				facturado_el = '$ahora'
				where 
				idwebpedido = $idwebpedido
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $_SESSION['idwebpedido'] = 0;

        }
        /*-------------------------------------------------
             Preferencias y acciones de otros rubros
        ---------------------------------------------------*/
        if ($controlalevante > 0) {
            $buscar = "Select dias_credito from cliente where idcliente=$idcliente";
            $rsc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $dias_credito = intval($rsc->fields['dias_credito']);
            $vtofac = antisqlinyeccion($primervto, 'date');
            //Armamos el texto para la impresion posterior en las facturas
            if (intval($numero_orden_compra) > 0) {
                $compuesto .= "OC Num: $numero_orden_compra | ";
            }
            if ($dias_credito > 0) {
                $compuesto .= "CRED:$dias_credito dias ";
            }
            $update = "
				update ventas 
				set
				ocnumero=$numero_orden_compra,
				obs_varios='$compuesto'
				where 
				idventa=$idventa 
				";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
        }

        if (intval($_POST['cod_pulsera']) > 0) {
            $consulta = "
				INSERT INTO pulseras_transacciones
				(idpulsera, idtipotranspulsera, idventa, monto, idpulsera_origen, idpulsera_destino, estado, registrado_el, registrado_por) 
				VALUES 
				($idpulsera,2,$idventa,$totalcobrar,NULL, NULL, 1, '$ahora', $idusu)
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // recalcular saldo pulsera
            $parametros_array = [
                'idpulsera' => $idpulsera
            ];
            recalcular_saldo_pulseras($parametros_array);
            // los consumos no afectan  caja
            $consulta = "
				update gest_pagos set estado = 6 where idventa = $idventa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // los consumos se marcan como consumo en ventas
            $consulta = "
				update ventas set idtipotran = 2 where idventa = $idventa
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }
        if ($_POST['pulsera_cargauto'] == 'S') {
            $idpulsera = intval($_POST['idpulsera_carga']);
            $consulta = "
				INSERT INTO pulseras_transacciones
				(idpulsera, idtipotranspulsera, idventa, monto, idpulsera_origen, idpulsera_destino, estado, registrado_el, registrado_por) 
				VALUES 
				($idpulsera,1,$idventa,$totalcobrar,NULL, NULL, 1, '$ahora', $idusu)
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // recalcular saldo pulsera
            $parametros_array = [
                'idpulsera' => $idpulsera
            ];
            recalcular_saldo_pulseras($parametros_array);
            // excluir de ventas
            /*$consulta="
            update ventas set excluye_repven = 1 where idventa = $idventa
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            */

        }
        if ($_POST['pulsera_entrada'] == 'S') {
            $idpulsera = intval($_POST['idpulsera']);
            $consulta = "
				INSERT INTO pulseras_transacciones
				(idpulsera, idtipotranspulsera, idventa, monto, idpulsera_origen, idpulsera_destino, estado, registrado_el, registrado_por) 
				VALUES 
				($idpulsera,6,$idventa,$totalcobrar,NULL, NULL, 1, '$ahora', $idusu)
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // recalcular saldo pulsera
            $parametros_array = [
                'idpulsera' => $idpulsera
            ];
            recalcular_saldo_pulseras($parametros_array);

            $consulta = "
				update pulseras set entrada_cobrada = 'S' where idpulsera = $idpulsera;
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        }
        // si es un evento
        if ($ideventoped > 0) {
            $consulta = "
				insert into ventas_datosextra
				(idventa,idevento)
				values
				($idventa,$ideventoped)
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $parametros_array = ['idevento' => $ideventoped];
            actualiza_saldo_evento($parametros_array);
        }




        // genera factura electronica
        if ($facturador_electronico == 'S') {
            if ($ticketofactura == 'FAC') {
                // GENERAR DOCUMENTO ELECTRONICO
                $parametros_array = [
                    'idventa' => $idventa,
                    'iddocumentoelectronico' => 1
                ];
                genera_documento_electronico($parametros_array);
                /*$res_electro_json=$obj_electro->generarXML($idventa,$slot_firma,$tipo_documento_set);
                //echo $res_electro_json;
                $res_electro_array=json_decode($res_electro_json,true);
                $status_electro=intval($res_electro_array['status']);
                // si es correcto
                if($status_electro == 1){
                    $consulta="
                    update ventas set electronica_ok = 'S' where idventa = $idventa
                    ";
                    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                    $consulta="
                    update documentos_electronicos_emitidos set estado_set = 2 where idventa = $idventa
                    ";
                    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

                }*/
            }
        }


        // si todo finalizo correctamente
        $consulta = "
			update ventas set finalizo_correcto = 'S' where idventa = $idventa
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // borrra cookie domicilio
        if (isset($_COOKIE['dom_deliv'])) {
            $_COOKIE['dom_deliv'] = null;
            unset($_COOKIE['dom_deliv']);
            setcookie('dom_deliv', null, -1, '/');
        }
        if (isset($_SESSION['idwebpedido'])) {
            $_SESSION['idwebpedido'] = null;
            unset($_SESSION['idwebpedido']);
        }
        // borra cliente previo
        $_SESSION['idclienteprevio'] = 0;

        // lista precio
        if (intval($_SESSION['idlistaprecio']) > 0) {
            $idlistaprecio = intval($_SESSION['idlistaprecio']);
            $consulta = "
				SELECT desactiva_al_vender FROM lista_precios_venta where idlistaprecio = $idlistaprecio limit 1
				";
            $rslist = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rslist->fields['desactiva_al_vender'] == 'S') {
                $_SESSION['idlistaprecio'] = 0;
                $_SESSION['idlistaprecio'] = null;
                unset($_SESSION['idlistaprecio']);
            }
        }
        // CANAL VENTA
        if (intval($_SESSION['idcanalventa']) > 0) {
            $idcanalventa = intval($_SESSION['idcanalventa']);
            $consulta = "
				SELECT desactiva_al_vender FROM canal_venta where idcanalventa = $idcanalventa limit 1
				";
            $rslist = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rslist->fields['desactiva_al_vender'] == 'S') {
                $_SESSION['idcanalventa'] = 0;
                $_SESSION['idcanalventa'] = null;
                unset($_SESSION['idcanalventa']);
            }
        }

        // regalia
        if (intval($_SESSION['idfacturaregalia']) > 0) {
            $idfacturaregalia = intval($_SESSION['idfacturaregalia']);
            $consulta = "
				update facturas_regalia
				set
				idventa = $idventa,
				estado = 3
				where
				idfacturaregalia = $idfacturaregalia
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $_SESSION['idfacturaregalia'] = 0;
            $_SESSION['idfacturaregalia'] = null;
            unset($_SESSION['idfacturaregalia']);
        }

        // cortesia
        if (trim($_POST['cortesia_diaria']) == 'S') {
            $cortesia_diaria_ci = intval($_POST['cortesia_diaria_ci']);
            $consulta = "
				insert into cortesia_diaria
				(idventa, fecha, cedula, idsucursal, idcaja, estado, registrado_por, registrado_el)
				values
				($idventa, '$ahora', $cortesia_diaria_ci, $idsucursal, $idcaja, 1, $idusu, '$ahora')
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        }




        if ($_POST['redir'] == 'S') {
            header("location: gest_ventas_resto.php");
            exit;
        } else {
            // si pide respuesta por json
            if ($_POST['json'] == 'S') {
                // si envio factura o no
                $redirfac = 'N';
                if (intval($fac_nro) > 0) {
                    $redirfac = 'S';
                }
                if ($autoimpresor == 'S') {
                    $redirfac = 'S';
                }
                // respuesta json
                $arr = [
                'idventa' => $idventa,
                'redirfac' => $redirfac,
                'error' => ''
                ];

                // convierte a formato json
                $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

                // devuelve la respuesta formateada
                echo $respuesta;


            } else {
                if (intval($fac_nro) == 0) {
                    echo "ok";
                } else {
                    echo $idventa;
                }
            }
        }
        /*}else{
            // if valido no*/




    } //if valido

}
if ($valido != 'S') {
    // si pide respuesta por json
    if ($_POST['json'] == 'S') {
        $errores = str_replace('<br />', $saltolinea, $errores);
        // respuesta json
        $arr = [
        'error' => $errores
        ];

        // convierte a formato json
        $respuesta = json_encode($arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        // devuelve la respuesta formateada
        echo $respuesta;
    } else {
        echo $errores;
    }
}
