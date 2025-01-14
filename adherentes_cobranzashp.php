<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "21";
$submodulo = "137";
require_once("includes/rsusuario.php");

$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$script_impresora = trim($rspref->fields['script_ticket']);
if ($script_impresora == '') {
    $script_impresora = 'http://localhost/impresorweb/ladocliente.php';
}
$script_impresora = strtolower($script_impresora);
function acredita_pago_ciclo($monto, $idpago, $idcliente, $idadherente, $idserviciocom)
{
    // variables globales
    global $idempresa;
    global $idsucursal;
    global $conexion;
    global $ahora;
    global $idusu;

    // limpia variables
    $idcliente = intval($idcliente);
    $idadherente = intval($idadherente);
    $idserviciocom = intval($idserviciocom);
    $monto = intval($monto);
    $idpago = intval($idpago);
    $whereadd = "";

    if ($idadherente > 0) {
        $whereadd .= "
		and cuentas_clientes.idadherente = $idadherente
		";
    }
    if ($idserviciocom > 0) {
        $whereadd .= "
		and cuentas_clientes.idserviciocom = $idserviciocom
		";
    }

    $idpago_afavor = 0;
    $dispoact = $monto;
    while ($dispoact > 0) {
        // busca las cuentas por servicio
        $consulta = "
			select *, cuentas_clientes.saldo_activo
			from cuentas_clientes 
			where 
			cuentas_clientes.idcliente = $idcliente
			$whereadd
			and cuentas_clientes.saldo_activo > 0
			and cuentas_clientes.idempresa = $idempresa
			order by registrado_el asc, idcta asc
			";
        $rscuen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcta = intval($rscuen->fields['idcta']);
        // si encuentra recorre y abona
        if ($idcta > 0) {
            // recorre cada cuenta
            while (!$rscuen->EOF) {
                $idcta = $rscuen->fields['idcta'];
                $saldoactivo_cuenta = intval($rscuen->fields['saldo_activo']);
                $idadherente = intval($rscuen->fields['idadherente']);
                $idserviciocom = intval($rscuen->fields['idserviciocom']);
                //echo $dispoact."<br />";
                // si el disponible es mayor a la cuenta
                if ($dispoact > $saldoactivo_cuenta) {
                    $monto_abonado = $saldoactivo_cuenta;

                    // registra el pago
                    $consulta = "
						INSERT INTO 
						cuentas_clientes_pagos
						(fecha_pago, idpago, idpago_afavor, idcuenta, idcliente, monto_abonado, registrado_por, idempresa, efectivogs, chequegs, 
						banco, chequenum, estado, sucursal, idtransaccion, totalgs, anulado_por, anulado_el, idadherente, idserviciocom) 
						VALUES 
						('$ahora', $idpago, $idpago_afavor, $idcta, $idcliente, $monto_abonado, $idusu, $idempresa, $monto_abonado, 0, 0, 0, 1, 
						$idsucursal, 0, $monto_abonado, 0, NULL, $idadherente, $idserviciocom)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    // cera el saldo de la cuenta
                    $consulta = "
						update cuentas_clientes 
						set
						saldo_activo = 0
						where
						idcliente = $idcliente
						and cuentas_clientes.idadherente = $idadherente
						and cuentas_clientes.idserviciocom = $idserviciocom
						and cuentas_clientes.idcta = $idcta
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    // resta al saldo de pago lo abonado
                    $dispoact = $dispoact - $saldoactivo_cuenta;

                    // si el disponible  es menor a la cuenta
                } elseif ($dispoact < $saldoactivo_cuenta) {
                    $monto_abonado = $dispoact;
                    $saldonew = $saldoactivo_cuenta - $monto_abonado;

                    // registra el pago
                    $consulta = "
						INSERT INTO 
						cuentas_clientes_pagos
						(fecha_pago, idpago, idpago_afavor, idcuenta, idcliente, monto_abonado, registrado_por, idempresa, efectivogs, chequegs, 
						banco, chequenum, estado, sucursal, idtransaccion, totalgs, anulado_por, anulado_el, idadherente, idserviciocom) 
						VALUES 
						('$ahora', $idpago, $idpago_afavor, $idcta, $idcliente, $monto_abonado, $idusu, $idempresa, $monto_abonado, 0, 0, 0, 1, 
						$idsucursal, 0, $monto_abonado, 0, NULL, $idadherente, $idserviciocom)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    // pone la diferencia como saldo de la cuenta
                    $consulta = "
						update cuentas_clientes 
						set
						saldo_activo = $saldonew
						where
						idcliente = $idcliente
						and cuentas_clientes.idadherente = $idadherente
						and cuentas_clientes.idserviciocom = $idserviciocom
						and cuentas_clientes.idcta = $idcta
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    // cera el saldo de pago
                    $dispoact = 0;
                    break 2;


                    // si el disponible es igual a la cuenta
                } else {
                    $monto_abonado = $dispoact;

                    // registra el pago
                    $consulta = "
						INSERT INTO 
						cuentas_clientes_pagos
						(fecha_pago, idpago, idpago_afavor, idcuenta, idcliente, monto_abonado, registrado_por, idempresa, efectivogs, chequegs, 
						banco, chequenum, estado, sucursal, idtransaccion, totalgs, anulado_por, anulado_el, idadherente, idserviciocom) 
						VALUES 
						('$ahora', $idpago, $idpago_afavor, $idcta, $idcliente, $monto_abonado, $idusu, $idempresa, $monto_abonado, 0, 0, 0, 1, 
						$idsucursal, 0, $monto_abonado, 0, NULL, $idadherente, $idserviciocom)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    // cera el saldo de la cuenta
                    $consulta = "
						update cuentas_clientes 
						set
						saldo_activo = 0
						where
						idcliente = $idcliente
						and cuentas_clientes.idadherente = $idadherente
						and cuentas_clientes.idserviciocom = $idserviciocom
						and cuentas_clientes.idcta = $idcta
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    // cera el saldo de pago
                    $dispoact = 0;
                    break 2;
                }



                $rscuen->MoveNext();
            }


        } else { // if($idcta > 0){
            break;
        }

    } // while($dispoact > 0){

    return $dispoact;
    //$rs->MoveNext(); }

}


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


if (isset($_GET['s']) && trim($_GET['s']) == 'form1' && intval($_GET['id']) == 0) {

    //filtros
    $whereadd = "";
    $orderby = "";
    $hayfiltro = "N";
    if (trim($_GET['nombre']) != '') {
        $nombre = antisqlinyeccion(trim($_GET['nombre']), "like");
        $whereadd .= " and cliente.nombre like '%$nombre%' ".$saltolinea;
        $orderby .= " cliente.nombre asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['apellido']) != '') {
        $apellido = antisqlinyeccion(trim($_GET['apellido']), "like");
        $whereadd .= " and cliente.apellido like '%$apellido%' ".$saltolinea;
        $orderby .= " cliente.apellido asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['razon_social']) != '') {
        $razon_social = antisqlinyeccion(trim($_GET['razon_social']), "like");
        $whereadd .= " and cliente.razon_social like '%$razon_social%' ".$saltolinea;
        $orderby .= " cliente.razon_social asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['adherente']) != '') {
        $adherente = antisqlinyeccion(trim($_GET['adherente']), "like");
        $whereadd .= " and cliente.idcliente in (select idcliente from adherentes where nomape like '%$adherente%' )".$saltolinea;
        $orderby .= " cliente.razon_social asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['documento']) != '') {
        $documento = antisqlinyeccion(trim($_GET['documento']), "text");
        $whereadd .= " and cliente.documento = $documento ".$saltolinea;
        $orderby .= " cliente.documento asc, ".$saltolinea;
        $hayfiltro = "S";
    }
    if (trim($_GET['ruc']) != '') {
        $ruc = antisqlinyeccion(trim($_GET['ruc']), "text");
        $whereadd .= " and cliente.ruc = $ruc ".$saltolinea;
        $orderby .= " cliente.ruc asc, ".$saltolinea;
        $hayfiltro = "S";
    }





    if ($hayfiltro == "S") {
        $consulta = "
		select * 
		from cliente 
		where 
		idcliente is not null
		$whereadd
		order by 
		$orderby
		cliente.idcliente asc
		";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;
    }

}

if (isset($_GET['id']) && intval($_GET['id']) > 0 && (!isset($_GET['s']))) {

    $idcliente = intval($_GET['id']);
    $consulta = "
	select * 
	from cliente 
	where 
	idcliente = $idcliente
	";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    //filtros
    $whereadd = "";
    $orderby = "";
    // filtro fijo
    if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
        $desde = date("Y-m-").'01';
        $hasta = date("Y-m-d");
    } else {
        $desde = date("Y-m-d", strtotime($_GET['desde']));
        $hasta = date("Y-m-d", strtotime($_GET['hasta']));
    }

    // otros filtos
    if (intval($_GET['adh']) > 0) {
        $idadherente = antisqlinyeccion(trim($_GET['adh']), "int");
        $whereadd .= " and cuentas_clientes.idadherente = $idadherente ".$saltolinea;
    }
    if (intval($_GET['sc']) > 0) {
        $idserviciocom = antisqlinyeccion(trim($_GET['sc']), "int");
        $whereadd .= " and cuentas_clientes.idserviciocom = $idserviciocom ".$saltolinea;
    }




    $consulta = "
	SELECT cuentas_clientes.idcta, date(registrado_el) as fecha, 
	(select date(cuentas_clientes_pagos.fecha_pago) as fecha_pago from cuentas_clientes_pagos where idcuenta = cuentas_clientes.idcta order by cuentas_clientes_pagos.fecha_pago desc limit 1) as fecha_pago, cuentas_clientes.deuda_global, 
	(select SUM(cuentas_clientes_pagos.monto_abonado) from cuentas_clientes_pagos where idcuenta = cuentas_clientes.idcta) as monto_abonado, cuentas_clientes.saldo_activo, 
	cuentas_clientes.idcliente, 
	cuentas_clientes.idadherente, 
	cuentas_clientes.idserviciocom, 
	(select nombre_servicio from servicio_comida where servicio_comida.idserviciocom = cuentas_clientes.idserviciocom) as servicio,
	(select nomape from adherentes where adherentes.idadherente = cuentas_clientes.idadherente) as adherente,
	cuentas_clientes.idventa 
	FROM cuentas_clientes
	where 
	cuentas_clientes.idcliente = $idcliente
	and date(cuentas_clientes.registrado_el) >= '$desde'
	and date(cuentas_clientes.registrado_el) <= '$hasta'
	$whereadd
	order by date(registrado_el) asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));





}


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    $buscar = "Select * from cliente where idcliente=$idcliente and idempresa=$idempresa";
    $rscl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $ruccliente = trim($rscl->fields['ruc']);
    $obs = antisqlinyeccion($_POST['obs']);
    $idadherente = 0;
    // validaciones basicas
    $valido = "S";
    $errores = "";
    // recibe parametros
    $mediopago = intval($_POST['mediopago']);
    $monto_abonado = antisqlinyeccion($_POST['monto_abonado'], "float");
    $fecha_pago = antisqlinyeccion($ahora, "text");
    $registrado_por = antisqlinyeccion($idusu, "float");
    $chequenum = antisqlinyeccion($_POST['chequenum'], "float");
    $factura = antisqlinyeccion($_POST['factura'], "text");
    $discrim = substr(strtoupper(trim($_POST['discrim'])), 0, 1);
    $banco = intval($_POST['banco']);
    $transferid = 0;
    $montotransferido = 0;
    $efectivo = 0;
    $montocheque = 0;
    $tarjeta = 0;
    $numtarjeta = 0;
    $tipotarjeta = 0;
    // efectivo
    if ($mediopago == 1) {
        $efectivo = $monto_abonado;
    }
    // tarjeta de credito
    if ($mediopago == 2) {
        $tarjeta = $monto_abonado;
        $tipotarjeta = 1;
    }
    // mixto
    if ($mediopago == 3) {
        /* $monto_recibido=$totalcobrar-$montomixto;
        //$efectivo=$monto_recibido;
        $tarjeta=$montomixto;
        $tipotarjeta=1; */
        $valido = 'N';

    }
    // tarjeta de debito
    if ($mediopago == 4) {
        $tarjeta = $monto_abonado;
        $tipotarjeta = 2;
    }
    // cheque
    if ($mediopago == 5) {
        $montocheque = $monto_abonado;
        $numcheque = $chequenum;
    }
    // transferencia
    if ($mediopago == 6) {
        $montotransferido = $monto_abonado;
        $transferid = $chequenum;
    }
    // credito
    if ($mediopago == 7 || $mediopago == 8) {
        $valido = 'N';
    }
    if ($mediopago == 9) {
        $valido = 'N';

    }

    if ($monto_abonado <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_abonado no puede ser cero o negativo.<br />";
    }
    /*if ($factura == 'NULL'){
        $valido="N";
        $errores.=" - El campo monto_abonado no puede ser cero o negativo.<br />";
    }*/

    // si discrimina es si validar que coincida con el monto global
    if ($discrim == 'S') {
        // recorre cada concepto y va sumando para ver que coincida con el total
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
            // obtener del post
            $idserviciocom = intval($tad->fields['idserviciocom']);
            $idadherente = intval($tad->fields['idadherente']);
            $abonadoserv = $_POST['abona_'.$idserviciocom.'_'.$idadherente];
            $abonadoserv_acum += $abonadoserv;
            $tad->MoveNext();
        }
        if ($monto_abonado != $abonadoserv_acum) {
            $valido = "N";
            $errores .= " - La sumatoria de los servicios no coincide con el total a abonar.<br />";
        }
    }


    // si todo es correcto inserta
    if ($valido == "S") {


        // insertar pago en caja
        $consulta = "	
		INSERT INTO gest_pagos
		(
		idcliente, fecha, medio_pago, total_cobrado, chequenum, banco, factura, recibo, tickete, estado, ruc,  tipo_pago, idempresa, sucursal,
		 efectivo, codtransfer, montotransfer, montocheque, cajero, fechareal, idventa, anulado_el, anulado_por, montovale, idpedido, idmesa,
		  montotarjeta, numtarjeta, tipotarjeta, vueltogs, delivery, idcaja, rendido, fec_rendido,obs
		) 
		VALUES
		(
		$idcliente, '$ahora', $mediopago, $monto_abonado, $chequenum, $banco, $factura, NULL, NULL, 1, '$ruccliente', 1, $idempresa, $idsucursal,$efectivo,$transferid, $montotransferido, $montocheque, $idusu,'$ahora',
		 0, NULL, 0, $tarjeta, 0, 0, $tarjeta, $numtarjeta,$tipotarjeta,0, 0, $idcaja, 'S', NULL,'$obs'
		) 
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //Traemos el id del pago
        $buscar = "Select max(idpago) as mayor from gest_pagos where idcaja=$idcaja and cajero=$idusu order by idpago desc limit 1";
        $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idp = intval($rsd->fields['mayor']);

        $disponible = $monto_abonado;
        // si es global
        if ($discrim != 'S') {
            //Registramos el log
            $insertar = "Insert into 	
			adherentes_pagos_reg 
			(idpago	,fecha	,monto_abonado,idservicio,idadherente,registrado_por,registrado_el)
			values
			($idp,'$ahora',$monto_abonado,0,$idadherente,$idusu,'$ahora')";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


            $disponiblenew = acredita_pago_ciclo($disponible, $idp, $idcliente, $idadherente, $idserviciocom);

            // si despues de pagar todo, queda disponible
            if (intval($disponiblenew) > 0) {
                $idadherente = intval($idadherente);
                $idserviciocom = intval($idserviciocom);
                // insertar en pagos a favor
                $consulta = "
					INSERT INTO pagos_afavor_adh
					(idpago, monto, saldo, idcliente, idadherente, idserviciocom, fechahora, idusuario, idcaja, idempresa, idsucursal) 
					VALUES 
					($idp,$disponiblenew,$disponiblenew,$idcliente,$idadherente,$idserviciocom,'$ahora',$idusu,$idcaja,$idempresa,$idsucursal)
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }

            // si es discriminado
        } else {
            // recorre cada concepto y va acreditando con la funcion
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
                // obtener del post
                $idserviciocom = intval($tad->fields['idserviciocom']);
                $idadherente = intval($tad->fields['idadherente']);
                $abonadoserv = $_POST['abona_'.$idserviciocom.'_'.$idadherente];
                $disponiblenew = acredita_pago_ciclo($abonadoserv, $idp, $idcliente, $idadherente, $idserviciocom);

                // si despues de pagar todo, queda disponible
                if (intval($disponiblenew) > 0) {
                    $idadherente = intval($idadherente);
                    $idserviciocom = intval($idserviciocom);
                    // insertar en pagos a favor
                    $consulta = "
						INSERT INTO pagos_afavor_adh
						(idpago, monto, saldo, idcliente, idadherente, idserviciocom, fechahora, idusuario, idcaja, idempresa, idsucursal) 
						VALUES 
						($idp,$disponiblenew,$disponiblenew,$idcliente,$idadherente,$idserviciocom,'$ahora',$idusu,$idcaja,$idempresa,$idsucursal)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                }

                $tad->MoveNext();
            }
        }


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
            // obtener del post


            $idserviciocom = intval($tad->fields['idserviciocom']);
            $idadherente = intval($tad->fields['idadherente']);
            $ma = intval($_POST['abona_'.$idserviciocom.'_'.$idadherente]);
            $insertar = "Insert into 	
			adherentes_pagos_reg 
			(idpago	,fecha	,monto_abonado,idservicio,idadherente,registrado_por,registrado_el,monto_asignado)
			values
			($idp,'$ahora',$monto_abonado,$idserviciocom,$idadherente,$idusu,'$ahora',$ma)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);
            $tad->MoveNext();
        }






        header("Location: adherentes_cobranzas.php?ls=$idp");
        exit;

    }

}

//si viene un id de pago, es un pago x lineas o adelantos, x lo cual imprimimos el tk


$last = intval($_GET['ls']);
if ($last > 0) {
    $buscar = "Select *, razon_social from gest_pagos
	inner join cliente on cliente.idcliente=gest_pagos.idcliente
	where gest_pagos.idpago=$last";
    $rspp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $fechapago = date("d-m-Y", strtotime($rspp->fields['fecha']));
    $obs = trim($rspp->fields['obs']);
    $razon_social = trim($rspp->fields['razon_social']);
    $ruc = trim($rspp->fields['ruc']);
    $totalgs = $rspp->fields['total_cobrado'];
    $tr = $rspp->RecordCount();
    if ($tr > 0) {
        //Pagos a favor adherente
        $buscar = "Select * from pagos_afavor_adh where idpago=$last";
        $rspf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //Por adherente
        $buscar = "Select nombre_servicio,monto_asignado,nomape
		from adherentes_pagos_reg
		inner join  servicio_comida on servicio_comida.idserviciocom=adherentes_pagos_reg.idservicio
		inner join adherentes on adherentes.idadherente=adherentes_pagos_reg.idadherente
		where adherentes_pagos_reg.idpago=$last order by nomape,nombre_servicio";
        $rsad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $tad = $rsad->RecordCount();
    }
    //armar tk
    // centrar nombre empresa
    $nombreempresa_centrado = corta_nombreempresa($nombreempresa);
    $ahorta = date("d-m-Y H:i:s", strtotime($ahora));

    $texto = "
****************************************
$nombreempresa_centrado
            RECIBO DE PAGO
****************************************
PAGO N° $last | CONTADO
----------------------------------------
FECHA PAGO : $fechapago
FECHA IMP : $ahorta
Razon SOC.: $razon_social
RUC NRO.  : $ruc
----------------------------------------
Monto Abonado Neto:".formatomoneda($totalgs)."
----------------------------------------
";
    if ($tad > 0) {
        //hay adherentes
        while (!$rsad->EOF) {
            $adherente = trim($rsad->fields['nomape']);
            $servicio = trim($rsad->fields['nombre_servicio']);
            $montodes = intval($rsad->fields['monto_asignado']);
            if ($montodes > 0) {
                $texto .= "
$adherente
$servicio x  $montodes";
            }
            $rsad->MoveNext();
        }
    }
    $texto .= "
Observaciones:$obs



***Gracias x su Preferencia***";
    $texto = trim($texto);

    $imprimir = 1;

}//FIn de id de pago recibido





?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
<script>	
function imprime_cliente(){
		var idp=<?php echo $last?>;
		var texto = document.getElementById("texto").value;
		//alert(texto);
        var parametros = {
                "tk" : texto
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora; ?>',
                type:  'post',
				dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
				crossDomain: true,
                success:  function (response) {
						$("#impresion_box").html(response);	
						window.open("gest_impre_doc.php?ls="+idp);
							
                }
        });
	
}
	</script>
</head>
<body bgcolor="#FFFFFF" <?php if ($imprimir == 1) {?> onload="imprime_cliente();"<?php } ?>>
	


	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
      <br /><br />

      <div class="divstd">
    		<span class="resaltaditomenor">
    			Pagos de Cuentas a Credito<br />
    		</span>
 		</div>
<br /><hr /><br />
<?php if (!isset($_GET['id'])) { ?>
<form id="form1" name="form1" method="get" action="">

<table width="400" border="1">
  <tbody>
    <tr>
      <td><strong>Nombre:</strong></td>
      <td>
        <input type="text" name="nombre" id="nombre" value="<?php echo antixss($_GET['nombre']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>Apellido:</strong></td>
      <td><input type="text" name="apellido" id="apellido" value="<?php echo antixss($_GET['apellido']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>Razon Social Titular:</strong></td>
      <td><input type="text" name="razon_social" id="razon_social" value="<?php echo antixss($_GET['razon_social']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>RUC:</strong></td>
      <td><input type="text" name="ruc" id="ruc" value="<?php echo antixss($_GET['ruc']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>Adherente:</strong></td>
      <td><input type="text" name="adherente" id="adherente" value="<?php echo antixss($_GET['adherente']); ?>" /></td>
    </tr>
    <tr>
      <td><strong>Documento:</strong></td>
      <td><input type="text" name="documento" id="documento" value="<?php echo antixss($_GET['documento']); ?>" /></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" name="Buscar" id="Buscar" value="Buscar" />
        <input type="hidden" name="s" id="s" value="form1" /></td>
      </tr>
  </tbody>
</table>
</form>
<br /><hr /><br />
<?php } ?>
<?php if (isset($_GET['s']) && trim($_GET['s']) == 'form1' && intval($_GET['id']) == 0) { ?>
<?php if ($rs->fields['idcliente'] > 0) {?>
<table width="938" border="1">
  <tbody>
      <tr>
      <td width="150" height="30" align="center" bgcolor="#F8FFCC"><strong>Nombre</strong></td>
      <td width="153" align="center" bgcolor="#F8FFCC"><strong>Apellido</strong></td>
      <td width="193" align="center" bgcolor="#F8FFCC"><strong>Razon Social Titular</strong></td>
      <td width="315" bgcolor="#F8FFCC"><strong>Adherentes</strong></td>
      <td bgcolor="#F8FFCC">&nbsp;</td>
    </tr>
<?php while (!$rs->EOF) {
    $idcliente = intval($rs->fields['idcliente']);?>
    <tr>
      <td><?php echo $rs->fields['nombre'];?></td>
      <td><?php echo $rs->fields['apellido'];?></td>
      <td><strong><?php echo $rs->fields['razon_social'];?></strong></td>
      <td><strong>
        <?php
        $buscar = "select nomape from adherentes where idcliente=$idcliente order by nomape asc";
    $rsadh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tsd = $rsadh->RecordCount();
    if ($tsd > 0) {
        $son = '';
        $paso = 0;
        while (!$rsadh->EOF) {
            $paso = $paso + 1;
            if ($paso == 1) {
                $son = '';
            } else {
                $son = $son.' | ';
            }
            $son = $son.$rsadh->fields['nomape'];
            $rsadh->MoveNext();
        }

        ?>
        <?php echo $son;?>
		  <?php }?>
		</strong></td>
      <td width="93"><input type="button" name="button" id="button" value="Seleccionar" onclick="document.location.href='adherentes_cobranzas.php?id=<?php echo $rs->fields['idcliente']; ?>'" /></td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<?php } else {?>
<p align="center">* No se encontraron registros con los datos de busqueda.</p>
<?php } ?>

<?php } ?>
<?php if (isset($_GET['id']) && intval($_GET['id']) > 0 && (!isset($_GET['s']))) {



    ?>
				
<div class="resumenmini">
<form id="form2" name="form2" method="get" action="">
<table width="400" height="124" border="1">
  <tbody>
	  <tr>
		  
		  <td height="33" colspan="5" align="center">Id Caja: <?php echo $idcaja?> | Cajero: <?php echo $cajero?>
	  </tr>
    <tr>
      <td align="center"><strong>Cliente</strong></td>
      <td colspan="3" align="center"><?php echo $rscli->fields['nombre'];?> <?php echo $rscli->fields['apellido'];?>&nbsp; <?php echo $rscli->fields['razon_social'];?></td>
      <td width="10" align="center"><input type="button" name="button2" id="button2" value="Cambiar" onmouseup="document.location.href='adherentes_cobranzas.php'" /></td>
    </tr>
    <tr>
      <td colspan="5" align="center"><a href="descarga_consumo_detallado.php?idc=<?php echo $idcliente?>" target="_blank"><img src="img/pdf.png" width="32" height="32" title="Consumo Detallado"/></a></td>
		</tr>
  </tbody>
</table>
 </form>
	<br /> Si la cuenta del cliente figura en cero,el pago asignado aqu&iacute;,ser&aacute; tomado como pago adelantado.
</div>
<?php
    $consulta = "
	SELECT sum(cuentas_clientes.saldo_activo) as saldo_activo
	FROM cuentas_clientes
	where 
	cuentas_clientes.idcliente = $idcliente
	";
    $rssact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>
</p><br /><br />


<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<form id="form1" name="form1" method="post" action="adherentes_cobranzas.php?id=<?php echo $idcliente ?>">
<table width="400" border="1" class="tablaconborde" align="center">
  <tbody>
    <tr>
      <td width="50%"><strong>Total Cuenta: </strong></td>
      <td><input name="textfield" type="text" disabled="disabled" id="textfield" readonly="readonly" value="<?php echo formatomoneda($rssact->fields['saldo_activo']); ?>" style="width: 99%; height: 40px;" /></td>
    </tr>
    <tr>
      <td><strong>Forma de Pago:</strong></td>
      <td><select name="mediopago" id="mediopago" style="width: 99%; height: 40px;"  onChange="mostrar(this.value)" required='required'>
        <option value="" selected="selected">Seleccionar </option>
        <?php
                                                    $buscar = "Select * from formas_pago where estado=1 order by descripcion asc";
    $rsf = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    while (!$rsf->EOF) {
        ?>
        <option value="<?php echo $rsf->fields['idforma']?>" <?php if ($_POST['mediopago'] == $rsf->fields['idforma']) {?>selected="selected"<?php } ?>><?php echo $rsf->fields['descripcion'];?>
          </option>
        <?php $rsf->MoveNext();
    }?>
        </select></td>
    </tr>

	<tr>
	  <td align="left"><strong>Monto Total Abonado</strong></td>
	  <td width="130" align="left"><input type="text" name="monto_abonado" id="monto_abonado" style="width: 99%; height: 40px;"  value="<?php  if (isset($_POST['monto_abonado'])) {
	      echo htmlentities($_POST['monto_abonado']);
	  } else {
	  }?>" required="required"   /></td>
	  </tr>

	<tr>
	  <td align="left"><strong>Banco</strong></td>
	  <td width="130" align="left">
		  <select name="banco" id="banco" style="height: 40px; width: 99%; ">
			    <?php
              $buscar = "Select * from gest_bancos where idempresa=$idempresa and estado=1";
    $bancos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    ?>
			  <option value="0" selected="selected">Seleccione banco</option>
				<?php while (!$bancos->EOF) {?>
			    <option value="<?php echo $bancos->fields['banco'] ?>"<?php  if (($_POST['banco']) == $bancos->fields['banco']) {?> selected="selected"<?php }?>><?php echo $bancos->fields['descripcion'] ?></option>
			  <?php $bancos->Movenext();
				}?>
		  </select></td>
	  </tr>

	<tr>
		<td align="left"><strong>Cheque Nro. / transfer</strong></td>
		<td width="130" align="left"><input type="text" name="chequenum" id="chequenum" style="width: 99%; height: 40px;"  value="<?php  if (isset($_POST['chequenum'])) {
		    echo htmlentities($_POST['chequenum']);
		} else {
		    echo htmlentities($rs->fields['chequenum']);
		}?>"   /></td>
	</tr>
	  <tr>
		<td align="left"><strong>Factura Num</strong></td>
		<td width="130" align="left"><input type="text" name="factura" id="factura" style="width: 99%; height: 40px;"  value="<?php  if (isset($_POST['factura'])) {
		    echo htmlentities($_POST['factura']);
		} else {
		    echo htmlentities($rs->fields['factura']);
		}?>"     /></td>
	</tr>
	  <tr>
	    <td align="left"><strong>Discriminar por Adherente y Servicio</strong></td>
	    <td align="left"><input type="radio" name="discrim" id="discrims" value="S" onmouseup="$('#prorrateo').show();"  checked="checked"  />
          SI
            <label for="radio">
            <input type="radio" name="discrim" id="discrimn" value="N" onmouseup="$('#prorrateo').hide();" />
            NO</label></td>
	    </tr>
	  <tr>
		  <td height="39" align="left" style="width:99%;"><p><strong>Observaciones/ Comentarios</strong></p>
		    <p><strong> (sale impreso en tickete/factura)</strong></p></td>
		  <td><textarea name="obs" id="obs"  rows="3" ></textarea></td>
	  </tr>

  </tbody>
</table>
	<?php

    //Vemos si posee un adherente
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
    actualiza_saldos_clientes($idcliente, 0, 0);
    while (!$tad->EOF) {
        $idserviciocom = intval($tad->fields['idserviciocom']);
        $idadherente = intval($tad->fields['idadherente']);
        actualiza_saldos_clientes($idcliente, $idadherente, 0);
        actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);
        $tad->MoveNext();
    }
    $tad->MoveFirst();

    if ($tad > 0) {
        //Mostramos la seccion desgloce

        ?>
		<br />
        <div id="prorrateo" style="display:;"> 
	<div class="resumenmini">
		<h1>Distribucion del pago por adherente :</h1>
		Si no completa los campos de totales, el monto abonado ser&aacute; prorateado de la cuenta m&aacute;s antigua en adelante.</div>
	<br />
	  <table width="550" border="1">
			<?php
                    $ante = 0;
        $paso = 0;
        while (!$tad->EOF) {
            $idaserv = intval($tad->fields['idserviciocom']);
            $idadherente = intval($tad->fields['idadherente']);
            //echo $idadherente;exit;
            $paso = $paso + 1;
            if ($paso == 1) {
                $ante = $idadherente;
            } else {
                if ($idadherente != $ante) {
                    $paso = 1;
                    $ante = $idadherente;

                }
            }
            if ($paso == 1) {


                ?>
					<tr>
						<td width="59" height="38" align="right" bgcolor="#FFFA8D"><strong>Adherente</strong></td>
						<td width="92" align="center">&nbsp;<?php echo $tad->fields['nomape']; ?></td>
						<td width="85" align="right" bgcolor="#F4F4F4"><strong>Linea Credito</strong></td>
						<td width="79" align="right">&nbsp;<?php echo formatomoneda($tad->fields['linea_credito'])?></td>
						<td width="80" align="right" ><strong>Saldo Favor disponible</strong></td>
						<td width="40" align="right" bgcolor="#F4F4F4">0</td>
						
					</tr>
					<?php
            }//de paso=1?>
		  			<?php


            ?>
		  			<tr>
						<td height="29" align="right" bgcolor="#D2FFF4"><strong>Servicio</strong></td>
						<td align="center">&nbsp;<?php echo $tad->fields['nombre_servicio']; ?></td>
					  <td align="right"><strong>Disponible</strong></td>
						
					  <td align="right"><?php echo formatomoneda($tad->fields['disponibleserv'])?></td>
						<td align="right" 
							><strong>Total Acreditar Gs</strong></td>
					  <td align="right" bgcolor="#F4F4F4">
						  <input type="number" name="abona_<?php echo $idaserv?>_<?php echo $idadherente?>" style="height: 40px;" size="7" value="0" /></td>
						
		</tr>
		  			<?php $tad->MoveNext();
        } ?>
		  
		  

	  </table>
		
	<?php
    }
    ?>
    </div>
<br />
<p align="center">
  <input type="submit" name="button" id="button" value="Registrar" />
  <input type="hidden" name="MM_insert" value="form1" />
</p>
<br />
</form>

<p>&nbsp;</p>
<p align="center">&nbsp;</p>
<p>&nbsp;</p>



<?php } ?>

<div  id="impresion_box" hidden="hidden"><textarea readonly id="texto" style="display:; width:310px; height:500px;" ><?php echo $texto; ?></textarea></div><br />
          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>