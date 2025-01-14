<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

// funciones para stock
require_once("includes/funciones_stock.php");




$errores = "";
$valido = "S";

//print_r($_POST);
//exit;

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
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
$rsdep = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rsdep->fields['iddeposito']);
if ($iddeposito == 0) {
    $errores .= "No existe deposito de ventas asignado a la sucursal actual.";
    $valido = "N";
}




// parametros de entrada
$retirotab = substr(trim($_POST['retirotab']), 0, 1);
//echo $retirotab;
//exit;
$idcliente = intval($_POST['idcliente']);
$idzona = trim($_POST['idzona']);
//explodear
$idf = explode("-", $idzona);
$idzona = intval($idf[0]);
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
    if (trim($rscasa->fields['referencia']) != '') {
        $direccion .= ' - '.trim($rscasa->fields['referencia']);
    }
    $telefono = trim($rscasa->fields['telefono']);
    $nombre_deliv = antisqlinyeccion(trim($rscasa->fields['nombres']), 'text');
    $apellido_deliv = antisqlinyeccion(trim($rscasa->fields['apellidos']), 'text');
    if ($idzona == 0) {
        $valido = 'N';
        $errores .= '- Debes seleccionar la zona del delivery.<br />';
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
$adicional_int = intval($_POST['adicional']); // para numero
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
    $buscar = "Select costoentrega from gest_zonas where idzona=$idzona and idempresa=$idempresa and idsucursal=$idsucursal and estado=1 limit 1";
    $rszonita = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $delivery_costo = intval($rszonita->fields['costoentrega']);

} else {
    $delivery_costo = 0;
    $idzona = 0;
    $delivery = antisqlinyeccion('N', 'text');
}

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

    // monto total venta
    $montototal = $montototalprod + $montototalag;
    $totalventa = $montototal - $descuento + $delivery_costo;
} else {  // si es venta por tablet u otro
    //Total de la venta real
    if ($pedido > 0) {
        $buscar = "Select sum(monto) as total 
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
    $totalventa = $totalvendido + $delivery_costo - $descuento;
}

// si se bloquea la venta sin stock
if ($ventas_nostock == 2) {
    // si es venta por caja
    if ($generatmpcab == 'S') {

        $consulta = "
		select *
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
		and tmp_ventares.idproducto not in (
										select insumos_lista.idproducto
										from insumos_lista
										inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
										where
										gest_depositos_stock_gral.disponible > 0
										and gest_depositos_stock_gral.iddeposito = $iddeposito
										and insumos_lista.idproducto is not null
										)
		and (select insumos_lista.mueve_stock from insumos_lista where insumos_lista.idproducto = tmp_ventares.idproducto) = 'S'
		Limit 5
		";
        //echo $consulta;
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si hay productos en el carrito con stock 0 o menor
        if (intval($rs->fields['idproducto']) > 0) {
            $valido = 'N';
            $errores .= '- Esta intentando vender productos sin stock.<br />';
            $errores .= "Sin Stock: ";
            $prodsinstock = "";
            while (!$rs->EOF) {
                $prodsinstock .= strtolower($rs->fields['descripcion']).", ";
                $rs->MoveNext();
            }
            $errores .= substr(trim($prodsinstock), 0, -1);
            $errores .= ".<br />";

        }
        // si es venta por tablet u otro
    } else {
        // si no es mesa
        if ($pedido > 0) {
            $buscar = "
			Select *
			from  tmp_ventares_cab
			inner join tmp_ventares on tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
			inner join productos on tmp_ventares.idproducto = productos.idprod_serial
			where 
			tmp_ventares_cab.idtmpventares_cab=$pedido 
			and tmp_ventares.borrado = 'N'
			and tmp_ventares_cab.registrado='N' 
			and tmp_ventares_cab.idempresa = $idempresa 
			and tmp_ventares_cab.idsucursal = $idsucursal
			and tmp_ventares.idproducto not in (
											select insumos_lista.idproducto
											from insumos_lista
											inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
											where
											gest_depositos_stock_gral.disponible > 0
											and gest_depositos_stock_gral.iddeposito = $iddeposito
											and insumos_lista.idproducto is not null
											)
			and (select insumos_lista.mueve_stock from insumos_lista where insumos_lista.idproducto = tmp_ventares.idproducto) = 'S'
			Limit 5
			";
            // si es mesa
        } else {
            $buscar = "Select *
			from  tmp_ventares_cab 
			inner join tmp_ventares on tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
			inner join productos on tmp_ventares.idproducto = productos.idprod_serial
			where  
			finalizado='S' 
			and tmp_ventares.borrado = 'N'
			and registrado='N' 
			and estado=1 
			and tmp_ventares_cab.idsucursal = $idsucursal
			and tmp_ventares_cab.idempresa = $idempresa
			and idmesa = $idmesa
			and tmp_ventares.idproducto not in (
											select insumos_lista.idproducto
											from insumos_lista
											inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
											where
											gest_depositos_stock_gral.disponible > 0
											and gest_depositos_stock_gral.iddeposito = $iddeposito
											and insumos_lista.idproducto is not null
											)
			and (select insumos_lista.mueve_stock from insumos_lista where insumos_lista.idproducto = tmp_ventares.idproducto) = 'S'								
			Limit 5
			";
        }
        $rsmo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        // si hay productos en el carrito con stock 0 o menor
        if (intval($rsmo->fields['idproducto']) > 0) {
            $valido = 'N';
            $errores .= '- Esta intentando vender productos sin stock.<br />';
            $errores .= "Sin Stock: ";
            $prodsinstock = "";
            while (!$rsmo->EOF) {
                $prodsinstock .= strtolower($rsmo->fields['descripcion']).", ";
                $rsmo->MoveNext();
            }
            $errores .= substr(trim($prodsinstock), 0, -1);
            $errores .= ".<br />";
        }

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
$totalcobrar = $totalventa;
if ($montomixto < 0) {
    $montomixto = 0;
}
// valores por defecto que luego se cambian
$tipotarjeta = "NULL";
$efectivo = 0;
$tarjeta = 0;




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
    $numcheque = $adicional_int;
}
// transferencia
if ($mediopago == 6) {
    $montotransferido = $totalcobrar;
}
// credito
if ($mediopago == 7 || $mediopago == 8) {
    $monto_recibido = $totalcobrar;
}
if ($mediopago == 9) {
    //Venta rapida
    $mediopago = 1;
    $monto_recibido = $totalcobrar;
    $efectivo = $totalcobrar;
    $factura = 'NULL';
    $fac_suc = '';
    $fac_pexp = '';
    $fac_nro = 0;

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
}
// si el canal es mesa
if ($canal == 4) {
    if ($idmesa == 0) {
        $valido = 'N';
        $errores .= '- Debes indicar la mesa.<br />';
    }
}
// si hay descuento obliga a poner motivo
if ($descuento > 0) {
    if (trim($_POST['motivo_descuento']) == '') {
        $valido = 'N';
        $errores .= '- Debes indicar el motivo de descuento.<br />';
    }
}
// valida que el monto sea igual a la venta si no es efectivo
if ($mediopago != 1 && $mediopago != 3) {
    if ($totalcobrar <> $monto_recibido) {
        $valido = 'N';
        $errores .= '- El monto recibido debe ser igual a la venta cuando el medio de pago no es efectivo o mixto.<br />';
    }
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


////////////////////////////////// PEDIDOS //////////////////////////////////
if ($generatmpcab == 'S') {

    // validaciones para pedidos
    if ($totalprod == 0) {
        $errores .= "- Debe agregar al menos 1 producto al carrito.<br />";
        $valido = "N";
    }


    if ($valido == 'S') {
        // insertar cabecera temporal
        $consulta = "
		INSERT INTO tmp_ventares_cab
		(razon_social, ruc, chapa, observacion, monto, idusu, fechahora, idsucursal, idempresa,idcanal,delivery,idmesa,clase,tipoventa,delivery_zona,delivery_costo,nombre_deliv,apellido_deliv,idclientedel,iddomicilio) 
		VALUES 
		('$razon_social', '$ruc', $chapa, NULL, $montototal, $idusu, '$ahora', $idsucursal, $idempresa,$canal,$delivery,$idmesa,1,$tipoventa,$idzona,$delivery_costo,$nombre_deliv,$apellido_deliv,$idclientedel,$iddomicilio)
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

        //datos delivery
        if ($idzona > 0) {



            $llevapos = intval($_POST['llevapos']);
            if ($llevapos == 0 || $llevapos == 1) {
                $llevapos = 'N';
            } else {

                $llevapos = 'S';
            }
            $cambio = floatval($_POST['cambiode']);
            $observacion_delivery = antisqlinyeccion($_POST['observadelivery'], 'text');
            $consulta = "
			update tmp_ventares_cab
			set
			telefono='$telefono',
			delivery_zona=$idzona,
			direccion='$direccion',
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

        // marcar como finalizado
        $consulta = "
		update tmp_ventares
		set 
		finalizado = 'S',
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


////////////////////////////////// REGISTRAR VENTA //////////////////////////////////
if (($pedido > 0 or $idmesa > 0)) {



    //Cabecera temporal
    if (intval($idmesa) == 0) {
        $buscar = "SELECT * FROM tmp_ventares_cab where idtmpventares_cab=$pedido and registrado='N' and idempresa = $idempresa and idsucursal = $idsucursal";
        $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idcanal = intval($rscab->fields['idcanal']);
        $idmesatmp = intval($rscab->fields['idmesa_tmp']);
    } else {
        $buscar = "SELECT sum(monto) as monto FROM tmp_ventares_cab where idmesa=$idmesa and finalizado = 'S' and registrado = 'N' and estado = 1 and idempresa = $idempresa and idsucursal = $idsucursal";
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
    $operador_pedido = intval($rscabpri->fields['idusu']);
    $totalvendido = floatval($rscab->fields['monto']);
    $totalvendido_deldesc = floatval($rscab->fields['monto']) + floatval($rscab->fields['delivery_costo']) - $descuento;
    $iva10 = (floatval($totalvendido_deldesc) / 11);
    $tventa5 = 0;
    $iva5 = 0;
    $tventaex = 0;
    //cuerpo
    if ($idmesa == 0) {
        $buscar = "
		SELECT * 
		FROM tmp_ventares
		 where 
		idtmpventares_cab=$pedido 
		and borrado='N'
		and idtmpventares_cab in 
						(
						SELECT idtmpventares_cab 
						FROM tmp_ventares_cab 
						where 
						idtmpventares_cab=$pedido  
						and finalizado = 'S' 
						and registrado = 'N' 
						and estado = 1
						and idempresa = $idempresa 
						and idsucursal = $idsucursal
						)
		 ";
        $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    } else {
        $buscar = "
		SELECT * 
		FROM tmp_ventares 
		where  
		borrado='N' 
		and idtmpventares_cab in 
							(
							SELECT idtmpventares_cab 
							FROM tmp_ventares_cab 
							where 
							idmesa=$idmesa 
							and finalizado = 'S' 
							and registrado = 'N' 
							and estado = 1
							and idempresa = $idempresa 
							and idsucursal = $idsucursal
							)";
        $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    }





    if ($valido == 'S') {
        //Track ID
        $tcid = $idempresa.$idsucursal.$idusu;
        $v = date(YmdHis);
        $tcid = $tcid.$v;



        /*---------------------VENTAS*------------------------*/
        $insertar = "Insert into ventas
			(fecha,idcliente,tipo_venta,idempresa,sucursal,factura,recibo,ruchacienda,dv,
			total_venta,idtransaccion,trackid,registrado_por,totaliva10,totaliva5,texe,idpedido,			otrosgs,descneto,deliv,totalcobrar,tipoimpresion,vendedor,total_cobrado,estado,obs,idmesa,idcanal,idcaja,idzona,operador_pedido,formapago,vuelto,recibido,idadherente,idserviciocom,idclientedel,iddomicilio)
			VALUES
			('$ahora',$idcliente,$tipoventa,$idempresa,$idsucursal,$factura,NULL,$ruc_pri,$ruc_dv,	
			$totalvendido,0,$tcid,$idusu,$iva10,$iva5,$tventaex
			,$pedido,$delivery_costo,$descuento,'',$totalcobrar,1,$idusu,$totalcobrar,$esta,$motivo_descuento,$idmesa,$idcanal, 
			$idcaja,$idzona,$operador_pedido,$mediopago,$vuelto,$monto_recibido,$idadherente,$idservcom,$idclientedel,$iddomicilio)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        //Traemos el id de la venta
        $buscar = "Select max(idventa) as mayor from ventas where idempresa = $idempresa and vendedor = $idusu";
        $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idventa = intval($rsm->fields['mayor']);

        //Crear cuenta si es credito
        if ($tipoventa == 2) {
            // venta que acaba de insertarse

            //Credito
            $buscar = "Select max(idcta) as mayor from cuentas_clientes where idempresa=$idempresa";
            $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $mayor = intval($rs1->fields['mayor']) + 1;
            //Generamos la cuenta
            $insertar = "
				Insert into cuentas_clientes 
				(idcta,idempresa,sucursal,deuda_global,saldo_activo,idcliente,estado,registrado_el,registrado_por,idventa,idadherente,idserviciocom)
				values
				($mayor,$idempresa,$idsucursal,$totalcobrar,$totalcobrar,$idcliente,1,'$ahora',$idusu,$idventa,$idadherente,$idservcom)
				";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

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
            acredita_saldoafavor($idcliente);
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
				montocheque,cajero,idventa,vueltogs,idpedido,delivery,idmesa,idcaja,rendido,tipotarjeta)
				values
				($idcliente,'$ahora',$mediopago,$totalcobrar,$numcheque,$banco,$numtarjeta,$tarjeta,
				$factura,'','','$ruc',$tipoventa,$idempresa,$idsucursal,$efectivo,
				$numerotrans,$montotransferido,$montocheque,$idusu,$idventa,$vuelto,$pedido,$delivery_costo,$idmesa,$idcaja,'$rendido',$tipotarjeta)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }


        //Detalles de la venta
        while (!$rsdet->EOF) {
            $idprod = trim($rsdet->fields['idproducto']);
            $buscar = "Select idprod,tipoiva,idmedida from productos where idprod_serial=$idprod";
            $rsfg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $codigoproducto = antisqlinyeccion($rsfg->fields['idprod'], 'text');
            $tipoiva = intval($rsfg->fields['tipoiva']);
            $idmedida = intval($rsfg->fields['idmedida']);
            //CANTIDAD DE PRODUCTO FINAL VENDIDO
            $cantidad = floatval($rsdet->fields['cantidad']);
            //PRECIO DE VENTA DE PRODUCTO FINAL VENDIDO
            $precioventa = floatval($rsdet->fields['precio']);
            $subtotal = floatval($rsdet->fields['subtotal']);
            $idventatmp = intval($rsdet->fields['idventatmp']);
            $idprod1 = antixss($rsdet->fields['idprod_mitad1'], 'int');
            $idprod2 = antixss($rsdet->fields['idprod_mitad2'], 'int');
            $combo = antixss($rsdet->fields['combo'], 'text');
            $combo_es = $rsdet->fields['combo'];
            $combinado = antixss($rsdet->fields['combinado'], 'text');
            $combinado_es = $rsdet->fields['combinado'];
            $idtipoproducto = $rsdet->fields['idtipoproducto'];
            $descuento = floatval($rsdet->fields['descuento']);
            $utilidadprox = 0;
            $disponible = 0;
            $menor = 0;
            $costobase = 0;
            //Manejo en Base a receta: el id de producto en la tabla de costo individual, es el que se debe usar .
            //Insertamos en Venta Detalle Como el manej es x receta, no hacer caso de las  columnas disponibles ni menor
            $insertar = "Insert into ventas_detalles 
				(cantidad,pventa,subtotal,idventa,idemp,sucursal,idprod,costo,utilidad,iva,registrado_el,descuento,
				idprod_mitad1,idprod_mitad2,existencia,serialcostos)
				values
				($cantidad,$precioventa,$subtotal,$idventa,$idempresa,$idsucursal,'$idprod',$costobase,
				$utilidadprox,$tipoiva,'$ahora',$descuento,'$idprod1','$idprod2',$disponible,$menor)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


            //traemos el id de ventas detalles insertado para usar en ventas_detalles de la receta
            $buscar = "Select  max(idventadet) as mayor from ventas_detalles where idventa=$idventa and idprod='$idprod'";
            $rsmayor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $mayorid = intval($rsmayor->fields['mayor']);
            $mi = $mayorid;
            $idventadet = $mayorid;




            //Paso 1): Traer la receta del producto
            $buscar = "Select * from recetas where idproducto='$idprod' and recetas.idempresa = $idempresa";

            $rsre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idreceta = intval($rsre->fields['idreceta']);
            // si tiene receta
            if ($idreceta > 0) {
                //Lista de componenentes de la receta
                $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva
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
                    if ($idmedida_med == 4) {
                        //es unitario, tomamos la cantidad de la receta
                        $cant = floatval($rsrecu->fields['cantidad']) * floatval($cantidad);
                    } else {
                        //cantidad vendida
                        $cant = floatval($cantidad);
                    }

                    // descuenta stock general
                    if ($mueve_stock != 'N') {
                        descontar_stock_general($insu, $cant, $iddeposito);
                        $costo_insumo_utilizado = descuenta_stock_vent($insu, $cant, $iddeposito);
                        movimientos_stock($insu, $cant, $iddeposito, 2, '-');
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

                    $insertar = "Insert into venta_receta
						(idventa,idventadet,idprod,idingrediente,idinsumo,cantidad,idmedida,costo,fechahora)
						values
						($idventa,$idventadet,'$pp',$in,$insu,$cant,$idmedida_med,$costo_insumo_utilizado,'$ahora')";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



                    $rsrecu->MoveNext();
                }
                //Por ultimo actualizamos el costo de la receta
                $buscar = "Select sum(costo)/sum(cantidad) as costog from venta_receta where idprod='$pp' and idventa=$idventa";
                $rscostoglobal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $costof = floatval($rscostoglobal->fields['costog']);
                $utilidadpp = $precioventa - $costof;
                $update = "Update ventas_detalles set costo=$costof,utilidad=$utilidadpp where idventa=$idventa and
					idprod='$pp' and idventadet=$mi";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
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
                            $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva
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

                                // descuenta stock general
                                if ($mueve_stock != 'N') {
                                    descontar_stock_general($insu, $ca, $iddeposito);
                                    $costo_insumo_utilizado = descuenta_stock_vent($insu, $ca, $iddeposito);
                                    movimientos_stock($insu, $ca, $iddeposito, 2, '-');
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

                // busca los productos que componen el combo
                $idprod_princ = $idprod;
                $consulta = "
					select * 
					from tmp_combos_listas
					where
					tmp_combos_listas.idventatmp = $idventatmp
					and tmp_combos_listas.idsucursal = $idsucursal
					and tmp_combos_listas.idusuario = $idusu
					and tmp_combos_listas.idempresa = $idempresa
					";
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                while (!$rsgrupos->EOF) {

                    $idprod_combo = $rsgrupos->fields['idproducto'];

                    //Paso 1): Traer la receta del producto
                    $buscar = "Select * from recetas where idproducto='$idprod_combo' and recetas.idempresa = $idempresa";
                    $rsre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $idreceta = intval($rsre->fields['idreceta']);

                    // si tiene receta
                    if ($idreceta > 0) {

                        //Lista de componenentes de la receta
                        $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva
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
                        $buscar = "Select  max(idventadet) as mayor from ventas_detalles where idventa=$idventa and idprod='$idprod_combo'";
                        $rsmayorid = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                        $mi = intval($rsmayorid->fields['mayor']);
                        while (!$rsrecu->EOF) {


                            $ir = intval($rsrecu->fields['idreceta']);
                            $in = intval($rsrecu->fields['ingrediente']);
                            $pp = intval($rsrecu->fields['idprod']);
                            $insu = intval($rsrecu->fields['idinsumo']);
                            $ca = floatval($rsrecu->fields['cantidad']);
                            $co = floatval($rsrecu->fields['costo']);
                            $med = intval($rsrecu->fields['idmedida']);
                            $va = intval($rsrecu->fields['tipoiva']);


                            // descuenta stock general
                            if ($mueve_stock != 'N') {
                                descontar_stock_general($insu, $ca, $iddeposito);
                                $costo_insumo_utilizado = descuenta_stock_vent($insu, $ca, $iddeposito);
                                movimientos_stock($insu, $ca, $iddeposito, 2, '-');
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

                // busca los productos que componen el combinado
                $consulta = "
					select * 
					from tmp_combinado_listas
					where
					tmp_combinado_listas.idventatmp = $idventatmp
					and tmp_combinado_listas.idsucursal = $idsucursal
					and tmp_combinado_listas.idusuario = $idusu
					and tmp_combinado_listas.idempresa = $idempresa
					";
                $rsgrupos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                // recorre cada producto
                while (!$rsgrupos->EOF) {

                    $idproducto_partes = $rsgrupos->fields['idproducto_partes'];
                    $idprod_combo = $idproducto_partes;

                    //Paso 1): Traer la receta del producto
                    $buscar = "Select * from recetas where idproducto='$idproducto_partes' and recetas.idempresa = $idempresa";
                    $rsre = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $idreceta = intval($rsre->fields['idreceta']);

                    // si tiene receta
                    if ($idreceta > 0) {

                        //Lista de componenentes de la receta
                        $buscar = "Select idreceta,idprod,ingrediente,cantidad,insumos_lista.idinsumo,costo,idmedida,tipoiva
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
                            $ca = floatval($rsrecu->fields['cantidad']);
                            $co = floatval($rsrecu->fields['costo']);
                            $med = intval($rsrecu->fields['idmedida']);
                            $va = intval($rsrecu->fields['tipoiva']);


                            // descuenta stock general
                            if ($mueve_stock != 'N') {
                                descontar_stock_general($insu, $ca, $iddeposito);
                                $costo_insumo_utilizado = descuenta_stock_vent($insu, $ca, $iddeposito);
                                movimientos_stock($insu, $ca, $iddeposito, 2, '-');
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



            //Agregados
            $buscar = "Select * from tmp_ventares_agregado where idventatmp=$idventatmp";
            //echo $buscar."<br />";
            $ag = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tag = $ag->RecordCount();
            if ($tag > 0) {
                while (!$ag->EOF) {

                    $producto = intval($ag->fields['idproducto']);
                    $ingediente = intval($ag->fields['idingrediente']);
                    $pre = intval($ag->fields['precio_adicional']);
                    $canti = floatval($ag->fields['cantidad']);
                    $char = trim($ag->fields['alias']);

                    $insertar = "Insert into ventas_agregados
						(idventadet,idproducto,idingrediente,precio_adicional,alias,cantidad,fechahora)
						values
						($mayorid,$producto,$ingediente,$pre,'$char',$canti,'$ahora')";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                    // buscar datos para venta receta
                    $consulta = "
						select insumos_lista.idinsumo, insumos_lista.idmedida, insumos_lista.costo
						from insumos_lista 
						inner join ingredientes on ingredientes.idinsumo = insumos_lista.idinsumo 
						WHERE
						 ingredientes.idingrediente = $ingediente
						 ";
                    $rsinsu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $insu = $rsinsu->fields['idinsumo'];
                    $idmedida = $rsinsu->fields['idmedida'];
                    $costo = $rsinsu->fields['costo'];


                    // descuenta stock general
                    if ($mueve_stock != 'N') {
                        descontar_stock_general($insu, $canti, $iddeposito);
                        $costo_insumo_utilizado = descuenta_stock_vent($insu, $canti, $iddeposito);
                        movimientos_stock($insu, $canti, $iddeposito, 2, '-');
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
                    // insertar en venta receta
                    $insertar = "Insert into venta_receta
						(idventa,idventadet,idprod,idingrediente,idinsumo,cantidad,idmedida,costo,fechahora)
						values
						($idventa,$mayorid,$producto,$ingediente,$insu,$canti,$idmedida,$costo_insumo_utilizado,'$ahora')";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                    $ag->MoveNext();
                }

            }


            //Sumar todos los costos de venta receta y updatear el costo global en detalles de la venta y utilidad


            $rsdet->MoveNext();
        }
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
				and idempresa = $idempresa 
				and idsucursal = $idsucursal
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
					and idempresa = $idempresa 
					and idsucursal = $idsucursal
					";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
                $update = "
					update ventas
					set 
					idmesa = $idmesatmp,
					idcanal = 4
					where 
					idventa=$idventa
					and idempresa = $idempresa 
					and sucursal = $idsucursal
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
        }
        // actualiza el detalle temporal
        $update = "
			update tmp_ventares 
			set registrado = 'S' 
			where 
			registrado = 'N' 
			and idtmpventares_cab in (
									select idtmpventares_cab 
									from tmp_ventares_cab 
									where 
									registrado = 'S'
									and idempresa = $idempresa
									and idsucursal = $idsucursal
									)
			and idempresa = $idempresa 
			and idsucursal = $idsucursal
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


        // si completo el campo factura
        if (intval($fac_nro) > 0) {
            $numfac = intval($fac_nro);
            $ano = date("Y");
            // busca si existe algun registro
            $buscar = "Select max(numfac) as mayor from lastcomprobantes where idsuc=$idsucursal and pe=$fac_pexp and idempresa=$idempresa ";
            $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexionpg, $buscar));
            $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
            if ($maxnfac <= 1) {
                $consulta = "
					INSERT INTO lastcomprobantes
					(idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
					numhoja, hojalevante, idempresa) 
					VALUES
					($idsucursal, $factura, $fac_nro, NULL, 0, NULL, 0, $ano, $fac_pexp, NULL, 
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
					and idsuc=$idsucursal
					and pe=$fac_pexp
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            }
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

        // borrra cookie domicilio
        if (isset($_COOKIE['dom_deliv'])) {
            $_COOKIE['dom_deliv'] = null;
            unset($_COOKIE['dom_deliv']);
            setcookie('dom_deliv', null, -1, '/');
        }


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
