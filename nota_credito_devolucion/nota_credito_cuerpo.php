<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

require_once("../includes/funciones_stock.php");
require_once("../includes/funciones_cobros.php");
require_once("../devoluciones/preferencias_devolucion.php");
// echo $preferencias_devolucion_impotacion;exit;
$idnotacred = intval($_GET['id']);
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}
$consulta = "
select idmotivo from nota_cred_motivos_cli where UPPER(nota_cred_motivos_cli.descripcion) like UPPER(\"devolucion\")
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtipomotivo_devolucion = intval($rs->fields['idmotivo']);
if ($idtipomotivo_devolucion == 0) {
    $errores .= "- El motivo DEVOLUCION no esta creado favor crearlo o contactar con soporte.<br />";
}


$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 nota_credito_cabeza.estado = 1 
 and nota_credito_cabeza.idnotacred = $idnotacred
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idnotacred = intval($rs->fields['idnotacred']);
$notacredito_numero = $rs->fields['numero'];
$fecha_nota = $rs->fields['fecha_nota'];
$ruc_notacred = $rs->fields['ruc'];
$idcliente_notacred = $rs->fields['idcliente'];
$idtandatimbrado_nc = $rs->fields['idtandatimbrado'];
$idcliente = $idcliente_notacred;
$idmotivo_nota_cred = $rs->fields['idmotivo'];
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}


// INICIO APERTURA DE CAJA ////////////////////////
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja_old = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja_old == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}

// si la caja no esta abierta direcciona
$parametros_array = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res = caja_abierta($parametros_array);
$idcaja_new = $res['idcaja'];
//print_r($res);
//exit;
if ($res['valido'] != 'S') {
    header("location: gest_administrar_caja.php");
    exit;
}
// FIN APERTURA DE CAJA ////////////////////////

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $valido_pri = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
        $valido_pri = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
        $valido_pri = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots


    // validaciones
    $consulta = "
	select * 
	from nota_credito_cabeza 
	where 
	numero = '$notacredito_numero' 
	and idnotacred <> $idnotacred
	and idtandatimbrado = $idtandatimbrado_nc
	and estado <> 6
	limit 1
	";
    $rscval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rscval->fields['idnotacred'] > 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "-Ya existe otra nota de credito con el mismo numero $notacredito_numero.<br />";
    }
    $consulta = "
	select * 
	from nota_credito_cuerpo 
	where 
	idnotacred = $idnotacred
	limit 1
	";
    $rscuerval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rscuerval->fields['idnotacred']) == 0) {
        $valido = "N";
        $valido_pri = "N";
        $errores .= "-No se cargo ningun articulo o concepto a aplicar.<br />";
    }

    // recorre cada articulo y valida si la cantidad  no supera lo ya aplicado en todas las notas para esa venta
    $consulta = "
	select ncc.*, nota_credito_cabeza.idcliente,
	(
		select descripcion 
		from productos 
		where 
		productos.idprod_serial = ncc.codproducto
	) as producto,
	(
		select sum(nota_credito_cuerpo.cantidad) as cantidad 
		from nota_credito_cuerpo 
		inner join nota_credito_cabeza on nota_credito_cabeza.idnotacred = nota_credito_cuerpo.idnotacred
		where 
		nota_credito_cuerpo.idventa = ncc.idventa
		and nota_credito_cuerpo.codproducto = ncc.codproducto
		and nota_credito_cabeza.estado <> 6
	) as cantidad_todasnotas,
	(
		select sum(ventas_detalles.cantidad) as cantidad 
		from ventas_detalles 
		where 
		ventas_detalles.idventa = ncc.idventa
		and ventas_detalles.idprod = ncc.codproducto
	) as cantidad_factura,
	(
		select sum(nota_credito_cuerpo.subtotal) as subtotal 
		from nota_credito_cuerpo 
		inner join nota_credito_cabeza on nota_credito_cabeza.idnotacred = nota_credito_cuerpo.idnotacred
		where 
		nota_credito_cuerpo.idventa = ncc.idventa
		and nota_credito_cuerpo.idnotacred = $idnotacred
		and nota_credito_cabeza.estado <> 6
	) as subtotal_estanota,
	(
		select tipo_venta 
		from ventas 
		where 
		idventa = ncc.idventa
	) as tipo_venta,
	(
		select estado 
		from ventas 
		where 
		idventa = ncc.idventa
	) as estado_venta,
	(
		select sum(saldo_activo) as saldo_activo
		from cuentas_clientes
		where 
		idventa = ncc.idventa
	) as saldo_activo,
	(
		select idcta
		from cuentas_clientes
		where 
		idventa = ncc.idventa
	) as idcta,
	(
		select idcliente 
		from ventas 
		where 
		idventa = ncc.idventa
		and estado <> 6
	) as idcliente
	from nota_credito_cuerpo ncc
	inner join nota_credito_cabeza on nota_credito_cabeza.idnotacred = ncc.idnotacred
	where 
	ncc.idnotacred = $idnotacred
	and nota_credito_cabeza.estado = 1
	";
    $rscuerpo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    while (!$rscuerpo->EOF) {
        $clase = $rscuerpo->fields['clase']; // 1 articulo 2 monto global 3 monto articulo

        $idventa = intval($rscuerpo->fields['idventa']);
        $idcta = intval($rscuerpo->fields['idcta']);
        //$subtotal=floatval($rscuerpo->fields['subtotal_estanota']);
        $subtotal = floatval($rscuerpo->fields['subtotal']);

        // si aplica a facturas
        if ($idventa > 0) {
            $idcliente = $rscuerpo->fields['idcliente'];
            // SI ES ARTICULO
            if ($clase == 1) {
                $producto = $rscuerpo->fields['producto'];
                if ($rscuerpo->fields['cantidad_todasnotas'] > $rscuerpo->fields['cantidad_factura']) {
                    $valido = "N";
                    $valido_pri = "N";
                    $errores .= "-La cantidad ingresada en el articulo [$producto] para la venta [$idventa].<br />";
                }
            }
            // si la venta es a credito
            if ($rscuerpo->fields['tipo_venta'] == 2) {
                if ($rscuerpo->fields['subtotal_estanota'] > $rscuerpo->fields['saldo_activo']) {
                    $valido = "N";
                    $valido_pri = "N";
                    $errores .= "-El monto total a aplicar a la venta [$idventa] supera el saldo de la cuenta.<br />";
                }
            }
            if ($rscuerpo->fields['estado_venta'] == 6) {
                $valido = "N";
                $valido_pri = "N";
                $errores .= "-La venta [$idventa] se encuentra anulada.<br />";
            }

            // validar que el idcliente de la nota de credito corresponda a los idcliente de todos los idventa registrados
            if ($idcliente != $idcliente_notacred) {
                $valido = "N";
                $valido_pri = "N";
                $errores .= "-El cliente [$idcliente] de la venta [$idventa] no es el mismo de la nota de credito [$idcliente_notacred].<br />";
            }


            // si es a credito
            $ex_credito = "N";
            if ($idcta > 0) {
                $ex_credito = "S";
                if ($valido_pri == "S") {
                    // inserta en los carritos
                    $consulta = "
					INSERT INTO 
					tmp_carrito_cobros
					(idcta, idcliente, monto_abonar, registrado_por, registrado_el, estado, notacred,idnotacred)
					VALUES
					($idcta,$idcliente,$subtotal,$idusu,'$ahora',1,'S',$idnotacred)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $consulta = "
					INSERT INTO tmp_carrito_cobros_fpag
					(idcliente, registrado_por, registrado_el, idformapago, 
					monto_pago, nrochq, fecha_emision, fecha_vencimiento, idbanco, idmoneda, nrocta, nomape_titular, 
					idtipopersona_titular, idpaisdoc_titular, idtipodoc_titular, nrodoc_titular, idchequerecibidotipo, 
					codtransfer, nroboletadepo, notacred, idnotacred)
					VALUES
					($idcliente,$idusu,'$ahora',12,
					$subtotal, NULL, NULL, NULL, NULL, 1, NULL, NULL,
					NULL, NULL, NULL, NULL, NULL, 
					NULL, NULL, 'S',$idnotacred
					)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }

            }

        } // if($idventa > 0){


        $rscuerpo->MoveNext();
    }


    if ($valido_pri == "S") {
        // datos para validar pago
        $datos_ar = [
            'fecha_pago' => $rs->fields['fecha_nota'],
            'registrado_por' => $idusu,
            'idcliente' => $rs->fields['idcliente'],
            'idcaja_new' => $idcaja_new,
            'idcaja_old' => $idcaja_old,
            'registrado_el' => $ahora,
            'recibo' => '',
            'sucursal' => $idsucursal,
            'idnotacredito' => $idnotacred,
            'notacredito' => $notacredito_numero,
        ];
        //print_r($datos_ar);


        // valida pago
        $res = valida_pago_cuentacliente($datos_ar);
        if ($res['valido'] == 'N') {
            $valido = "N";
            $errores .= $res['errores'];
        }
    }

    // busca si hay ventas a credito en la nota que no estan en el carrito para registrar el pago
    $consulta = "
	select nota_credito_cuerpo.idventa as registros_faltan
	from nota_credito_cuerpo
	inner join ventas on ventas.idventa = nota_credito_cuerpo.idventa
	where
	nota_credito_cuerpo.idnotacred = $idnotacred
	and ventas.tipo_venta = 2
	and nota_credito_cuerpo.idventa > 0
	and nota_credito_cuerpo.idventa not in (
		select cuentas_clientes.idventa
		from tmp_carrito_cobros 
		inner join cuentas_clientes on cuentas_clientes.idcta = tmp_carrito_cobros.idcta
		where 
		tmp_carrito_cobros.notacred = 'S'
		and tmp_carrito_cobros.idnotacred = $idnotacred
	 )
	 limit 1
	";
    $rscar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rscar->fields['registros_faltan'] > 0) {
        $valido = "N";
        $errores .= "-Existen ventas a credito que no se marcaron para afectar la cuenta del cliente.<br />";
    }


    // si no es valido borra carritos
    if ($valido == "N") {

        // si no es valido borra de los carritos
        $consulta = "
		update tmp_carrito_cobros set estado = 6 WHERE notacred = 'S' and idnotacred = $idnotacred
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		delete from tmp_carrito_cobros_fpag WHERE notacred = 'S' and idnotacred = $idnotacred
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }
    // si todo es correcto actualiza
    if ($valido == "S") {


        $consulta = "
		select *, nota_credito_cuerpo.cantidad, nota_credito_cuerpo.codproducto,
		(
			select idinsumo 
			from productos 
			inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
			where 
			idprod_serial = nota_credito_cuerpo.codproducto
		) as idinsumo,
		(
		select 
		sum(subtotal_costo)/sum(cantidad) as pcosto
		from ventas_detalles
		where 
		idventa = nota_credito_cuerpo.idventa
		and idprod = nota_credito_cuerpo.codproducto
		) as pcosto
		from nota_credito_cuerpo 
		where 
		idnotacred = $idnotacred
		and nota_credito_cuerpo.codproducto > 0
		";
        $rscuerpo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rscuerpo->EOF) {
            $iddeposito = $rscuerpo->fields['iddeposito'];
            $idinsumo = $rscuerpo->fields['idinsumo'];
            $cantidad = $rscuerpo->fields['cantidad'];
            $pcosto = floatval($rscuerpo->fields['pcosto']);
            $idproducto = $rscuerpo->fields['codproducto'];
            $subtotal = $rscuerpo->fields['subtotal'];
            $idventa = $rscuerpo->fields['idventa'];
            $ruc1 = $rscuerpo->fields['idventa'];

            // mover en base a venta_receta

            // mueve stock en los que aplica (temporal, cambiar a venta receta)
            if ($iddeposito > 0) {
                if ($idinsumo > 0) {
                    aumentar_stock_general($idinsumo, $cantidad, $iddeposito);
                    // aumenta stock costo
                    aumentar_stock($idinsumo, $cantidad, $pcosto, $iddeposito);
                    // registra el aumento // codrefer es idnotacredito y fechacomprobante es fecha nota de credito
                    movimientos_stock($idinsumo, $cantidad, $iddeposito, 13, '+', $idnotacred, $fecha_nota); // 13 nota de credito cliente



                }
            }



            // registra en caja vieja
            $insertar = "
			insert into gest_pagos
			(
			fecha,medio_pago,idcliente,total_cobrado,chequenum,banco,factura,recibo,tickete,estado,
			ruc,tipo_pago,idempresa,sucursal,efectivo,codtransfer,montotransfer,montocheque,cajero,fechareal,
			idventa,anulado_el,anulado_por,montovale,idpedido,idmesa,montotarjeta,numtarjeta,tipotarjeta,vueltogs,
			delivery,idcaja,rendido,fec_rendido,obs,reimpresofc,idnotacred,monto_notacred,
			idtipocajamov,tipomovdinero
			)
			values
			(
			'$fecha_nota',12,$idcliente,$subtotal,NULL,NULL,NULL,NULL,NULL,1,
			'$ruc_notacred',3,$idempresa,$idsucursal,0,0,0,0,$idusu,'$ahora',
			$idventa,NULL,NULL,0,0,0,0,0,0,0,
			0,$idcaja_old,NULL,NULL,NULL,NULL,$idnotacred,$subtotal,
			5,'S'
			)
			";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            $consulta = "
			select idpago from gest_pagos where idnotacred = $idnotacred order by idpago desc limit 1
			";
            $rsmaxpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idpago_gest = $rsmaxpag->fields['idpago'];

            $consulta = "
			INSERT INTO gest_pagos_det
			(idpago, monto_pago_det, idformapago) 
			VALUES 
			($idpago_gest, $subtotal, 1)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



            $rscuerpo->MoveNext();
        }

        // registra en caja nueva
        $consulta = "
		select sum(subtotal) as subtotal from nota_credito_cuerpo where idnotacred = $idnotacred
		";
        $rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $total_notacredito = $rstot->fields['subtotal'];
        // genera cabecera de caja
        $parametros_caja['idcaja'] = $idcaja_new;
        $parametros_caja['idtipocajamov'] = 5; // Nota de credito a cliente
        $parametros_caja['tipomovdinero'] = 'E'; // E: entrada S: salida
        $parametros_caja['monto_movimiento'] = $total_notacredito;
        $parametros_caja['idmoneda'] = 1; // guaranies
        $parametros_caja['fechahora_mov'] = $ahora;
        $parametros_caja['registrado_por'] = $idusu;

        // campos segun forma de cobro
        $i = 1;
        $parametros_caja['detalles'][$i]['monto_movimiento'] = $total_notacredito;
        $parametros_caja['detalles'][$i]['idformapago'] = 12; // nota credito
        $parametros_caja['detalles'][$i]['fecha_vencimiento'] = '';
        $parametros_caja['detalles'][$i]['fecha_emision'] = '';
        $parametros_caja['detalles'][$i]['idbanco'] = '';
        $parametros_caja['detalles'][$i]['idtipopersona_titular'] = '';
        $parametros_caja['detalles'][$i]['idpaisdoc_titular'] = '';
        $parametros_caja['detalles'][$i]['idtipodoc_titular'] = '';
        $parametros_caja['detalles'][$i]['idtipodoc_titular'] = '';
        $parametros_caja['detalles'][$i]['nrodoc_titular'] = '';
        $parametros_caja['detalles'][$i]['nomape_titular'] = '';
        $parametros_caja['detalles'][$i]['nrochq'] = '';
        $parametros_caja['detalles'][$i]['idcuentacon'] = 1;

        // registra movimiento de caja gestion
        $idcajamov = caja_movimiento_registra($parametros_caja);
        if ($res['valido'] == 'N') {
            $valido = $res['valido'];
            $errores .= nl2br($res['errores']);
        }

        if ($idcajamov == 0) {
            echo "No se envio el movimiento de caja de gestion.";
            exit;
        }

        // afecta las cuentas del cliente solo de las facturas a credito si existieren
        if ($ex_credito == 'S') {
            $datos_ar = [
                'fecha_pago' => $fecha_nota,
                'idcliente' => $idcliente_notacred,
                'registrado_por' => $idusu,
                'registrado_el' => $ahora,
                'idcaja_new' => $idcaja_new,
                'idcaja_old' => $idcaja_old,
                'recibo' => '',
                'idcajamov' => $idcajamov,
                'idnotacredito' => $idnotacred,
                'notacredito' => $notacredito_numero,
            ];

            // afectar estado de cuenta del cliente
            $res = pagar_cuentacliente($datos_ar);
            $idcuentaclientepagcab = $res['idcuentaclientepagcab'];
            if (intval($idcuentaclientepagcab) == 0) {
                echo "-Ocurrio un error y no se registro correctamente, contacte al soporte.";
                exit;
            }


            // registra idnotacred en cuentas_clientes_pagos_cab
            $consulta = "
			update cuentas_clientes_pagos_cab 
			set  
			notanum = $idnotacred,
			notacredito = '$notacredito_numero'
			where 
			idcuentaclientepagcab = $idcuentaclientepagcab
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // asignar a nota de credito
            $consulta = "
			update nota_credito_cabeza
			set
				idcuentaclientepagcab=$idcuentaclientepagcab,
				idcajamov = $idcajamov
			where
				idnotacred = $idnotacred
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        }


        // cambia estado a finalizado
        $consulta = "
		update nota_credito_cabeza
		set
			estado = 3,
			idcajaaplicar=$idcaja_old
		where
			idnotacred = $idnotacred
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // borra de los carritos
        $consulta = "
		update tmp_carrito_cobros set estado = 6 WHERE notacred = 'S' and idnotacred = $idnotacred
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		delete from tmp_carrito_cobros_fpag WHERE notacred = 'S' and idnotacred = $idnotacred
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




        ///circuito para actualizar  ordenes de retiro para operativa RDE
        if ($idtipomotivo_devolucion != 0 && $idmotivo_nota_cred == $idtipomotivo_devolucion) {
            // en cabeza idcliente
            // 	$idcliente
            // 	$idventa
            // 	$consulta="
            // update tmp_ca
            // rrito_cobros set estado = 6 WHERE notacred = 'S' and idnotacred = $idnotacred
            // ";
            // $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            // $consulta="
            // delete from tmp_carrito_cobros_fpag WHERE notacred = 'S' and idnotacred = $idnotacred
            // ";
            // $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        }

        header("location: nota_credito_cabeza.php");
        exit;

    }

}

// control de formulario despues de recibir el post y valididtipomotivo_devolucionar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
function abrir_facturas(){

	///////////////////////////////////////
	var idcliente_notacred = <?php echo $idcliente_notacred;?>;
	var devolucion = <?php echo $idtipomotivo_devolucion != 0 && $idmotivo_nota_cred == $idtipomotivo_devolucion ? true : false ?>;
	var direccionurl='facturas_venta_detalles.php';	
	var razon_social = <?php echo $rs->fields['razon_social'] != "" && $rs->fields['razon_social'] != "NULL" ? "'".$rs->fields['razon_social']."'" : "'SIN RAZON SOCIAL'" ?>;
	var parametros = {
	  "idcliente_notacred" : idcliente_notacred,
	  "devolucion" : devolucion
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
			// $("#facturas_det_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			alerta_modal(razon_social,(response));	
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});


}
function alerta_modal(titulo,mensaje){
	$('#dialogobox').modal('show');
	$("#myModalLabel").html(titulo);
	$("#modal_cuerpo").html(mensaje);

	
}
function cerrar_modal(){
	$('#dialogobox').modal('hide');
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; 
  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function buscar_factura_varias(idventa){
	$("#factura").val('');
	$("#idventa").val(idventa);
	buscar_factura();
}
function buscar_factura(){
	
	var factura = $("#factura").val();	
	var idorden_retiro = $("#factura").attr('data-hidden-idorden-retiro');
	if(idorden_retiro == undefined || idorden_retiro == '' ){
		idorden_retiro = 0;
	}
	var idventa = $("#idventa").val();	
	var clase = $("#clase").val();	
	if(parseInt(idventa) > 0){
		$("#factura").val('');
	}
	
	

	//////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////

	var direccionurl='nota_credito_cuerpo_fac_add_orden_devolucion.php';	
	var parametros = {
	"clase"             : clase,
	"idorden_retiro"	: idorden_retiro,
	"idnotacred"        : <?php echo intval($idnotacred); ?>
	};


	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
		},
		success:  function (response, textStatus, xhr) {
			console.log(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
	});
	//////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////


	var direccionurl='nota_credito_cuerpo_fac.php';	
	var parametros = {
	  "factura"       : factura,
	  "idventa"       : idventa,
	  "clase"         : clase,
	  "idorden_retiro": idorden_retiro,
	  "idnotacred"    : <?php echo intval($idnotacred); ?>,
	};




	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#facturas_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {

			$("#facturas_box").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});


	// facturas_det_box


	//////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////

	var direccionurl='nota_credito_cuerpo_det.php';	
	var parametros = {
	"clase"             : clase,
	"idorden_retiro"	: idorden_retiro,
	"idnotacred"        : <?php echo intval($idnotacred); ?>
	};


	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
		},
		success:  function (response, textStatus, xhr) {
			$("#facturas_det_box").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
	});
	//////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////

	// nota_credito_cuerpo_det_devolucion


	var direccionurl='nota_credito_cuerpo_det_devolucion.php';	
	var parametros = {
	"clase"             : clase,
	"idorden_retiro"	: idorden_retiro,
	"idnotacred"        : <?php echo intval($idnotacred); ?>
	};

	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
		},
		success:  function (response, textStatus, xhr) {
			$("#facturas_det_box_devolucion").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
	});


	
}
function cargar_monto_factura(idventa){
	
	var monto = $("#totalcobrar").val();	
	var concepto = $("#concepto").val();
	var direccionurl='nota_credito_cuerpo_fac_add.php';	
	var parametros = {
	  "idventa"     : idventa,
	  "monto"       : monto,
	  "concepto"    : concepto,
	  "clase"       : 2,
	  "idnotacred"  : <?php echo intval($idnotacred); ?>,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
			$("#facturas_det_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			//alert(response);
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					//alert('a');
					actualizar_detalle();
				}else{
					alerta_modal('Errores',nl2br(obj.errores));	
					actualizar_detalle();
				}
			}else{
				//alert(response);
				$("#facturas_det_box").html(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}

function cargar_articulo_sinfactura(idproducto,clase){
	
	

	var cantidad = $("#idprod_cant_"+idproducto).val();
	var precio_unitario = $("#idprod_monto_"+idproducto).val();
	var iddeposito = $("#iddeposito_"+idproducto).val();
	var direccionurl='nota_credito_cuerpo_fac_add_sf.php';	
	var parametros = {
	  "idproducto"      : idproducto,
	  "iddeposito"      : iddeposito,
	  "cantidad"        : cantidad,
	  "precio_unitario" : precio_unitario,
	  "clase"           : clase,
	  "idnotacred"      : <?php echo intval($idnotacred); ?>,
	  "aplica_fact"     : 'N'
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
			$("#facturas_det_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			//alert(response);
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					//alert('a');
					actualizar_detalle();
					buscar_factura();
				}else{
					alerta_modal('Errores',nl2br(obj.errores));	
					actualizar_detalle();
				}
			}else{
				//alert(response);
				$("#facturas_det_box").html(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
function cargar_articulo_factura(idventa,idproducto,clase){
	
	

	var cantidad = $("#idprod_"+idproducto).val();
	var iddeposito = $("#iddeposito_"+idproducto).val();
	var direccionurl='nota_credito_cuerpo_fac_add.php';	
	var parametros = {
	  "idventa"     : idventa,
	  "idproducto"  : idproducto,
	  "iddeposito"  : iddeposito,
	  "cantidad"    : cantidad,
	  "clase"       : clase,
	  "idnotacred"  : <?php echo intval($idnotacred); ?>,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
			$("#facturas_det_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			//alert(response);
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					//alert('a');
					actualizar_detalle();
					buscar_factura();
				}else{
					alerta_modal('Errores',nl2br(obj.errores));	
					actualizar_detalle();
				}
			}else{
				//alert(response);
				$("#facturas_det_box").html(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
function cargar_monto_articulo_sinfactura(idproducto,clase){
	
	

	var monto_articulo = $("#idprod_"+idproducto).val();
	var iddeposito = $("#iddeposito_"+idproducto).val();
	var direccionurl='nota_credito_cuerpo_fac_add_sf.php';	
	var parametros = {
	  "idproducto"        : idproducto,
	  "iddeposito"        : iddeposito,
	  "monto_articulo"    : monto_articulo,
	  "clase"             : clase,
	  "idnotacred"        : <?php echo intval($idnotacred); ?>,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
			$("#facturas_det_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			//alert(response);
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					//alert('a');
					actualizar_detalle();
					buscar_factura();
				}else{
					alerta_modal('Errores',nl2br(obj.errores));	
					actualizar_detalle();
				}
			}else{
				//alert(response);
				$("#facturas_det_box").html(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
function cargar_monto_articulo_factura(idventa,idproducto,clase){

	var monto_articulo = $("#idprod_"+idproducto).val();
	var iddeposito = $("#iddeposito_"+idproducto).val();
	var direccionurl='nota_credito_cuerpo_fac_add.php';	
	var parametros = {
	  "idventa"           : idventa,
	  "idproducto"        : idproducto,
	  "iddeposito"        : iddeposito,
	  "monto_articulo"    : monto_articulo,
	  "clase"             : clase,
	  "idnotacred"        : <?php echo intval($idnotacred); ?>,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
			$("#facturas_det_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			//alert(response);
			if(IsJsonString(response)){
				var obj = jQuery.parseJSON(response);
				if(obj.valido == 'S'){
					//alert('a');
					actualizar_detalle();
					buscar_factura();
				}else{
					alerta_modal('Errores',nl2br(obj.errores));	
					actualizar_detalle();
				}
			}else{
				//alert(response);
				$("#facturas_det_box").html(response);	
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
function actualizar_detalle(){
	
	var direccionurl='nota_credito_cuerpo_det.php';	
	var parametros = {
	  "idnotacred"    : <?php echo intval($idnotacred); ?>,
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			//$("#facturas_box").html('Cargando...');
			$("#facturas_det_box").html('Cargando...');			
		},
		success:  function (response, textStatus, xhr) {
			$("#facturas_det_box").html(response);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
function aplicar(tipo){
	if(tipo == 'A'){
		$("#factura_box").show();
		$("#idventa_box").show();
		$("#busfac_box").show();
		$("#producto_box").hide();
		$("#codproducto_box").hide();
		$("#codbarra_box").hide();
	}
	if(tipo == 'N'){
		$("#factura_box").hide();
		$("#idventa_box").hide();
		$("#busfac_box").hide();
		$("#producto_box").show();
		$("#codproducto_box").show();
		$("#codbarra_box").show();
	}
	
}

function buscar_producto(){
	

	var producto = $("#producto").val();
	var codigo = $("#codigo").val();
	var codbar = $("#codbar").val();
	var clase = $("#clase").val();	
	var direccionurl='nota_credito_cli_bus_producto.php';	
	var parametros = {
	  "MM_insert"	  : "form1",
	  "producto"	  : producto,
	  "codigo" 		  : codigo,
	  "codbar" 		  : codbar,
	  "clase"         : clase,
	  "idnotacred"    : <?php echo intval($idnotacred); ?>
	};
	$.ajax({		  
		data:  parametros,
		url:   direccionurl,
		type:  'post',
		cache: false,
		timeout: 3000,  // I chose 3 secs for kicks: 3000
		crossDomain: true,
		beforeSend: function () {
			$("#facturas_box").html('Cargando...');				
		},
		success:  function (response, textStatus, xhr) {
			if(xhr.status === 200){
				$("#facturas_box").html(response);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if(jqXHR.status == 404){
				alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
			}else if(jqXHR.status == 0){
				alert('Se ha rechazado la conexión.');
			}else{
				alert(jqXHR.status+' '+errorThrown);
			}
		}
		
		
	}).fail( function( jqXHR, textStatus, errorThrown ) {
		
		if (jqXHR.status === 0) {
	
			alert('No conectado: verifique la red.');
		
		} else if (jqXHR.status == 404) {
		
			alert('Pagina no encontrada [404]');
		
		} else if (jqXHR.status == 500) {
		
			alert('Internal Server Error [500].');
		
		} else if (textStatus === 'parsererror') {
		
			alert('Requested JSON parse failed.');
		
		} else if (textStatus === 'timeout') {
		
			alert('Tiempo de espera agotado, time out error.');
		
		} else if (textStatus === 'abort') {
		
			alert('Solicitud ajax abortada.'); // Ajax request aborted.
		
		} else {
		
			alert('Uncaught Error: ' + jqXHR.responseText);
		
		}
		
	});
}
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Nota de Credito a Clientes</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
                  
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idnotacred</th>
			<th align="center">Motivo</th>
			<th align="center">Sucursal</th>
			<th align="center">Fecha nota</th>
			<th align="center">Numero Nota</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Estado</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>


		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo antixss($rs->fields['idnotacred']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['motivo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_nota'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_nota']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['numero']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center">Cargando</td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<select name="clase" id="clase" class="form-control">
	  <option>Seleccionar...</option>
	  <?php if ($preferencias_devolucion_impotacion == 1) { ?>
		<?php if ((intval($idtipomotivo_devolucion) != 0) && (intval($idmotivo_nota_cred) != intval($idtipomotivo_devolucion))) { ?>
		  <option value="1">ARTICULO (afecta stock)</option>
		<?php } ?>
	  <?php } else { ?>
		<option value="1">ARTICULO (afecta stock)</option>
	  <?php } ?>
	  <option value="2">MONTO GLOBAL (no afecta stock)</option>
      <option value="3">MONTO ARTICULO (no afecta stock)</option>
    </select>                    
    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Aplicacion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<select name="aplicar" id="aplicar" class="form-control" required onChange="aplicar(this.value);">
    <option value="">Seleccionar...</option>
	  <option value="A">APLICAR A FACTURAS</option>
      <?php if ($rsco->fields['nc_sinventa'] == 'S') { ?>
	  <option value="N">NO APLICAR A FACTURA</option>
      <?php } ?>
    </select>                    
    
	</div>
</div>



<!-- //////////////////////////// ABRIR MODAL DE FACTURA/////////////////////////////////// -->

<div class="col-md-6 col-sm-6 form-group" id="factura_box"  <?php if ($rsco->fields['nc_sinventa'] == 'S') { ?>style="display:none;"<?php } ?>>
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
		<input readonly onclick="abrir_facturas()" type="text" name="factura" id="factura" value="<?php  if (isset($_POST['factura_numero'])) {
		    echo htmlentities($_POST['factura_numero']);
		} else {
		    echo htmlentities($rs->fields['factura_numero']);
		}?>" placeholder="Factura" style="cursor:pointer;" class="form-control"  />                    
	</div>
</div>

<!-- ///////////////////////////////////////////////////////////////// -->

<div class="col-md-6 col-sm-6 form-group" id="idventa_box"  <?php if ($rsco->fields['nc_sinventa'] == 'S') { ?>style="display:none;"<?php } ?>>
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idventa </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idventa" id="idventa" value="<?php  if (isset($_POST['idventa'])) {
	    echo htmlentities($_POST['idventa']);
	} else {
	    echo htmlentities($rs->fields['idventa']);
	}?>" placeholder="idventa" class="form-control"  />                    
    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group" id="producto_box"  style="display:none;">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Producto </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="producto" id="producto" value="<?php  if (isset($_POST['producto'])) {
	    echo htmlentities($_POST['producto']);
	} else {
	    echo htmlentities($rs->fields['producto']);
	}?>" placeholder="Producto" class="form-control" onKeyUp="buscar_producto();"  />                    
    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="codproducto_box"  style="display:none;">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Producto</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codigo" id="codigo" value="<?php  if (isset($_POST['codigo'])) {
	    echo htmlentities($_POST['codigo']);
	} else {
	    echo htmlentities($rs->fields['codigo']);
	}?>" placeholder="Codigo Producto" class="form-control"  />                    
    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group" id="codbarra_box"  style="display:none;">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Barras </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codbar" id="codbar" value="<?php  if (isset($_POST['codbar'])) {
	    echo htmlentities($_POST['codbar']);
	} else {
	    echo htmlentities($rs->fields['codbar']);
	}?>" placeholder="Codigo Barras" class="form-control"  />                    
    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group"  id="busfac_box"  <?php if ($rsco->fields['nc_sinventa'] == 'S') { ?>style="display:none;"<?php } ?>>
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
	   		<button type="button" class="btn btn-default" onMouseUp="buscar_factura();" ><span class="fa fa-search"></span> Buscar Factura</button>

        </div>
    </div>
    
<div class="clearfix"></div>
<br />

<div id="facturas_box"></div>
<div id="facturas_det_box">
<?php require_once("nota_credito_cuerpo_det.php"); ?>
</div>
	<div id="facturas_det_box_devolucion">
	
	</div>
<div class="clearfix"></div>
<br />

<form id="form1" name="form1" method="post" action="">


    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Finalizar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='nota_credito_cabeza.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 


            
          </div>
        </div>
        <!-- /page content -->
        
        <!-- POPUP DE MODAL OCULTO -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo" style="max-height: 50vh; overflow-y: auto;">
						...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
